<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamOfficeRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    private Office $ict;

    private Office $finance;

    private Office $hr;

    private Team $ictTeam;

    private Team $financeTeam;

    private Team $hrTeam;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->director = User::where('email', 'director@example.com')->firstOrFail();

        $this->ict = Office::create(['office_name' => 'Test ICT Office', 'office_code' => 'TICT', 'is_active' => true]);
        $this->finance = Office::create(['office_name' => 'Test Finance Office', 'office_code' => 'TFIN', 'is_active' => true]);
        $this->hr = Office::create(['office_name' => 'Test HR Office', 'office_code' => 'THR', 'is_active' => true]);

        $this->ictTeam = Team::create(['team_name' => 'Test ICT Team', 'office_id' => $this->ict->office_id, 'status' => 'Active']);
        $this->financeTeam = Team::create(['team_name' => 'Test Finance Team', 'office_id' => $this->finance->office_id, 'status' => 'Active']);
        $this->hrTeam = Team::create(['team_name' => 'Test HR Team', 'office_id' => $this->hr->office_id, 'status' => 'Active']);

        $this->project = Project::create([
            'project_name' => 'Office Restriction Test Project',
            'project_type' => 'Software',
            'team_id' => $this->ictTeam->team_id,
            'primary_office_id' => $this->ict->office_id,
            'status' => 'planning',
            'created_by' => $this->director->user_id,
        ]);
        $this->project->offices()->attach($this->ict->office_id, ['participation_type' => 'primary']);
    }

    public function test_same_office_team_assignment_works(): void
    {
        $this->actingAs($this->director)
            ->post(route('projects.teams.assign', $this->project), ['team_id' => $this->ictTeam->team_id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_teams', [
            'project_id' => $this->project->project_id,
            'team_id' => $this->ictTeam->team_id,
        ]);
    }

    public function test_participating_office_team_assignment_works(): void
    {
        $this->project->offices()->syncWithoutDetaching([
            $this->finance->office_id => ['participation_type' => 'participating'],
        ]);

        $this->actingAs($this->director)
            ->post(route('projects.teams.assign', $this->project), ['team_id' => $this->financeTeam->team_id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_teams', [
            'project_id' => $this->project->project_id,
            'team_id' => $this->financeTeam->team_id,
        ]);
    }

    public function test_unrelated_office_team_assignment_is_rejected(): void
    {
        $this->actingAs($this->director)
            ->post(route('projects.teams.assign', $this->project), ['team_id' => $this->hrTeam->team_id])
            ->assertSessionHasErrors(['team_id']);

        $this->assertDatabaseMissing('project_teams', [
            'project_id' => $this->project->project_id,
            'team_id' => $this->hrTeam->team_id,
        ]);
    }

    public function test_manipulated_team_id_on_project_store_is_rejected(): void
    {
        $response = $this->actingAs($this->director)
            ->post(route('projects.store'), [
                'project_name' => 'Should Not Exist',
                'project_type' => 'Software',
                'primary_office_id' => $this->ict->office_id,
                'team_id' => $this->hrTeam->team_id,
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('projects', ['project_name' => 'Should Not Exist']);
    }
}
