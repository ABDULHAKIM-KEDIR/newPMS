<?php

use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectMemberRole;
use App\Models\ProjectTeam;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
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

it('re-parents child roles when a role is deleted and protects system roles', function () {
    $service = app(RoleManagementService::class);

    $admin = User::create(['full_name' => 'Ad', 'email' => 'ad@t.io', 'password_hash' => bcrypt('x'), 'status' => 'Active']);
    $admin->roles()->attach(Role::where('role_name', 'Administrator')->value('role_id'));

    $system = Role::where('role_name', 'Team Member')->first();
    expect($service->deleteRole($system))->toBeFalse();

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
