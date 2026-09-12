<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Office;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrgHierarchyService
{
    private const HEAD_ROLE_NAMES = [
        'Head of Department',
        'Head of Office',
        'Head of Project',
        'Head of Team',
    ];

    private bool $synchronized = false;

    public function sync(): void
    {
        DB::transaction(function (): void {
            DB::table('org_unit_edges')->delete();

            $this->insertEdges(Department::query()->whereNotNull('parent_department_id')->get(['department_id', 'parent_department_id']), 'department', 'parent_department_id');
            $this->insertEdges(Office::query()->whereNotNull('department_id')->get(['office_id', 'department_id']), 'office', 'department_id', 'department');
            $this->insertEdges(Office::query()->whereNotNull('parent_office_id')->get(['office_id', 'parent_office_id']), 'office', 'parent_office_id', 'office');
            $this->insertEdges(Project::query()->whereNotNull('department_id')->get(['project_id', 'department_id']), 'project', 'department_id', 'department');
            $this->insertEdges(Project::query()->whereNotNull('primary_office_id')->get(['project_id', 'primary_office_id']), 'project', 'primary_office_id', 'office');
            $this->insertPivotEdges('project_office', 'office', 'project', 'office_id', 'project_id');
            $this->insertEdges(Project::query()->whereNotNull('team_id')->get(['project_id', 'team_id']), 'team', 'project_id', 'project');
            $this->insertPivotEdges('project_teams', 'team', 'project', 'team_id', 'project_id');
            $this->insertEdges(Team::query()->whereNotNull('parent_team_id')->get(['team_id', 'parent_team_id']), 'team', 'parent_team_id', 'team');
        });

        $this->synchronized = true;
    }

    public function getDescendants(object $entity): Collection
    {
        $this->ensureSynchronized();

        return $this->recursiveQuery('child_type', 'child_id', 'parent_type', 'parent_id', $this->nodeType($entity), $entity->getKey());
    }

    public function getAncestors(object $entity): Collection
    {
        $this->ensureSynchronized();

        return $this->recursiveQuery('parent_type', 'parent_id', 'child_type', 'child_id', $this->nodeType($entity), $entity->getKey());
    }

    public function canManage(User $user, object $entity): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->hasPermission('manage_system_settings') || $user->hasRole('Super Admin')) {
            return true;
        }

        $scopes = $this->scopeKeys($entity);
        $roles = DB::table('user_roles')
            ->join('roles', 'roles.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->user_id)
            ->whereIn('roles.role_name', self::HEAD_ROLE_NAMES)
            ->where(function ($query) use ($scopes): void {
                foreach ($scopes as $index => [$type, $id]) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}(function ($scopeQuery) use ($type, $id): void {
                        $scopeQuery->where('user_roles.scope_type', $type)
                            ->where('user_roles.scope_id', $id);
                    });
                }
            })
            ->exists();

        return $roles;
    }

    public function getEffectiveScope(User $user): Collection
    {
        $assignments = DB::table('user_roles')
            ->join('roles', 'roles.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->user_id)
            ->whereIn('roles.role_name', self::HEAD_ROLE_NAMES)
            ->whereNotNull('user_roles.scope_type')
            ->get([
                'roles.role_name',
                'user_roles.scope_type',
                'user_roles.scope_id',
            ]);

        $scope = collect();
        foreach ($assignments as $assignment) {
            $nodeType = $this->normalizeNodeType($assignment->scope_type);
            $scope->push((object) [
                'role_name' => $assignment->role_name,
                'node_type' => $nodeType,
                'node_id' => (int) $assignment->scope_id,
                'depth' => 0,
            ]);

            foreach ($this->recursiveQuery('child_type', 'child_id', 'parent_type', 'parent_id', $nodeType, (int) $assignment->scope_id) as $descendant) {
                $scope->push((object) [
                    'role_name' => $assignment->role_name,
                    'node_type' => $descendant->node_type,
                    'node_id' => (int) $descendant->node_id,
                    'depth' => (int) $descendant->depth,
                ]);
            }
        }

        return $scope->unique(fn ($node) => $node->node_type.':'.$node->node_id)->values();
    }

    public function assignHead(User $user, object $entity): void
    {
        $entity->heads()->firstOrCreate(['user_id' => $user->user_id]);
        $role = Role::where('role_name', $this->headRoleName($entity))->firstOrFail();
        app(RbacService::class)->assignRole($user, $role, $entity);
    }

    public function revokeHead(User $user, object $entity): void
    {
        $entity->heads()->where('user_id', $user->user_id)->delete();
        $role = Role::where('role_name', $this->headRoleName($entity))->first();
        if ($role) {
            app(RbacService::class)->revokeRole($user, $role, $entity);
        }
    }

    protected function insertEdges(Collection $rows, string $childType, string $parentColumn, string $parentType = 'department'): void
    {
        $rows->each(function ($row) use ($childType, $parentColumn, $parentType): void {
            DB::table('org_unit_edges')->insertOrIgnore([
                'parent_type' => $parentType,
                'parent_id' => $row->{$parentColumn},
                'child_type' => $childType,
                'child_id' => $row->{$childType.'_id'},
            ]);
        });
    }

    protected function insertPivotEdges(string $table, string $childType, string $parentType, string $childColumn, string $parentColumn): void
    {
        DB::table($table)->get([$childColumn, $parentColumn])->each(function ($row) use ($childType, $parentType, $childColumn, $parentColumn): void {
            DB::table('org_unit_edges')->insertOrIgnore([
                'parent_type' => $parentType,
                'parent_id' => $row->{$parentColumn},
                'child_type' => $childType,
                'child_id' => $row->{$childColumn},
            ]);
        });
    }

    protected function recursiveQuery(string $selectType, string $selectId, string $joinType, string $joinId, string $rootType, int|string $rootId): Collection
    {
        $sql = "WITH RECURSIVE tree(node_type, node_id, depth) AS (
            SELECT {$selectType}, {$selectId}, 1
            FROM org_unit_edges
            WHERE {$joinType} = ? AND {$joinId} = ?
            UNION
            SELECT e.{$selectType}, e.{$selectId}, tree.depth + 1
            FROM org_unit_edges e
            INNER JOIN tree ON e.{$joinType} = tree.node_type AND e.{$joinId} = tree.node_id
        ) SELECT node_type, node_id, MIN(depth) AS depth FROM tree GROUP BY node_type, node_id ORDER BY depth, node_type, node_id";

        return collect(DB::select($sql, [$rootType, $rootId]));
    }

    protected function scopeKeys(object $entity): array
    {
        $self = [[$this->nodeType($entity), $entity->getKey()]];
        $ancestors = $this->getAncestors($entity)->map(fn ($node) => [$node->node_type, $node->node_id])->all();

        return array_merge($self, $ancestors);
    }

    protected function nodeType(object $entity): string
    {
        return match (true) {
            $entity instanceof Department => 'department',
            $entity instanceof Office => 'office',
            $entity instanceof Project => 'project',
            $entity instanceof Team => 'team',
            default => throw new \InvalidArgumentException('Unsupported organizational entity: '.get_class($entity)),
        };
    }

    protected function headRoleName(object $entity): string
    {
        return 'Head of '.ucfirst($this->nodeType($entity));
    }

    protected function ensureSynchronized(): void
    {
        if (! $this->synchronized) {
            $this->sync();
        }
    }

    protected function normalizeNodeType(string $type): string
    {
        return match ($type) {
            'App\\Models\\Department', 'department' => 'department',
            'App\\Models\\Office', 'office' => 'office',
            'App\\Models\\Project', 'project' => 'project',
            'App\\Models\\Team', 'team' => 'team',
            default => $type,
        };
    }
}
