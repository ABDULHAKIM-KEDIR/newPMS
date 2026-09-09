<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Two sample records for every core entity so a fresh install has
 * something to look at. Safe to re-run: everything is firstOrCreate.
 */
class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('ChangeMe123!');

        // ---- Roles -----------------------------------------------------
        $adminRole = Role::where('role_name', 'Administrator')->firstOrFail();
        $pmRole = Role::where('role_name', 'Project Manager')->first()
            ?? Role::where('role_name', 'like', '%Manager%')->first();
        $memberRole = Role::where('role_name', 'Team Member')->first()
            ?? Role::where('role_name', 'like', '%Member%')->first();

        // ---- Users (2) ---------------------------------------------------
        $director = User::firstOrCreate(
            ['email' => 'director@example.com'],
            [
                'full_name' => 'Daniel Director',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'ICT Directorate',
                'office_id' => Office::where('office_name', 'like', '%ICT%')->value('office_id'),
            ]
        );
        if ($pmRole) {
            $director->roles()->syncWithoutDetaching([$pmRole->role_id]);
        }

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'full_name' => 'Sara Member',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'ICT Directorate',
                'office_id' => Office::where('office_name', 'like', '%ICT%')->value('office_id'),
            ]
        );
        if ($memberRole) {
            $member->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        // ---- Teams (2) ---------------------------------------------------
        $devTeam = Team::firstOrCreate(
            ['team_name' => 'Software Development Team'],
            ['team_leader_id' => $director->user_id, 'description' => 'Builds and maintains web applications.', 'status' => 'Active']
        );
        $netTeam = Team::firstOrCreate(
            ['team_name' => 'Network Infrastructure Team'],
            ['team_leader_id' => $director->user_id, 'description' => 'Handles cabling, switching and wireless.', 'status' => 'Active']
        );

        $devTeam->users()->syncWithoutDetaching([$director->user_id, $member->user_id]);
        $netTeam->users()->syncWithoutDetaching([$member->user_id]);

        // ---- Projects (2, categorized under offices) ---------------------
        $ictOfficeId = Office::where('office_name', 'like', '%ICT%')->value('office_id')
            ?? Office::orderBy('office_id')->value('office_id');
        $hrOfficeId = Office::where('office_name', 'like', '%Human%')->value('office_id')
            ?? $ictOfficeId;

        $softwareTypeId = ProjectType::where('name', 'Software Development')->value('project_type_id')
            ?? ProjectType::where('is_active', true)->value('project_type_id');
        $networkTypeId = ProjectType::where('name', 'Network Infrastructure')->value('project_type_id')
            ?? $softwareTypeId;

        $portal = Project::firstOrCreate(
            ['project_name' => 'Staff Self-Service Portal'],
            [
                'description' => 'Internal portal for leave requests, payslips and announcements.',
                'client' => 'Human Resources',
                'project_type' => 'Software Development',
                'project_type_id' => $softwareTypeId,
                'team_id' => $devTeam->team_id,
                'project_manager_id' => $director->user_id,
                'created_by' => $director->user_id,
                'priority' => 'High',
                'status' => 'active',
                'progress' => 35,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'primary_office_id' => $ictOfficeId,
            ]
        );
        $portal->offices()->syncWithoutDetaching([$ictOfficeId => ['participation_type' => 'primary']]);
        $portal->teams()->syncWithoutDetaching([$devTeam->team_id]);

        $campus = Project::firstOrCreate(
            ['project_name' => 'Campus Wi-Fi Expansion'],
            [
                'description' => 'Extend wireless coverage to dormitories and library.',
                'client' => 'ICT Directorate',
                'project_type' => 'Network Infrastructure',
                'project_type_id' => $networkTypeId,
                'team_id' => $netTeam->team_id,
                'project_manager_id' => $director->user_id,
                'created_by' => $director->user_id,
                'priority' => 'Medium',
                'status' => 'planning',
                'progress' => 10,
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addMonths(4)->toDateString(),
                'primary_office_id' => $ictOfficeId,
            ]
        );
        $campus->offices()->syncWithoutDetaching([$ictOfficeId => ['participation_type' => 'primary'], $hrOfficeId => ['participation_type' => 'participating']]);
        $campus->teams()->syncWithoutDetaching([$netTeam->team_id]);

        // ---- Phases (2 per project) ---------------------------------------
        foreach ([$portal, $campus] as $index => $project) {
            $planning = Phase::firstOrCreate(
                ['project_id' => $project->project_id, 'phase_name' => 'Planning'],
                [
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addWeeks(2)->toDateString(),
                    'duration' => 14,
                    'status' => 'In Progress',
                    'sequence_order' => 1,
                ]
            );
            $execution = Phase::firstOrCreate(
                ['project_id' => $project->project_id, 'phase_name' => 'Execution'],
                [
                    'start_date' => now()->addWeeks(3)->toDateString(),
                    'end_date' => now()->addMonths(2)->toDateString(),
                    'duration' => 45,
                    'status' => 'Not started',
                    'sequence_order' => 2,
                ]
            );

            // ---- Tasks (2 per project) ---------------------------------
            Task::firstOrCreate(
                ['project_id' => $project->project_id, 'task_name' => 'Gather requirements'],
                [
                    'phase_id' => $planning->phase_id,
                    'team_id' => $project->team_id,
                    'description' => 'Interview stakeholders and document requirements.',
                    'assigned_to' => $member->user_id,
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'progress' => 60,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addWeek()->toDateString(),
                ]
            );
            Task::firstOrCreate(
                ['project_id' => $project->project_id, 'task_name' => 'Prepare project charter'],
                [
                    'phase_id' => $execution->phase_id,
                    'team_id' => $project->team_id,
                    'description' => 'Draft the charter for sign-off.',
                    'assigned_to' => $director->user_id,
                    'status' => 'Pending',
                    'priority' => 'Medium',
                    'progress' => 0,
                    'start_date' => now()->addWeeks(3)->toDateString(),
                    'end_date' => now()->addWeeks(4)->toDateString(),
                ]
            );
        }

        $this->command?->info('Sample data created: 2 users, 2 teams, 2 projects, 4 phases, 4 tasks.');
    }
}
