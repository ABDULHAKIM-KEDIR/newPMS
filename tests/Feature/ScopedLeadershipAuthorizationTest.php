<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Office;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Services\OrgHierarchyService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ScopedLeadershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leadership_roles_manage_only_their_defined_subtrees(): void
    {
        $this->seed(RbacSeeder::class);

        $departmentA = Department::create([
            'department_name' => 'Department A',
            'department_code' => 'DEPT-A',
            'status' => 'Active',
        ]);
        $departmentB = Department::create([
            'department_name' => 'Department B',
            'department_code' => 'DEPT-B',
            'status' => 'Active',
        ]);

        $officeA = Office::create([
            'office_name' => 'Office A',
            'office_code' => 'OFF-A',
            'department_id' => $departmentA->department_id,
            'status' => 'Active',
        ]);
        $officeAChild = Office::create([
            'office_name' => 'Office A Child',
            'office_code' => 'OFF-A-CHILD',
            'department_id' => $departmentA->department_id,
            'parent_office_id' => $officeA->office_id,
            'status' => 'Active',
        ]);
        $officeB = Office::create([
            'office_name' => 'Office B',
            'office_code' => 'OFF-B',
            'department_id' => $departmentB->department_id,
            'status' => 'Active',
        ]);

        $departmentHead = $this->user('department-head@example.com');
        $officeHead = $this->user('office-head@example.com');
        $projectHead = $this->user('project-head@example.com');
        $teamHead = $this->user('team-head@example.com');
        $otherDepartmentHead = $this->user('other-department-head@example.com');
        $regularUser = $this->user('regular@example.com');
        $administrator = $this->user('administrator@example.com');

        $projectA = Project::create([
            'project_name' => 'Project A',
            'project_type' => 'Software',
            'primary_office_id' => $officeAChild->office_id,
            'department_id' => $departmentA->department_id,
            'project_manager_id' => $regularUser->user_id,
            'created_by' => $regularUser->user_id,
            'status' => 'Planning',
        ]);
        $projectB = Project::create([
            'project_name' => 'Project B',
            'project_type' => 'Software',
            'primary_office_id' => $officeB->office_id,
            'department_id' => $departmentB->department_id,
            'project_manager_id' => $regularUser->user_id,
            'created_by' => $regularUser->user_id,
            'status' => 'Planning',
        ]);

        $teamA = Team::create([
            'team_name' => 'Team A',
            'office_id' => $officeAChild->office_id,
            'status' => 'Active',
        ]);
        $subTeamA = Team::create([
            'team_name' => 'Sub-Team A',
            'office_id' => $officeAChild->office_id,
            'parent_team_id' => $teamA->team_id,
            'status' => 'Active',
        ]);
        $teamB = Team::create([
            'team_name' => 'Team B',
            'office_id' => $officeB->office_id,
            'status' => 'Active',
        ]);

        $projectA->teams()->attach($teamA->team_id);
        $projectA->teams()->attach($subTeamA->team_id);
        $projectB->teams()->attach($teamB->team_id);

        $hierarchy = app(OrgHierarchyService::class);
        $hierarchy->assignHead($departmentHead, $departmentA);
        $hierarchy->assignHead($officeHead, $officeA);
        $hierarchy->assignHead($projectHead, $projectA);
        $hierarchy->assignHead($teamHead, $teamA);
        $hierarchy->assignHead($otherDepartmentHead, $departmentB);

        $regularUser->roles()->attach(Role::where('role_name', 'Team Member')->value('role_id'));
        $administrator->roles()->attach(Role::where('role_name', 'Administrator')->value('role_id'));

        $this->assertTrue($hierarchy->canManage($departmentHead, $departmentA));
        $this->assertTrue($hierarchy->canManage($departmentHead, $officeAChild));
        $this->assertTrue($hierarchy->canManage($departmentHead, $projectA));
        $this->assertTrue($hierarchy->canManage($departmentHead, $teamA));
        $this->assertTrue($hierarchy->canManage($departmentHead, $subTeamA));
        $this->assertFalse($hierarchy->canManage($departmentHead, $officeB));
        $this->assertFalse($hierarchy->canManage($departmentHead, $projectB));

        $this->assertTrue($hierarchy->canManage($officeHead, $officeA));
        $this->assertTrue($hierarchy->canManage($officeHead, $projectA));
        $this->assertTrue($hierarchy->canManage($officeHead, $teamA));
        $this->assertTrue($hierarchy->canManage($officeHead, $subTeamA));
        $this->assertFalse($hierarchy->canManage($officeHead, $officeB));
        $this->assertFalse($hierarchy->canManage($officeHead, $projectB));

        $this->assertTrue($hierarchy->canManage($projectHead, $projectA));
        $this->assertTrue($hierarchy->canManage($projectHead, $teamA));
        $this->assertTrue($hierarchy->canManage($projectHead, $subTeamA));
        $this->assertFalse($hierarchy->canManage($projectHead, $projectB));

        $this->assertTrue($hierarchy->canManage($teamHead, $teamA));
        $this->assertTrue($hierarchy->canManage($teamHead, $subTeamA));
        $this->assertFalse($hierarchy->canManage($teamHead, $projectA));
        $this->assertFalse($hierarchy->canManage($teamHead, $teamB));

        $this->assertTrue(Gate::forUser($officeHead)->allows('manage_offices', $officeAChild));
        $this->assertTrue(Gate::forUser($teamHead)->allows('manage_team', $subTeamA));
        $this->assertTrue(Gate::forUser($projectHead)->allows('edit_projects', $projectA));
        $this->assertTrue(Gate::forUser($projectHead)->allows('view_projects', $projectA));
        $this->assertFalse(Gate::forUser($teamHead)->allows('edit_projects', $projectA));
        $this->assertFalse(Gate::forUser($regularUser)->allows('manage_team', $teamA));
        $this->assertTrue(Gate::forUser($administrator)->allows('manage_team', $teamB));
    }

    private function user(string $email): User
    {
        return User::create([
            'full_name' => ucwords(str_replace(['-', '@example.com'], [' ', ''], $email)),
            'email' => $email,
            'password_hash' => bcrypt('password'),
            'status' => 'Active',
        ]);
    }
}
