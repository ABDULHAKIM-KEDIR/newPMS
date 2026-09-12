<?php

use App\Models\Department;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectMemberRole;
use App\Models\ProjectTeam;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\OrgHierarchyService;
use App\Services\RbacService;
use App\Services\RoleManagementService;
use App\Support\Permissions;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rbac(): RbacService
{
    return app(RbacService::class);
}

beforeEach(function () {
    (new RbacSeeder)->run();
});

function makeProjectWithTeams(User $pm, Team $teams, string $level = 'contribute'): Project
{
    $project = Project::create([
        'project_name' => 'RBAC Test Project',
        'project_type' => 'Software',
        'team_id' => $teams->team_id,
        'project_manager_id' => $pm->user_id,
        'status' => 'active',
        'created_by' => $pm->user_id,
    ]);

    ProjectTeam::create([
        'project_id' => $project->project_id,
        'team_id' => $teams->team_id,
        'assigned_date' => now(),
        'access_level' => $level,
    ]);

    return $project;
}

it('grants organization role permissions system-wide', function () {
    $admin = User::create(['full_name' => 'Admin', 'email' => 'a@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $admin->roles()->attach(Role::where('role_name', 'Administrator')->value('role_id'));

    expect(rbac()->can($admin, 'manage_roles'))->toBeTrue()
        ->and(rbac()->can($admin, 'delete_projects'))->toBeTrue();
});

it('inherits head permissions through the organizational hierarchy', function () {
    $departmentHead = User::create(['full_name' => 'Department Head', 'email' => 'dh@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $unrelatedHead = User::create(['full_name' => 'Other Head', 'email' => 'oh@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $department = Department::create(['department_name' => 'Operations', 'department_code' => 'OPS', 'head_user_id' => $departmentHead->user_id]);
    $parentOffice = Office::create(['office_name' => 'Operations HQ', 'office_code' => 'OPS-HQ', 'department_id' => $department->department_id, 'head_user_id' => $unrelatedHead->user_id, 'status' => 'Active']);
    $office = Office::create(['office_name' => 'Operations Office', 'office_code' => 'OPS-O', 'department_id' => $department->department_id, 'parent_office_id' => $parentOffice->office_id, 'status' => 'Active']);
    $parentTeam = Team::create(['team_name' => 'Operations Team', 'office_id' => $parentOffice->office_id, 'status' => 'Active']);
    $team = Team::create(['team_name' => 'Operations Sub-Team', 'office_id' => $office->office_id, 'parent_team_id' => $parentTeam->team_id, 'status' => 'Active']);
    $project = Project::create([
        'project_name' => 'Operations Project',
        'project_type' => 'Software',
        'team_id' => $team->team_id,
        'primary_office_id' => $office->office_id,
        'department_id' => $department->department_id,
        'project_manager_id' => $unrelatedHead->user_id,
        'created_by' => $unrelatedHead->user_id,
        'status' => 'Planning',
    ]);

    rbac()->assignRole($departmentHead, Role::where('role_name', 'Head of Department')->firstOrFail(), $department);
    rbac()->assignRole($unrelatedHead, Role::where('role_name', 'Head of Office')->firstOrFail(), $office);

    expect(rbac()->can($departmentHead, 'manage_budgets', $project))->toBeTrue()
        ->and(rbac()->can($unrelatedHead, 'manage_budgets', $project))->toBeTrue()
        ->and(rbac()->canForScope($departmentHead, 'manage_offices', $office))->toBeTrue();
});

it('traverses arbitrary hierarchy depth through recursive edge queries', function () {
    $department = Department::create(['department_name' => 'Tree', 'department_code' => 'TREE']);
    $parentOffice = Office::create(['office_name' => 'Tree HQ', 'office_code' => 'TREE-HQ', 'department_id' => $department->department_id, 'status' => 'Active']);
    $childOffice = Office::create(['office_name' => 'Tree Branch', 'office_code' => 'TREE-B', 'department_id' => $department->department_id, 'parent_office_id' => $parentOffice->office_id, 'status' => 'Active']);
    $parentTeam = Team::create(['team_name' => 'Tree Team', 'office_id' => $parentOffice->office_id, 'status' => 'Active']);
    $childTeam = Team::create(['team_name' => 'Tree Sub-Team', 'office_id' => $childOffice->office_id, 'parent_team_id' => $parentTeam->team_id, 'status' => 'Active']);
    $project = Project::create([
        'project_name' => 'Tree Project',
        'project_type' => 'Software',
        'team_id' => $parentTeam->team_id,
        'primary_office_id' => $childOffice->office_id,
        'department_id' => $department->department_id,
        'created_by' => User::create(['full_name' => 'Tree Owner', 'email' => 'tree-owner@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active'])->user_id,
        'status' => 'Planning',
    ]);
    $project->teams()->attach($childTeam->team_id);

    $hierarchy = app(OrgHierarchyService::class);
    $descendants = $hierarchy->getDescendants($department);
    $ancestors = $hierarchy->getAncestors($childTeam);

    expect($descendants->pluck('node_type'))->toContain('office', 'project', 'team')
        ->and($ancestors->pluck('node_id'))->toContain($department->department_id, $parentOffice->office_id, $childOffice->office_id, $project->project_id, $parentTeam->team_id);
});

it('supports co-heads on one node and isolates separate team branches', function () {
    $headA = User::create(['full_name' => 'Head A', 'email' => 'head-a@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $headB = User::create(['full_name' => 'Head B', 'email' => 'head-b@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $manager = User::create(['full_name' => 'Manager', 'email' => 'manager@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $office = Office::create(['office_name' => 'Shared Office', 'office_code' => 'SHARED', 'status' => 'Active']);
    $teamA = Team::create(['team_name' => 'Team A', 'office_id' => $office->office_id, 'status' => 'Active']);
    $teamB = Team::create(['team_name' => 'Team B', 'office_id' => $office->office_id, 'status' => 'Active']);
    $projectA = makeProjectWithTeams($manager, $teamA);
    $projectB = Project::create([
        'project_name' => 'Project B',
        'project_type' => 'Software',
        'team_id' => $teamB->team_id,
        'primary_office_id' => $office->office_id,
        'project_manager_id' => $manager->user_id,
        'created_by' => $manager->user_id,
        'status' => 'Planning',
    ]);
    ProjectTeam::create(['project_id' => $projectB->project_id, 'team_id' => $teamB->team_id, 'assigned_date' => now(), 'access_level' => 'view']);

    $hierarchy = app(OrgHierarchyService::class);
    $hierarchy->assignHead($headA, $teamA);
    $hierarchy->assignHead($headB, $teamA);

    expect($teamA->heads()->count())->toBe(2)
        ->and($hierarchy->canManage($headA, $teamA))->toBeTrue()
        ->and($hierarchy->canManage($headB, $teamA))->toBeTrue()
        ->and(rbac()->can($headA, 'edit_projects', $projectA))->toBeFalse()
        ->and($hierarchy->canManage($headA, $projectB))->toBeFalse();
});

it('inherits parent-office leadership into child offices and projects', function () {
    $head = User::create(['full_name' => 'Regional Head', 'email' => 'regional@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $manager = User::create(['full_name' => 'Branch Manager', 'email' => 'branch@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $parentOffice = Office::create(['office_name' => 'Regional Office', 'office_code' => 'REGION', 'status' => 'Active']);
    $childOffice = Office::create(['office_name' => 'Branch Office', 'office_code' => 'BRANCH', 'parent_office_id' => $parentOffice->office_id, 'status' => 'Active']);
    $team = Team::create(['team_name' => 'Branch Team', 'office_id' => $childOffice->office_id, 'status' => 'Active']);
    $project = makeProjectWithTeams($manager, $team);
    $project->update(['primary_office_id' => $childOffice->office_id]);
    $hierarchy = app(OrgHierarchyService::class);
    $hierarchy->assignHead($head, $parentOffice);

    expect($hierarchy->canManage($head, $childOffice))->toBeTrue()
        ->and($hierarchy->canManage($head, $project))->toBeTrue();
});

it('allows Super Admin to bypass hierarchy scope checks', function () {
    $admin = User::create(['full_name' => 'Super', 'email' => 'super@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $admin->roles()->attach(Role::where('role_name', 'Super Admin')->value('role_id'));
    $team = Team::create(['team_name' => 'Any Team', 'status' => 'Active']);

    expect(app(OrgHierarchyService::class)->canManage($admin, $team))->toBeTrue();
});

it('honours role inheritance in the union of permissions', function () {
    $member = User::create(['full_name' => 'Member', 'email' => 'm@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $member->roles()->attach(Role::where('role_name', 'Team Lead')->value('role_id'));

    // Team Lead extends Team Member: inherited view permissions.
    expect(rbac()->can($member, 'view_projects'))->toBeTrue()
        ->and(rbac()->can($member, 'edit_projects'))->toBeTrue()
        ->and(rbac()->can($member, 'manage_budgets'))->toBeFalse();
});

it('denies inactive users everything', function () {
    $user = User::create(['full_name' => 'Off', 'email' => 'o@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Inactive']);
    $user->roles()->attach(Role::where('role_name', 'Administrator')->value('role_id'));

    expect(rbac()->can($user, 'view_projects'))->toBeFalse();
});

it('gives team members project access through a manage-level team assignment', function () {
    $pm = User::create(['full_name' => 'PM', 'email' => 'pm@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $pm->roles()->attach(Role::where('role_name', 'Project Manager')->value('role_id'));

    $member = User::create(['full_name' => 'Dev', 'email' => 'd@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);

    $team = Team::create(['team_name' => 'Core', 'team_leader_id' => $pm->user_id, 'status' => 'Active']);
    TeamMember::create(['team_id' => $team->team_id, 'user_id' => $member->user_id, 'joined_date' => now()]);

    $project = makeProjectWithTeams($pm, $team, 'manage');

    expect(rbac()->can($member, 'view_projects', $project))->toBeTrue()
        ->and(rbac()->can($member, 'edit_projects', $project))->toBeTrue()
        ->and(rbac()->can($member, 'delete_projects', $project))->toBeFalse();
});

it('restricts view-level teams to read-only project permissions', function () {
    $pm = User::create(['full_name' => 'PM2', 'email' => 'pm2@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $member = User::create(['full_name' => 'Watch', 'email' => 'w@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);

    $team = Team::create(['team_name' => 'Audit', 'team_leader_id' => $pm->user_id, 'status' => 'Active']);
    TeamMember::create(['team_id' => $team->team_id, 'user_id' => $member->user_id, 'joined_date' => now()]);

    $project = makeProjectWithTeams($pm, $team, 'view');

    expect(rbac()->can($member, 'view_tasks', $project))->toBeTrue()
        ->and(rbac()->can($member, 'update_task_status', $project))->toBeFalse();
});

it('applies a direct project-scoped role only inside that project', function () {
    $pm = User::create(['full_name' => 'PM3', 'email' => 'pm3@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $specialist = User::create(['full_name' => 'Spec', 'email' => 's@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);

    $team = Team::create(['team_name' => 'T3', 'team_leader_id' => $pm->user_id, 'status' => 'Active']);
    $project = makeProjectWithTeams($pm, $team, 'view');

    ProjectMemberRole::create([
        'project_id' => $project->project_id,
        'user_id' => $specialist->user_id,
        'role_id' => Role::where('role_name', 'Project Contributor')->value('role_id'),
        'assigned_date' => now(),
    ]);

    expect(rbac()->can($specialist, 'update_task_status', $project))->toBeTrue()
        // Organization-level check (no project) must NOT include project grant.
        ->and(rbac()->can($specialist, 'update_task_status'))->toBeFalse()
        ->and(rbac()->can($specialist, 'delete_projects', $project))->toBeFalse();
});

it('gives the project manager of record implicit manage permissions', function () {
    $pm = User::create(['full_name' => 'PM4', 'email' => 'pm4@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);

    $team = Team::create(['team_name' => 'T4', 'status' => 'Active']);
    $project = makeProjectWithTeams($pm, $team, 'view');

    expect(rbac()->can($pm, 'edit_projects', $project))->toBeTrue();
});

it('rejects role inheritance cycles at write time', function () {
    $service = app(RoleManagementService::class);

    $a = $service->createRole(['name' => 'Role A', 'description' => null, 'scope' => 'organization', 'parent_role_id' => null, 'rank' => 50]);
    $b = $service->createRole(['name' => 'Role B', 'description' => null, 'scope' => 'organization', 'parent_role_id' => $a->role_id, 'rank' => 50]);

    expect(app(RbacService::class)->setParentRole($a, $b))->toBeFalse()
        ->and(app(RbacService::class)->setParentRole($a, $a))->toBeFalse();
});

it('re-parents child roles when a role is deleted and protects only the Administrator role', function () {
    $service = app(RoleManagementService::class);

    $admin = User::create(['full_name' => 'Ad', 'email' => 'ad@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $admin->roles()->attach(Role::where('role_name', 'Administrator')->value('role_id'));

    $administrator = Role::where('role_name', 'Administrator')->first();
    expect($service->deleteRole($administrator))->toBeFalse();

    // Every other role — including system roles like Team Member — is deletable.
    $system = Role::where('role_name', 'Team Member')->first();
    expect($service->deleteRole($system))->toBeTrue();

    $parent = $service->createRole(['name' => 'P', 'description' => null, 'scope' => 'organization', 'parent_role_id' => null, 'rank' => 50]);
    $child = $service->createRole(['name' => 'C', 'description' => null, 'scope' => 'organization', 'parent_role_id' => $parent->role_id, 'rank' => 51]);
    $grandchild = $service->createRole(['name' => 'G', 'description' => null, 'scope' => 'organization', 'parent_role_id' => $child->role_id, 'rank' => 52]);

    expect($service->deleteRole($child))->toBeTrue()
        ->and($grandchild->refresh()->parent_role_id)->toBe($parent->role_id);
});

it('prevents removing the last holder of an administrative role', function () {
    $service = app(RoleManagementService::class);

    $admin = User::create(['full_name' => 'Last', 'email' => 'last@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $role = Role::where('role_name', 'Administrator')->first();
    $admin->roles()->attach($role->role_id);

    expect($service->revokeRoleFromUser($admin, $role))->toBeFalse();
});

it('syncs the permission catalogue through the gate', function () {
    expect(count(Permissions::ALL))->toBe(Permission::count());

    $member = User::create(['full_name' => 'G', 'email' => 'g@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $member->roles()->attach(Role::where('role_name', 'Team Member')->value('role_id'));

    expect($member->can('view_projects'))->toBeTrue()
        ->and($member->can('manage_users'))->toBeFalse();
});
