<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Team;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo project per ProjectType (global and office-scoped), with PMs,
 * primary + participating offices, teams and 3–5 tasks each.
 */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $offices = Office::pluck('office_id', 'office_name');
        $ict = $offices['ICT Directorate'] ?? null;
        $academic = $offices['Academic Affairs'] ?? null;
        $finance = $offices['Finance Directorate'] ?? null;
        $procurement = $offices['Procurement'] ?? null;
        $research = $offices['Research'] ?? null;
        $students = $offices['Student Services'] ?? null;

        $admin = User::where('email', 'admin@pms.test')->firstOrFail();

        // PM per office for assigning project managers.
        $pmByOffice = [
            $ict => User::where('email', 'pm.ict@pms.test')->first(),
            $academic => User::where('email', 'pm.academic@pms.test')->first(),
            $finance => User::where('email', 'pm.finance@pms.test')->first(),
            $procurement => User::where('email', 'lead.procurement@pms.test')->first(),
            $research => User::where('email', 'pm.research@pms.test')->first(),
            $students => User::where('email', 'lead.students@pms.test')->first(),
        ];

        // primary office per project type name.
        $primaryByType = [
            'Software Development' => $ict,
            'Network & Infrastructure' => $ict,
            'Network Infrastructure' => $ict,
            'System Maintenance' => $ict,
            'Software' => $ict,
            'Academic Systems' => $academic,
            'Enterprise Systems' => $finance,
            'Financial Management Systems' => $finance,
            'Procurement Systems' => $procurement,
            'Hardware Procurement' => $procurement,
            'Research & Development' => $research,
            'Student Services Tech' => $students,
            'Student Services Platforms' => $students,
            'Records & Registry Systems' => $academic,
            'Training & Consultancy' => $students,
            'IT Support' => $ict,
        ];

        $statusPool = ['Planning', 'Active', 'In Progress', 'Completed', 'On Hold'];
        $priorityPool = ['Low', 'Medium', 'High', 'Urgent'];
        $i = 0;

        foreach (ProjectType::where('is_active', true)->get() as $type) {
            $primary = $primaryByType[$type->name] ?? $ict;
            $pm = $pmByOffice[$primary] ?? $admin;

            // Team tied to the primary office, fallback to any team.
            $team = Team::where('office_id', $primary)->first() ?? Team::first();

            $start = now()->subDays(60 - ($i * 5));
            $status = $statusPool[$i % count($statusPool)];
            $progress = match ($status) {
                'Completed' => 100,
                'Planning' => 5,
                'On Hold' => 30,
                'In Progress' => 55,
                default => 40,
            };

            $project = Project::updateOrCreate(
                ['project_name' => "Demo: {$type->name} Initiative"],
                [
                    'description' => "Demo project exercising the {$type->name} project type. {$type->description}",
                    'client' => 'Internal',
                    'project_type' => $type->name,
                    'project_type_id' => $type->project_type_id,
                    'team_id' => $team?->team_id,
                    'project_manager_id' => $pm?->user_id,
                    'created_by' => $admin->user_id,
                    'scope_statement' => "Deliver the {$type->name} scope across participating offices this cycle.",
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->addDays(90)->toDateString(),
                    'status' => $status,
                    'priority' => $priorityPool[$i % count($priorityPool)],
                    'progress' => $progress,
                    'primary_office_id' => $primary,
                ]
            );

            // Cross-office participation: primary + two other offices.
            $participants = collect([$ict, $academic, $finance, $procurement, $research, $students])
                ->filter()
                ->reject(fn($id) => (int) $id === (int) $primary)
                ->values();

            $attached = [$primary => ['participation_type' => 'primary']];
            foreach ($participants->take(2) as $officeId) {
                $attached[$officeId] = ['participation_type' => 'participating'];
            }
            $project->offices()->sync($attached);

            // Assign the office team to the project (project_teams pivot).
            if ($team) {
                $project->teams()->syncWithoutDetaching([
                    $team->team_id => ['assigned_date' => now()->toDateString()],
                ]);
            }

            $this->seedTasks($project, $team, $pm, $status);
            $i++;
        }
    }

    private function seedTasks(Project $project, ?Team $team, ?User $pm, string $projectStatus): void
    {
        $assignees = User::where('status', 'Active')->orderBy('user_id')->pluck('user_id');
        $isDone = $projectStatus === 'Completed';

        $tasks = [
            ['Requirements gathering and stakeholder interviews', 'Pending'],
            ['Draft implementation plan and timeline', 'In Progress'],
            ['Procure necessary resources and tooling', 'Pending'],
            ['Execute core deliverables', $isDone ? 'Completed' : 'In Progress'],
            ['Testing, review and handover', $isDone ? 'Completed' : 'Pending'],
        ];

        $count = 3 + ($project->project_id % 3); // 3–5 tasks

        foreach (array_slice($tasks, 0, $count) as $idx => [$name, $status]) {
            if ($isDone) {
                $status = 'Completed';
            }

            $start = now()->subDays(30 - $idx * 7);

            Task::updateOrCreate(
                ['project_id' => $project->project_id, 'task_name' => $name],
                [
                    'team_id' => $team?->team_id,
                    'assigned_to' => $assignees[($project->project_id + $idx) % $assignees->count()] ?? $pm?->user_id,
                    'description' => "Task for {$project->project_name}: {$name}.",
                    'status' => $status,
                    'priority' => ['Low', 'Medium', 'High', 'Urgent'][$idx % 4],
                    'progress' => match ($status) {
                        'Completed' => 100,
                        'In Progress' => 50,
                        default => 0,
                    },
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->addDays(10)->toDateString(),
                    'duration' => 10,
                ]
            );
        }
    }
}
