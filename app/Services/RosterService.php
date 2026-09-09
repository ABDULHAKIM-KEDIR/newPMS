<?php

namespace App\Services;

use App\Models\Project;

/**
 * Builds the presentation-ready project roster: PM, team leaders, team
 * members and specialist member-roles, de-duplicated by user.
 */
class RosterService
{
    /**
     * Structured roster of all participants on a project with their roles
     * and specialties.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getFormattedRoster(Project $project): \Illuminate\Support\Collection
    {
        $project->loadMissing(['projectManager', 'team.leader', 'team.members.user', 'teams.leader', 'teams.members.user', 'memberRoles.user', 'memberRoles.role']);

        $roster = collect();
        $seen = [];
        $memberRoleMap = $project->memberRoles->keyBy('user_id');

        // 1. Project Manager
        if ($project->projectManager) {
            $pm = $project->projectManager;
            $roster->push((object) [
                'user_id' => $pm->user_id,
                'user' => $pm,
                'full_name' => $pm->full_name,
                'email' => $pm->email,
                'team_name' => 'Project Management',
                'project_role' => 'Project Manager',
                'specialty' => 'Project Management & Delivery',
                'badge_class' => 'b-active',
                'is_pm' => true,
                'is_leader' => false,
                'member_role_id' => null,
            ]);
            $seen[$pm->user_id] = true;
        }

        // 2. Team Leaders & Members from all assigned teams
        foreach ($project->allTeams() as $assignedTeam) {
            if ($assignedTeam->leader && ! isset($seen[$assignedTeam->leader->user_id])) {
                $tl = $assignedTeam->leader;
                $mr = $memberRoleMap->get($tl->user_id);
                $specialty = ($mr && $mr->specialty) ? $mr->specialty : "{$assignedTeam->team_name} Lead";
                $projectRole = ($mr && $mr->role) ? $mr->role->role_name : 'Team Lead';

                $roster->push((object) [
                    'user_id' => $tl->user_id,
                    'user' => $tl,
                    'full_name' => $tl->full_name,
                    'email' => $tl->email,
                    'team_name' => $assignedTeam->team_name,
                    'project_role' => $projectRole,
                    'specialty' => $specialty,
                    'badge_class' => 'b-planning',
                    'is_pm' => false,
                    'is_leader' => true,
                    'member_role_id' => $mr ? $mr->id : null,
                ]);
                $seen[$tl->user_id] = true;
            }

            foreach ($assignedTeam->members as $tm) {
                if ($tm->user && ! isset($seen[$tm->user_id])) {
                    $u = $tm->user;
                    $mr = $memberRoleMap->get($u->user_id);
                    $specialty = ($mr && $mr->specialty) ? $mr->specialty : (optional(optional($u)->roles->first())->role_name ?: 'Team Member');
                    $projectRole = ($mr && $mr->role) ? $mr->role->role_name : ($mr && $mr->specialty ? $mr->specialty : 'Team Member');

                    $roster->push((object) [
                        'user_id' => $u->user_id,
                        'user' => $u,
                        'full_name' => $u->full_name,
                        'email' => $u->email,
                        'team_name' => $assignedTeam->team_name,
                        'project_role' => $projectRole,
                        'specialty' => $specialty,
                        'badge_class' => 'b-risk',
                        'is_pm' => false,
                        'is_leader' => false,
                        'member_role_id' => $mr ? $mr->id : null,
                    ]);
                    $seen[$u->user_id] = true;
                }
            }
        }

        // 3. Project Member Roles (Specialists)
        foreach ($project->memberRoles as $mr) {
            if ($mr->user && ! isset($seen[$mr->user_id])) {
                $u = $mr->user;
                $specialtyName = $mr->specialty ?: (optional($mr->role)->role_name ?: 'Team Member');
                $roster->push((object) [
                    'user_id' => $u->user_id,
                    'user' => $u,
                    'full_name' => $u->full_name,
                    'email' => $u->email,
                    'team_name' => 'Specialist',
                    'project_role' => optional($mr->role)->role_name ?: 'Specialist',
                    'specialty' => $specialtyName,
                    'badge_class' => 'b-planning',
                    'is_pm' => false,
                    'is_leader' => false,
                    'member_role_id' => $mr->id,
                ]);
                $seen[$u->user_id] = true;
            }
        }

        return $roster;
    }
}
