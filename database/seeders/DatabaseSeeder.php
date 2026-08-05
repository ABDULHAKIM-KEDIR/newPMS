<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectDeliverable;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles & permissions ----
        $roleNames = ['ICT Director', 'Team Leader', 'Team Member', 'System Administrator'];
        $roles = collect($roleNames)->mapWithKeys(fn ($name) => [$name => Role::create([
            'role_name' => $name,
            'description' => "$name role",
        ])]);

        $permissionNames = [
            'Create / edit projects', 'Approve change requests', 'Manage budgets',
            'Assign tasks', 'Manage team membership', 'Manage users & roles', 'View audit log',
        ];
        $permissions = collect($permissionNames)->mapWithKeys(fn ($name) => [$name => Permission::create([
            'permission_name' => $name,
        ])]);

        $grants = [
            'ICT Director' => $permissionNames, // all
            'Team Leader' => ['Assign tasks', 'Manage team membership'],
            'Team Member' => [],
            'System Administrator' => ['Manage users & roles', 'View audit log'],
        ];
        foreach ($grants as $roleName => $perms) {
            foreach ($perms as $permName) {
                $roles[$roleName]->permissions()->attach($permissions[$permName]->permission_id);
            }
        }

        // ---- Users ----
        $userData = [
            ['Tariku Bekele', 'tariku.bekele@ju.edu.et', 'ICT Director'],
            ['Selam Girma', 'selam.girma@ju.edu.et', 'Team Leader'],
            ['Mekdes Yohannes', 'mekdes.yohannes@ju.edu.et', 'Team Leader'],
            ['Abel Kebede', 'abel.kebede@ju.edu.et', 'Team Leader'],
            ['Dawit Alemu', 'dawit.alemu@ju.edu.et', 'Team Member'],
            ['Rahel Tesfaye', 'rahel.tesfaye@ju.edu.et', 'Team Member'],
            ['Nahom Fikru', 'nahom.fikru@ju.edu.et', 'System Administrator'],
        ];
        $users = collect($userData)->mapWithKeys(function ($row) use ($roles) {
            [$name, $email, $roleName] = $row;
            $user = User::create([
                'full_name' => $name,
                'email' => $email,
                'password_hash' => Hash::make('password'),
                'phone' => '+2519' . rand(10000000, 99999999),
                'status' => 'Active',
            ]);
            $user->roles()->attach($roles[$roleName]->role_id);
            return [$name => $user];
        });

        // ---- Teams ----
        $teams = collect([
            ['Software Development', 'Selam Girma', 'Builds and maintains university-facing applications and internal systems.'],
            ['Network & Infrastructure', 'Mekdes Yohannes', 'Owns campus network, data center and connectivity projects.'],
            ['Training & Consultancy', 'Abel Kebede', 'Delivers digital literacy and systems training to staff and faculty.'],
        ])->mapWithKeys(function ($row) use ($users) {
            [$name, $leader, $desc] = $row;
            $team = Team::create([
                'team_name' => $name,
                'team_leader_id' => $users[$leader]->user_id,
                'description' => $desc,
            ]);
            return [$name => $team];
        });

        foreach ($users as $name => $user) {
            $team = match (true) {
                in_array($name, ['Selam Girma', 'Dawit Alemu', 'Rahel Tesfaye']) => $teams['Software Development'],
                in_array($name, ['Mekdes Yohannes', 'Nahom Fikru']) => $teams['Network & Infrastructure'],
                default => $teams['Training & Consultancy'],
            };
            TeamMember::create([
                'team_id' => $team->team_id,
                'user_id' => $user->user_id,
                'joined_date' => now()->subMonths(rand(2, 18)),
            ]);
        }

        // ---- Projects (+ phases + budgets) ----
        $phaseNames = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];

        $projectData = [
            ['Student Records Portal Revamp', 'Software', 'Software Development', 2, 850000, 519400, '2026-02-12', '2026-09-08', 'active'],
            ['Campus Wi-Fi Expansion — Block C', 'Network & Infrastructure', 'Network & Infrastructure', 1, 1200000, 210000, '2026-04-01', '2026-10-30', 'planning'],
            ['Staff Digital Literacy Training', 'Training & Consultancy', 'Training & Consultancy', 3, 180000, 151000, '2026-03-10', '2026-08-20', 'active'],
            ['ERP Data Migration Phase II', 'Software', 'Software Development', 2, 960000, 704000, '2026-01-20', '2026-08-14', 'risk'],
            ['Data Center UPS Upgrade', 'Network & Infrastructure', 'Network & Infrastructure', 4, 2100000, 2038000, '2025-11-05', '2026-07-01', 'closed'],
            ['e-Library Access Management', 'Software', 'Software Development', 0, 410000, 22000, '2026-06-01', '2026-12-12', 'planning'],
        ];

        foreach ($projectData as [$name, $type, $teamName, $currentPhase, $alloc, $spent, $start, $end, $status]) {
            $project = Project::create([
                'project_name' => $name,
                'description' => "$name — delivered by the $teamName team.",
                'project_type' => $type,
                'team_id' => $teams[$teamName]->team_id,
                'scope_statement' => "Approved scope for $name.",
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
                'created_by' => $users['Tariku Bekele']->user_id,
            ]);

            ProjectBudget::create([
                'project_id' => $project->project_id,
                'allocated_amount' => $alloc,
                'spent_amount' => $spent,
                'currency' => 'ETB',
            ]);

            $phaseIds = [];
            foreach ($phaseNames as $i => $phaseName) {
                $phaseStatus = $i < $currentPhase ? 'Done' : ($i === $currentPhase ? 'In Progress' : 'Not started');
                $phase = Phase::create([
                    'project_id' => $project->project_id,
                    'phase_name' => $phaseName,
                    'start_date' => now()->subMonths(6 - $i),
                    'end_date' => now()->subMonths(5 - $i),
                    'duration' => 30,
                    'status' => $phaseStatus,
                    'sequence_order' => $i,
                ]);
                $phaseIds[] = $phase->phase_id;

                PhaseBudget::create([
                    'phase_id' => $phase->phase_id,
                    'allocated_amount' => round($alloc / 5),
                    'spent_amount' => $i < $currentPhase ? round($alloc / 5 * 0.92) : ($i === $currentPhase ? round($alloc / 5 * 0.45) : 0),
                ]);
            }

            ProjectDeliverable::create([
                'project_id' => $project->project_id,
                'deliverable_name' => "$name — production build",
                'due_date' => $end,
                'status' => $status === 'closed' ? 'Delivered' : 'In progress',
            ]);

            ChangeRequest::create([
                'project_id' => $project->project_id,
                'requested_by' => $users['Selam Girma']->user_id,
                'description' => 'Extend go-live date to accommodate UAT feedback',
                'status' => 'Pending',
                'requested_date' => now()->subDays(4),
            ]);

            // A handful of tasks on the current (execution-ish) phase
            $executionPhaseId = $phaseIds[min($currentPhase, count($phaseIds) - 1)];
            $sampleTasks = [
                ['Integrate SSO with student portal', 'In Progress', 'High', 'Selam Girma'],
                ['Design responsive layout', 'Pending', 'Medium', 'Dawit Alemu'],
                ['Write unit tests', 'Done', 'Medium', 'Selam Girma'],
                ['Migrate legacy records', 'In Progress', 'High', 'Rahel Tesfaye'],
                ['Security review — auth endpoints', 'Pending', 'High', 'Nahom Fikru'],
            ];
            $firstTaskId = null;
            foreach ($sampleTasks as $i => [$taskName, $taskStatus, $priority, $assignee]) {
                $task = Task::create([
                    'phase_id' => $executionPhaseId,
                    'task_name' => "$taskName — $name",
                    'assigned_to' => $users[$assignee]->user_id,
                    'status' => $taskStatus,
                    'priority' => $priority,
                    'start_date' => now()->subDays(20 - $i * 2),
                    'end_date' => now()->addDays($i * 3),
                ]);
                if ($i === 0) $firstTaskId = $task->task_id;

                TaskComment::create([
                    'task_id' => $task->task_id,
                    'user_id' => $users['Nahom Fikru']->user_id,
                    'comment_text' => 'Flagged for a quick review before merge.',
                ]);
            }
        }

        // ---- Notifications (a few for each seeded user, so every login has something to see) ----
        foreach ($users as $name => $user) {
            Notification::insert([
                ['user_id' => $user->user_id, 'message' => '3 change requests are awaiting review', 'is_read' => false, 'created_at' => now()->subMinutes(10), 'type' => 'approval'],
                ['user_id' => $user->user_id, 'message' => 'You were mentioned in a task comment', 'is_read' => false, 'created_at' => now()->subHour(), 'type' => 'mention'],
                ['user_id' => $user->user_id, 'message' => 'Weekly budget summary is ready for review', 'is_read' => true, 'created_at' => now()->subDay(), 'type' => 'budget'],
            ]);
        }

        // ---- Audit log ----
        AuditLog::insert([
            ['user_id' => $users['Rahel Tesfaye']->user_id, 'action' => 'Updated task status', 'entity_type' => 'Task', 'entity_id' => 1, 'timestamp' => now()->subHours(2), 'details' => 'Pending -> In Progress'],
            ['user_id' => $users['Tariku Bekele']->user_id, 'action' => 'Approved change request', 'entity_type' => 'ChangeRequest', 'entity_id' => 1, 'timestamp' => now()->subHours(3), 'details' => 'Biometric login as secondary auth'],
            ['user_id' => null, 'action' => 'Budget threshold alert', 'entity_type' => 'Project', 'entity_id' => 4, 'timestamp' => now()->subDay(), 'details' => 'Crossed 70% utilisation'],
            ['user_id' => $users['Selam Girma']->user_id, 'action' => 'Created change request', 'entity_type' => 'Project', 'entity_id' => 1, 'timestamp' => now()->subDays(2), 'details' => 'Extend go-live by 3 weeks'],
        ]);
    }
}
