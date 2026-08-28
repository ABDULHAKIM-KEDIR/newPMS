<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->can('view_projects'), 403);

        $teams = Team::with(['leader', 'members', 'projects'])->get();

        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        // Standing up a brand-new team is an org-structure change — reserved
        // for whoever holds manage_team AND the ICT Director role specifically.
        // A Team Leader has manage_team too, but only to run the team(s) they
        // already lead, not to create new ones.
        abort_unless($this->canCreateTeams(), 403);

        $users = User::orderBy('full_name')->get();

        return view('teams.create', compact('users'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreateTeams(), 403);

        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:100'],
            'team_leader_id' => ['nullable', 'exists:users,user_id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team = Team::create($data);

        if ($team->team_leader_id) {
            TeamMember::create(['team_id' => $team->team_id, 'user_id' => $team->team_leader_id, 'joined_date' => now()]);
        }

        Activity::log('Created team', 'Team', $team->team_id, $team->team_name);

        return redirect()->route('teams.show', $team)->with('status', 'Team created.');
    }

    public function show(Team $team)
    {
        abort_unless(Auth::user()->can('view_projects'), 403);

        $team->load([
            'leader', 'members.user.assignedTasks',
            'projects.budget', 'projects.phases.tasks',
            'assignedProjects.budget', 'assignedProjects.phases.tasks',
            'tasks.project', 'tasks.assignee', 'tasks.comments', 'tasks.attachments',
        ]);
        $user = Auth::user();
        $canManage = $this->canManageTeam($user, $team);

        $allProjects = $team->allProjects();
        $taskStats = $team->taskStats();
        $teamTasks = $team->tasks;

        // If tasks are attached directly or via projects
        if ($teamTasks->isEmpty()) {
            $teamTasks = $allProjects->flatMap(fn ($p) => $p->allTasks()->filter(fn ($t) => (int) $t->team_id === (int) $team->team_id));
        }

        $memberIds = $team->members->pluck('user_id');
        $availableUsers = $canManage
            ? User::whereNotIn('user_id', $memberIds)->where('status', 'Active')->orderBy('full_name')->get()
            : collect();

        $leaderCandidates = $canManage
            ? User::where('status', 'Active')->orderBy('full_name')->get()
            : collect();

        return view('teams.show', compact('team', 'canManage', 'availableUsers', 'leaderCandidates', 'allProjects', 'taskStats', 'teamTasks'));
    }

    public function addMember(Request $request, Team $team)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);

        $input = $request->input('user_id') ?? $request->input('user_name') ?? $request->input('name');
        $resolvedUserId = $this->resolveUserId($input, $team->team_id);

        if (! $resolvedUserId) {
            return back()->withErrors(['user_id' => 'Please provide a valid user name or select a member.']);
        }

        if (! $team->members()->where('user_id', $resolvedUserId)->exists()) {
            TeamMember::create(['team_id' => $team->team_id, 'user_id' => $resolvedUserId, 'joined_date' => now()]);
            $added = User::find($resolvedUserId);
            Activity::log('Added team member', 'Team', $team->team_id, "{$added->full_name} → {$team->team_name}");
            Activity::notify((int) $resolvedUserId, "You were added to the {$team->team_name} team", 'general');
        }

        $added = User::find($resolvedUserId);

        return back()->with('status', "Member \"{$added->full_name}\" added to {$team->team_name}.");
    }

    public function removeMember(Team $team, TeamMember $member)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);
        abort_unless($member->team_id === $team->team_id, 404);

        $removedName = optional($member->user)->full_name ?? 'A member';
        $member->delete();

        Activity::log('Removed team member', 'Team', $team->team_id, "{$removedName} left {$team->team_name}");

        return back()->with('status', 'Member removed.');
    }

    public function updateLeader(Request $request, Team $team)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);

        $input = $request->input('team_leader_id') ?? $request->input('team_leader_name') ?? $request->input('leader_name');
        $resolvedUserId = $this->resolveUserId($input, $team->team_id);

        if (! $resolvedUserId) {
            return back()->withErrors(['team_leader_id' => 'Please provide a valid leader name or select a member.']);
        }

        $oldLeader = optional($team->leader)->full_name ?? 'None';
        $newLeader = User::find($resolvedUserId);

        // A leader must be on the team — add them if they aren't already.
        if (! $team->members()->where('user_id', $resolvedUserId)->exists()) {
            TeamMember::create(['team_id' => $team->team_id, 'user_id' => $resolvedUserId, 'joined_date' => now()]);
        }

        $team->update(['team_leader_id' => $resolvedUserId]);

        Activity::log('Changed team leader', 'Team', $team->team_id, "{$oldLeader} → {$newLeader->full_name} ({$team->team_name})");
        Activity::notify((int) $resolvedUserId, "You are now the leader of the {$team->team_name} team", 'general');

        return back()->with('status', "Team leader changed to {$newLeader->full_name}.");
    }

    public function edit(Team $team)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);

        $users = User::orderBy('full_name')->get();

        return view('teams.edit', compact('team', 'users'));
    }

    public function update(Request $request, Team $team)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);

        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:100'],
            'team_leader_id' => ['nullable', 'exists:users,user_id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:Active,Inactive'],
        ]);

        $team->update([
            'team_name' => $data['team_name'],
            'team_leader_id' => $data['team_leader_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? $team->status,
        ]);

        // A leader must be a member of the team — add them if they aren't yet.
        if ($team->team_leader_id && ! $team->members()->where('user_id', $team->team_leader_id)->exists()) {
            TeamMember::create(['team_id' => $team->team_id, 'user_id' => $team->team_leader_id, 'joined_date' => now()]);
        }

        Activity::log('Updated team', 'Team', $team->team_id, $team->team_name);

        return redirect()->route('teams.show', $team)->with('status', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);

        $name = $team->team_name;

        // Detach projects first — projects.team_id cascades on delete, and we
        // don't want to destroy projects when their team is removed. Tasks
        // referencing this team are nulled automatically at the DB level.
        Project::where('team_id', $team->team_id)->update(['team_id' => null]);

        Activity::log('Deleted team', 'Team', $team->team_id, $name);
        $team->delete();

        return redirect()->route('teams.index')->with('status', "\"{$name}\" was deleted.");
    }

    private function resolveUserId($input, ?int $teamId = null): ?int
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
        if ($trimmed === '' || $trimmed === '— Select Member —' || $trimmed === '— Select Team Leader —' || $trimmed === 'None') {
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
            $slug = 'member.'.rand(100, 999);
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
            'department' => 'Staff',
        ]);

        $role = Role::where('role_name', 'Team Member')->first();
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

        Activity::log('Created team member', 'User', $newUser->user_id, "{$newUser->full_name} ({$email})");

        return $newUser->user_id;
    }

    private function canCreateTeams(): bool
    {
        $user = Auth::user();

        return $user->can('manage_team') || $user->isAdmin() || $user->isDirectorOrAdmin();
    }

    private function canManageTeam(User $user, Team $team): bool
    {
        if ($user->isAdmin() || $user->isDirectorOrAdmin()) {
            return true;
        }

        if (! $user->can('manage_team')) {
            return false;
        }

        return (int) $team->team_leader_id === (int) $user->user_id;
    }
}
