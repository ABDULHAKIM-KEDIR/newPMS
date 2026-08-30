<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\MentionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndCreateTask(): array
    {
        $this->seed(DatabaseSeeder::class);

        $author = User::where('email', 'director@ju.edu.et')->first();
        $project = Project::first();
        $phase = Phase::where('project_id', $project->project_id)->first();

        $this->actingAs($author)->post(route('tasks.store'), [
            'phase_id' => $phase->phase_id,
            'task_name' => 'Interaction Test Task',
            'status' => 'To Do',
            'priority' => 'Medium',
        ]);

        $task = Task::where('task_name', 'Interaction Test Task')->first();
        $this->assertNotNull($task);

        return [$author, $task];
    }

    public function test_status_update_via_post_returns_success_json_and_persists(): void
    {
        [$author, $task] = $this->seedAndCreateTask();

        $response = $this->actingAs($author)->postJson(route('tasks.status', $task), [
            'status' => 'In Progress',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Status updated');

        $this->assertDatabaseHas('tasks', [
            'task_id' => $task->task_id,
            'status' => 'In Progress',
        ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        [$author, $task] = $this->seedAndCreateTask();

        $this->actingAs($author)
            ->postJson(route('tasks.status', $task), ['status' => 'Bogus'])
            ->assertStatus(422);

        $this->assertDatabaseHas('tasks', [
            'task_id' => $task->task_id,
            'status' => 'To Do',
        ]);
    }

    public function test_mentioned_user_receives_unread_notification_with_task_link(): void
    {
        [$author, $task] = $this->seedAndCreateTask();

        $mentioned = User::create([
            'full_name' => 'Mentioned Person',
            'email' => 'mentioned.person@ju.edu.et',
            'password_hash' => bcrypt('secret'),
            'status' => 'Active',
            'role' => 'member',
        ]);

        $this->actingAs($author)->postJson(route('tasks.comments', $task), [
            'comment_text' => 'Pinging @mentioned-person to take a look please.',
        ])->assertOk();

        $notification = Notification::where('user_id', $mentioned->user_id)
            ->where('type', 'mention')
            ->first();

        $this->assertNotNull($notification);
        $this->assertFalse((bool) $notification->is_read);
        $this->assertStringContainsString('You were mentioned in a comment', $notification->message);
        $this->assertEquals(route('tasks.show', ['task' => $task->task_id]), $notification->link);
    }

    public function test_author_is_not_mention_notified_and_unknown_handles_are_ignored(): void
    {
        [$author, $task] = $this->seedAndCreateTask();

        $this->actingAs($author)->postJson(route('tasks.comments', $task), [
            'comment_text' => 'FYI @'.str_replace(' ', '-', strtolower($author->full_name)).' and @nobody-here',
        ])->assertOk();

        $this->assertSame(0, Notification::where('user_id', $author->user_id)->where('type', 'mention')->count());
        $this->assertSame(0, Notification::where('type', 'mention')->count());
    }

    public function test_mention_service_extracts_users_by_name_slug_and_email_prefix(): void
    {
        $author = User::create([
            'full_name' => 'Author Person', 'email' => 'author@ju.edu.et',
            'password_hash' => bcrypt('x'), 'status' => 'Active', 'role' => 'member',
        ]);
        $byName = User::create([
            'full_name' => 'Jane Doe', 'email' => 'jane.doe@ju.edu.et',
            'password_hash' => bcrypt('x'), 'status' => 'Active', 'role' => 'member',
        ]);
        $byEmail = User::create([
            'full_name' => 'Someone Else', 'email' => 'jwick@ju.edu.et',
            'password_hash' => bcrypt('x'), 'status' => 'Active', 'role' => 'member',
        ]);
        $inactive = User::create([
            'full_name' => 'Ghost User', 'email' => 'ghost@ju.edu.et',
            'password_hash' => bcrypt('x'), 'status' => 'Inactive', 'role' => 'member',
        ]);

        $found = app(MentionService::class)
            ->extractMentionedUsers('cc @jane-doe and @jwick, not @ghost-user', $author->user_id);

        $ids = array_map(fn ($u) => $u->user_id, $found);
        $this->assertContains($byName->user_id, $ids);
        $this->assertContains($byEmail->user_id, $ids);
        $this->assertNotContains($inactive->user_id, $ids);
        $this->assertNotContains($author->user_id, $ids);
    }
}
