<?php

namespace Tests\Feature;

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProgressLog;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAndTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_and_updates_project_and_task(): void
    {
        $this->seed(DatabaseSeeder::class);

        $project = Project::first();
        $this->assertNotNull($project);
        $this->assertEquals('Jimma University PMS', $project->project_name);
        $this->assertEquals('active', $project->status);

        $task = Task::first();
        $this->assertNotNull($task);
        $this->assertEquals('Requirements Gathering', $task->task_name);
        $this->assertEquals('In Progress', $task->status);
    }

    public function test_director_can_create_and_update_project(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $team = Team::first();

        $response = $this->actingAs($director)->post(route('projects.store'), [
            'project_name' => 'New ICT Portal',
            'description' => 'A new portal for university staff',
            'project_type' => 'Software',
            'team_id' => $team->team_id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'allocated_amount' => 200000,
        ]);

        $project = Project::where('project_name', 'New ICT Portal')->first();
        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.show', $project));

        $updateResponse = $this->actingAs($director)->put(route('projects.update', $project), [
            'project_name' => 'New ICT Portal Updated',
            'description' => 'Updated portal description',
            'project_type' => 'Software',
            'team_id' => $team->team_id,
            'status' => 'active',
            'allocated_amount' => 250000,
        ]);

        $updateResponse->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'project_id' => $project->project_id,
            'project_name' => 'New ICT Portal Updated',
            'status' => 'active',
        ]);
    }

    public function test_can_create_and_update_task(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $project = Project::first();
        $phase = Phase::where('project_id', $project->project_id)->first();

        $startDate = now()->toDateString();
        $endDate = now()->addDays(10)->toDateString();

        $response = $this->actingAs($director)->post(route('tasks.store'), [
            'phase_id' => $phase->phase_id,
            'task_name' => 'Architecture Design',
            'priority' => 'High',
            'status' => 'In Progress',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertSessionHasNoErrors();
        $task = Task::where('task_name', 'Architecture Design')->first();
        $this->assertNotNull($task);
        $this->assertEquals('In Progress', $task->status);
        $this->assertEquals($startDate, $task->start_date->toDateString());
        $this->assertEquals($endDate, $task->end_date->toDateString());

        $updateResponse = $this->actingAs($director)->put(route('tasks.update', $task), [
            'task_name' => 'Architecture Design (Final)',
            'priority' => 'Medium',
            'status' => 'Done',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'task_id' => $task->task_id,
            'task_name' => 'Architecture Design (Final)',
            'status' => 'Done',
        ]);
    }

    public function test_can_delete_task(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $task = Task::first();
        $this->assertNotNull($task);

        // Add a comment and progress log to ensure cascade deletion
        TaskComment::create([
            'task_id' => $task->task_id,
            'user_id' => $director->user_id,
            'comment_text' => 'Test comment before delete',
        ]);

        TaskProgressLog::create([
            'task_id' => $task->task_id,
            'user_id' => $director->user_id,
            'previous_status' => 'Pending',
            'new_status' => 'In Progress',
        ]);

        $response = $this->actingAs($director)->deleteJson(route('tasks.destroy', $task));
        $response->assertOk();
        $response->assertJson(['message' => 'Task deleted successfully']);

        $this->assertDatabaseMissing('tasks', [
            'task_id' => $task->task_id,
        ]);
        $this->assertDatabaseMissing('task_comments', [
            'task_id' => $task->task_id,
        ]);
        $this->assertDatabaseMissing('task_progress_logs', [
            'task_id' => $task->task_id,
        ]);
    }

    public function test_task_is_rendered_in_kanban_and_list_view_after_creation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $project = Project::first();
        $phase = Phase::where('project_id', $project->project_id)->first();

        $createResponse = $this->actingAs($director)->post(route('tasks.store'), [
            'phase_id' => $phase->phase_id,
            'task_name' => 'Verify Kanban and List View Task',
            'priority' => 'High',
            'status' => 'Pending',
            'assigned_to' => $director->user_id,
        ]);

        $createResponse->assertSessionHas('status', 'Task added successfully.');

        $showResponse = $this->actingAs($director)->get(route('projects.show', $project));
        $showResponse->assertOk();
        $showResponse->assertSee('Verify Kanban and List View Task');

        $tasksResponse = $this->actingAs($director)->get(route('tasks.index'));
        $tasksResponse->assertOk();
        $tasksResponse->assertSee('Verify Kanban and List View Task');
    }

    public function test_flash_messages_render_with_close_button_after_project_create_and_delete(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $team = Team::first();

        // 1. Create project
        $createResponse = $this->actingAs($director)->post(route('projects.store'), [
            'project_name' => 'Flash Test Project',
            'description' => 'Test project for flash alerts',
            'project_type' => 'Software',
            'team_id' => $team->team_id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'allocated_amount' => 100000,
        ]);

        $project = Project::where('project_name', 'Flash Test Project')->first();
        $this->assertNotNull($project);
        $createResponse->assertRedirect(route('projects.show', $project));

        $followCreate = $this->actingAs($director)->get(route('projects.show', $project));
        $followCreate->assertOk();
        $followCreate->assertSee('Project created.');
        $followCreate->assertSee('alert-close');

        // 2. Delete project
        $deleteResponse = $this->actingAs($director)->delete(route('projects.destroy', $project));
        $deleteResponse->assertRedirect(route('projects.index'));

        $followDelete = $this->actingAs($director)->get(route('projects.index'));
        $followDelete->assertOk();
        $followDelete->assertSee('was deleted.');
        $followDelete->assertSee('alert-close');
    }

    public function test_can_update_phase_status_and_add_phase(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $project = Project::first();
        $phase = $project->phases()->first();

        // Update phase status
        $statusResponse = $this->actingAs($director)->post(route('phases.status', $phase), [
            'status' => 'Done',
        ]);
        $statusResponse->assertSessionHasNoErrors();
        $this->assertEquals('Done', $phase->fresh()->status);

        // Add a new phase
        $addResponse = $this->actingAs($director)->post(route('phases.store', $project), [
            'phase_name' => 'Deployment & UAT',
            'status' => 'Not started',
        ]);
        $addResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('phases', [
            'project_id' => $project->project_id,
            'phase_name' => 'Deployment & UAT',
            'status' => 'Not started',
        ]);
    }

    public function test_can_update_task_phase(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = User::where('email', 'director@ju.edu.et')->first();
        $project = Project::first();
        $phases = $project->phases()->get();
        $phase1 = $phases[0];
        $phase2 = $phases[1];

        $task = Task::first();
        $task->update(['phase_id' => $phase1->phase_id]);

        $updateResponse = $this->actingAs($director)->put(route('tasks.update', $task), [
            'phase_id' => $phase2->phase_id,
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertEquals($phase2->phase_id, $task->fresh()->phase_id);
    }
}
