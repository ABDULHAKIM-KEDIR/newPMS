<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\OrgHierarchyService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_only_see_participating_projects_in_their_office(): void
    {
        $this->seed(RbacSeeder::class);

        $officeA = Office::create(['office_name' => 'Office A', 'office_code' => 'OA', 'status' => 'Active']);
        $officeB = Office::create(['office_name' => 'Office B', 'office_code' => 'OB', 'status' => 'Active']);
        $member = User::create([
            'full_name' => 'Office A Member',
            'email' => 'member@office-a.test',
            'password_hash' => bcrypt('password'),
            'status' => 'Active',
            'office_id' => $officeA->office_id,
        ]);
        $member->roles()->attach(Role::where('role_name', 'Team Member')->value('role_id'));

        $participatingTeam = Team::create([
            'team_name' => 'Participating Team',
            'office_id' => $officeA->office_id,
            'status' => 'Active',
        ]);
        $unrelatedTeam = Team::create([
            'team_name' => 'Unrelated Team',
            'office_id' => $officeA->office_id,
            'status' => 'Active',
        ]);
        $otherOfficeTeam = Team::create([
            'team_name' => 'Other Office Team',
            'office_id' => $officeB->office_id,
            'status' => 'Active',
        ]);

        TeamMember::create(['team_id' => $participatingTeam->team_id, 'user_id' => $member->user_id]);
        // Represents stale or forged data; office isolation must still win.
        TeamMember::create(['team_id' => $otherOfficeTeam->team_id, 'user_id' => $member->user_id]);

        $participatingProject = $this->makeProject($member, $officeA, $participatingTeam, 'Participating project');
        $unrelatedProject = $this->makeProject($member, $officeA, $unrelatedTeam, 'Unrelated project');
        $otherOfficeProject = $this->makeProject($member, $officeB, $otherOfficeTeam, 'Other office project');

        $visibleProjects = Project::query()->visibleTo($member)->pluck('project_id');

        $this->assertTrue($visibleProjects->contains($participatingProject->project_id));
        $this->assertFalse($visibleProjects->contains($unrelatedProject->project_id));
        $this->assertFalse($visibleProjects->contains($otherOfficeProject->project_id));
        $this->assertTrue(app('App\\Policies\\ProjectPolicy')->view($member, $participatingProject));
        $this->assertFalse(app('App\\Policies\\ProjectPolicy')->view($member, $unrelatedProject));
        $this->assertFalse(app('App\\Policies\\ProjectPolicy')->view($member, $otherOfficeProject));
    }

    public function test_office_heads_are_limited_to_their_office_projects_and_teams(): void
    {
        $this->seed(RbacSeeder::class);

        $officeA = Office::create(['office_name' => 'Office A', 'office_code' => 'OA', 'status' => 'Active']);
        $officeB = Office::create(['office_name' => 'Office B', 'office_code' => 'OB', 'status' => 'Active']);
        $officeHead = User::create([
            'full_name' => 'Office A Head',
            'email' => 'head@office-a.test',
            'password_hash' => bcrypt('password'),
            'status' => 'Active',
            'office_id' => $officeA->office_id,
        ]);
        $officeHead->roles()->attach(Role::where('role_name', 'Head of Office')->value('role_id'));
        app(OrgHierarchyService::class)->assignHead($officeHead, $officeA);

        $officeATeam = Team::create(['team_name' => 'Office A Team', 'office_id' => $officeA->office_id, 'status' => 'Active']);
        $officeBTeam = Team::create(['team_name' => 'Office B Team', 'office_id' => $officeB->office_id, 'status' => 'Active']);
        $officeAProject = $this->makeProject($officeHead, $officeA, $officeATeam, 'Office A Project');
        $officeBProject = $this->makeProject($officeHead, $officeB, $officeBTeam, 'Office B Project');

        $this->assertSame([$officeATeam->team_id], Team::query()->visibleTo($officeHead)->pluck('team_id')->all());
        $this->assertSame([$officeAProject->project_id], Project::query()->visibleTo($officeHead)->pluck('project_id')->all());
        $this->assertTrue(app('App\\Policies\\ProjectPolicy')->view($officeHead, $officeAProject));
        $this->assertFalse(app('App\\Policies\\ProjectPolicy')->view($officeHead, $officeBProject));
        $this->assertTrue($officeAProject->isManagedBy($officeHead));
        $this->assertFalse($officeBProject->isManagedBy($officeHead));
        $this->assertTrue(app(OrgHierarchyService::class)->canManage($officeHead, $officeATeam));
        $this->assertFalse(app(OrgHierarchyService::class)->canManage($officeHead, $officeBTeam));
    }

    private function makeProject(User $creator, Office $office, Team $team, string $name): Project
    {
        $project = Project::create([
            'project_name' => $name,
            'project_type' => 'Software',
            'team_id' => $team->team_id,
            'primary_office_id' => $office->office_id,
            'created_by' => $creator->user_id,
            'status' => 'active',
        ]);

        $project->offices()->attach($office->office_id, ['participation_type' => 'primary']);

        return $project;
    }
}
