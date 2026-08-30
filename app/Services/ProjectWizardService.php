<?php

namespace App\Services;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectMemberRole;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\Task;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles the multi-step project creation flow: project instantiation,
 * default phase & budget creation, roster assignment and initial task setup.
 */
class ProjectWizardService
{
    public const PHASES = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];

    /**
     * Create a project (with default phases, budgets, roster and initial tasks)
     * from a validated wizard request. The whole flow runs inside a single
     * database transaction so a failure in any step rolls everything back.
     */
    public function handleWizardSave(StoreProjectRequest $request): Project
    {
        /** @var User $user */
        $user = Auth::user();

        $pmInput = $request->input('project_manager_id') ?? $request->input('project_manager_name');
        $resolvedPmId = $this->resolveUserId($pmInput, $request->input('team_id'));

        $data = $request->validated();

        $selectedTeamIds = collect($request->input('team_ids', []))
            ->merge($request->input('teams', []))
            ->push($request->input('team_id'))
            ->filter()
            ->unique()
            ->values();

        $primaryTeamId = $selectedTeamIds->first() ?? $request->input('team_id');

        $project = DB::transaction(function () use ($user, $data, $resolvedPmId, $selectedTeamIds, $primaryTeamId) {
            $project = Project::create([
                'project_name' => $data['project_name'],
                'description' => $data['description'] ?? null,
                'client' => $data['client'] ?? null,
                'project_type' => $data['project_type'] ?? optional(ProjectType::find($data['project_type_id'] ?? null))->name ?? 'Software',
                'project_type_id' => $this->resolveProjectTypeId($data),
                'team_id' => $primaryTeamId,
                'project_manager_id' => $resolvedPmId,
                'priority' => $data['priority'] ?? 'Medium',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => 'planning',
                'progress' => 0,
                'created_by' => $user->user_id,
            ]);

            // Attach all selected teams in project_teams pivot
            if ($selectedTeamIds->isNotEmpty()) {
                foreach ($selectedTeamIds as $tid) {
                    DB::table('project_teams')->insertOrIgnore([
                        'project_id' => $project->project_id,
                        'team_id' => $tid,
                        'assigned_date' => now(),
                    ]);
                }
            }

            // Save flexible member assignments
            if (! empty($data['members']) && is_array($data['members'])) {
                $assignedUserIds = [];
                foreach ($data['members'] as $memberData) {
                    if (! empty($memberData['user_id']) && ! in_array($memberData['user_id'], $assignedUserIds)) {
                        $assignedUserIds[] = $memberData['user_id'];
                        ProjectMemberRole::create([
                            'project_id' => $project->project_id,
                            'user_id' => $memberData['user_id'],
                            'role_id' => $memberData['role_id'] ?? null,
                            'specialty' => $memberData['specialty'] ?? null,
                            'assigned_date' => now()->toDateString(),
                        ]);

                        if ((int) $memberData['user_id'] !== (int) $user->user_id) {
                            $roleName = ! empty($memberData['specialty']) ? " as {$memberData['specialty']}" : '';
                            Activity::notify((int) $memberData['user_id'], "You were assigned to \"{$project->project_name}\"{$roleName}", 'project');
                        }
                    }
                }
            }

            ProjectBudget::create([
                'project_id' => $project->project_id,
                'allocated_amount' => $data['allocated_amount'] ?? 0,
                'spent_amount' => 0,
                'currency' => 'ETB',
            ]);

            $firstPhase = null;
            foreach (self::PHASES as $i => $phaseName) {
                $phase = Phase::create([
                    'project_id' => $project->project_id,
                    'phase_name' => $phaseName,
                    'status' => $i === 0 ? 'In Progress' : 'Not started',
                    'sequence_order' => $i,
                ]);

                PhaseBudget::create([
                    'phase_id' => $phase->phase_id,
                    'allocated_amount' => round(($data['allocated_amount'] ?? 0) / 5),
                    'spent_amount' => 0,
                ]);

                $firstPhase = $firstPhase ?? $phase;
            }

            // Create initial tasks if provided in the wizard workflow
            if (! empty($data['tasks']) && is_array($data['tasks'])) {
                foreach ($data['tasks'] as $taskData) {
                    if (empty($taskData['task_name'])) {
                        continue;
                    }

                    $taskTeamId = ! empty($taskData['team_id']) ? (int) $taskData['team_id'] : $primaryTeamId;
                    $assigneeId = null;
                    if (! empty($taskData['assigned_to'])) {
                        $assigneeId = $this->resolveUserId($taskData['assigned_to'], $taskTeamId);
                    }

                    $status = $taskData['status'] ?? 'To Do';
                    if ($status === 'Pending') {
                        $status = 'To Do';
                    }

                    $task = Task::create([
                        'project_id' => $project->project_id,
                        'phase_id' => $firstPhase ? $firstPhase->phase_id : null,
                        'team_id' => $taskTeamId,
                        'task_name' => $taskData['task_name'],
                        'description' => $taskData['description'] ?? null,
                        'assigned_to' => $assigneeId,
                        'priority' => $taskData['priority'] ?? 'Medium',
                        'status' => $status,
                        'budget' => isset($taskData['budget']) ? (float) $taskData['budget'] : 0,
                        'start_date' => $data['start_date'] ?? now()->toDateString(),
                        'end_date' => $taskData['end_date'] ?? $data['end_date'] ?? null,
                        'progress' => in_array($status, ['Done', 'Completed']) ? 100 : 0,
                    ]);

                    if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->user_id) {
                        Activity::notify($task->assigned_to, "You have been assigned: \"{$task->task_name}\" on {$project->project_name}", 'task');
                    }
                }

                $project->recalculateProgress();
            }

            return $project;
        });

        Activity::log('Created project', 'Project', $project->project_id, $project->project_name);

        if ($project->project_manager_id && (int) $project->project_manager_id !== (int) $user->user_id) {
            Activity::notify((int) $project->project_manager_id, $user->full_name." assigned you as Project Manager for \"{$project->project_name}\"", 'project');
        }

        return $project;
    }

    /**
     * Prefers an explicit project_type_id; otherwise falls back to the
     * legacy free-text project_type string so old clients keep working.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolveProjectTypeId(array $data): ?int
    {
        if (! empty($data['project_type_id'])) {
            return (int) $data['project_type_id'];
        }

        $legacy = trim((string) ($data['project_type'] ?? ''));

        if ($legacy === '') {
            return null;
        }

        return ProjectType::whereRaw('lower(name) = ?', [strtolower($legacy)])
            ->value('project_type_id');
    }

    /**
     * Resolve a user ID or typed user name, creating a placeholder user
     * when the name is not found (legacy behaviour).
     */
    public function resolveUserId(string|int|null $input, ?int $teamId = null): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_numeric($input)) {
            $user = User::find((int) $input);
            if ($user) {
                return $user->user_id;
            }
        }

        $trimmed = trim((string) $input);
        if ($trimmed === '' || $trimmed === '— Select Project Manager —' || $trimmed === 'None') {
            return null;
        }

        $user = User::where('email', $trimmed)
            ->orWhere('full_name', $trimmed)
            ->orWhereRaw('LOWER(full_name) = ?', [strtolower($trimmed)])
            ->first();

        if ($user) {
            return $user->user_id;
        }

        $user = User::where('full_name', 'LIKE', "%{$trimmed}%")->first();
        if ($user) {
            return $user->user_id;
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $trimmed));
        $slug = trim($slug, '.');
        if (empty($slug)) {
            $slug = 'pm.'.rand(100, 999);
        }

        $email = $slug.'@ju.edu.et';
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = $slug.$counter.'@ju.edu.et';
            $counter++;
        }

        $newUser = User::create([
            'full_name' => $trimmed,
            'email' => $email,
            'password_hash' => bcrypt('ChangeMe123!'),
            'status' => 'Active',
        ]);

        $role = Role::where('role_name', 'Team Leader')->first() ?: Role::where('role_name', 'Team Member')->first();
        if ($role) {
            $newUser->roles()->attach($role->role_id);
        }

        if ($teamId) {
            TeamMember::firstOrCreate([
                'team_id' => $teamId,
                'user_id' => $newUser->user_id,
            ], [
                'joined_date' => now()->toDateString(),
            ]);
        }

        Activity::log('Created user for project leadership', 'User', $newUser->user_id, "{$newUser->full_name} ({$email})");

        return $newUser->user_id;
    }
}
