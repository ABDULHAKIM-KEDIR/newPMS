<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Office;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizational_hierarchy_relationships_are_recursive(): void
    {
        $user = User::create([
            'full_name' => 'Hierarchy Owner',
            'email' => 'hierarchy-owner@example.com',
            'password_hash' => bcrypt('password'),
            'status' => 'Active',
        ]);

        $department = Department::create([
            'department_name' => 'ICT Directorate',
            'department_code' => 'ICT-HIER',
            'status' => 'Active',
        ]);

        $parentOffice = Office::create([
            'office_name' => 'Software Development Office',
            'office_code' => 'SDO-HIER',
            'department_id' => $department->department_id,
            'status' => 'Active',
        ]);

        $childOffice = Office::create([
            'office_name' => 'Web Development Office',
            'office_code' => 'WDO-HIER',
            'department_id' => $department->department_id,
            'parent_office_id' => $parentOffice->office_id,
            'status' => 'Active',
        ]);

        $project = Project::create([
            'project_name' => 'Student Portal System',
            'project_type' => 'Software',
            'primary_office_id' => $childOffice->office_id,
            'status' => 'planning',
            'created_by' => $user->user_id,
        ]);

        $parentTeam = Team::create([
            'team_name' => 'Backend Team',
            'office_id' => $childOffice->office_id,
            'status' => 'Active',
        ]);

        $subTeam = Team::create([
            'team_name' => 'API Team',
            'office_id' => $childOffice->office_id,
            'parent_team_id' => $parentTeam->team_id,
            'status' => 'Active',
        ]);

        $project->teams()->attach($parentTeam->team_id);

        $this->assertTrue($childOffice->department->is($department));
        $this->assertTrue($parentOffice->childOffices->contains('office_id', $childOffice->office_id));
        $this->assertTrue($childOffice->parentOffice->is($parentOffice));
        $this->assertTrue($childOffice->projects->contains('project_id', $project->project_id));
        $this->assertTrue($project->office->is($childOffice));
        $this->assertTrue($project->teams->contains('team_id', $parentTeam->team_id));
        $this->assertTrue($subTeam->parentTeam->is($parentTeam));
        $this->assertTrue($parentTeam->childTeams->contains('team_id', $subTeam->team_id));
    }
}
