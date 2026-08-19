<?php

namespace App\Http\Controllers;

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

        $team->load(['leader', 'members.user', 'projects.budget']);
        $user = Auth::user();
        $canManage = $this->canManageTeam($user, $team);

        $memberIds = $team->members->pluck('user_id');
        $availableUsers = $canManage
            ? User::whereNotIn('user_id', $memberIds)->orderBy('full_name')->get()
            : collect();

        return view('teams.show', compact('team', 'canManage', 'availableUsers'));
    }

    public function addMember(Request $request, Team $team)
    {
        $user = Auth::user();
        abort_unless($this->canManageTeam($user, $team), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
        ]);

        if (! $team->members()->where('user_id', $data['user_id'])->exists()) {
            TeamMember::create(['team_id' => $team->team_id, 'user_id' => $data['user_id'], 'joined_date' => now()]);
            $added = User::find($data['user_id']);
            Activity::log('Added team member', 'Team', $team->team_id, "{$added->full_name} → {$team->team_name}");
            Activity::notify((int) $data['user_id'], "You were added to the {$team->team_name} team", 'general');
        }

        return back()->with('status', 'Member added.');
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

    private function canCreateTeams(): bool
    {
        $user = Auth::user();

        return $user->can('manage_team') && $user->hasRole('ICT Director');
    }

    private function canManageTeam(User $user, Team $team): bool
    {
        if (! $user->can('manage_team')) {
            return false;
        }

        return $user->hasRole('ICT Director') || $team->team_leader_id === $user->user_id;
    }
}
