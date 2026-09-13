<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Sample records for core entities. Safe to re-run: everything is firstOrCreate.
 */
class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('ChangeMe123!');

        // ---- Roles -----------------------------------------------------
        $adminRole = Role::where('role_name', 'Administrator')->first();
        $pmRole = Role::where('role_name', 'Project Manager')->first() ?? $adminRole;
        $leadRole = Role::where('role_name', 'Team Lead')->first() ?? $pmRole;
        $memberRole = Role::where('role_name', 'Team Member')->first() ?? $leadRole;

        $ictOfficeId = Office::where('office_code', 'ICT')->value('office_id')
            ?? Office::where('office_name', 'like', '%ICT%')->value('office_id')
            ?? Office::orderBy('office_id')->value('office_id');
        $hrOfficeId = Office::where('office_code', 'HR')->value('office_id')
            ?? Office::where('office_name', 'like', '%Human%')->value('office_id')
            ?? $ictOfficeId;

        $softwareTypeId = ProjectType::where('name', 'Software Development')->value('project_type_id')
            ?? ProjectType::where('is_active', true)->value('project_type_id');
        $networkTypeId = ProjectType::where('name', 'Network Infrastructure')->value('project_type_id')
            ?? $softwareTypeId;

        // ---- Users -------------------------------------------------------
        $director = User::firstOrCreate(
            ['email' => 'director@example.com'],
            [
                'full_name' => 'Daniel Director',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'ICT Directorate',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($adminRole && $pmRole) {
            $director->roles()->syncWithoutDetaching([$adminRole->role_id, $pmRole->role_id]);
        }

        $johnSmith = User::firstOrCreate(
            ['email' => 'john.smith@example.com'],
            [
                'full_name' => 'John Smith',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Project Management Office',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($pmRole) {
            $johnSmith->roles()->syncWithoutDetaching([$pmRole->role_id]);
        }

        $abebe = User::firstOrCreate(
            ['email' => 'abebe@example.com'],
            [
                'full_name' => 'Abebe Bikila',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Network & Infrastructure',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($pmRole && $leadRole) {
            $abebe->roles()->syncWithoutDetaching([$pmRole->role_id, $leadRole->role_id]);
        }

        $kebede = User::firstOrCreate(
            ['email' => 'kebede@example.com'],
            [
                'full_name' => 'Kebede Michael',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Software Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($leadRole) {
            $kebede->roles()->syncWithoutDetaching([$leadRole->role_id]);
        }

        $chaltu = User::firstOrCreate(
            ['email' => 'chaltu@example.com'],
            [
                'full_name' => 'Chaltu Dibaba',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'UI/UX Design',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $chaltu->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $caala = User::firstOrCreate(
            ['email' => 'caala@example.com'],
            [
                'full_name' => 'Caala Banti',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Software Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $caala->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $mary = User::firstOrCreate(
            ['email' => 'mary@example.com'],
            [
                'full_name' => 'Mary Johnson',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'UI/UX Design',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($leadRole) {
            $mary->roles()->syncWithoutDetaching([$leadRole->role_id]);
        }

        $hana = User::firstOrCreate(
            ['email' => 'hana@example.com'],
            [
                'full_name' => 'Hana Girma',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'UI/UX Design',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $hana->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $sarah = User::firstOrCreate(
            ['email' => 'sarah@example.com'],
            [
                'full_name' => 'Sarah Connor',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Frontend Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($leadRole) {
            $sarah->roles()->syncWithoutDetaching([$leadRole->role_id]);
        }

        $john = User::firstOrCreate(
            ['email' => 'john@example.com'],
            [
                'full_name' => 'John Doe',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Frontend Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $john->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $david = User::firstOrCreate(
            ['email' => 'david@example.com'],
            [
                'full_name' => 'David Miller',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Frontend Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $david->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $ahmed = User::firstOrCreate(
            ['email' => 'ahmed@example.com'],
            [
                'full_name' => 'Ahmed Ali',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Backend Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($leadRole) {
            $ahmed->roles()->syncWithoutDetaching([$leadRole->role_id]);
        }

        $michael = User::firstOrCreate(
            ['email' => 'michael@example.com'],
            [
                'full_name' => 'Michael Brown',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Backend Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $michael->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $daniel = User::firstOrCreate(
            ['email' => 'daniel@example.com'],
            [
                'full_name' => 'Daniel Wilson',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Backend Engineering',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $daniel->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'full_name' => 'Sara Member',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'ICT Directorate',
                'office_id' => $ictOfficeId,
            ]
        );
        if ($memberRole) {
            $member->roles()->syncWithoutDetaching([$memberRole->role_id]);
        }

        // ---- Teams -------------------------------------------------------
        $seTeam = Team::firstOrCreate(
            ['team_name' => 'Software Engineering'],
            [
                'team_leader_id' => $kebede->user_id,
                'description' => 'Core university software development team.',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $seTeam->users()->syncWithoutDetaching([$kebede->user_id, $abebe->user_id, $chaltu->user_id, $caala->user_id, $john->user_id, $director->user_id]);

        $uiuxTeam = Team::firstOrCreate(
            ['team_name' => 'UI/UX Team'],
            [
                'team_leader_id' => $mary->user_id,
                'description' => 'User experience research, wireframing, UI prototyping, and design system creation',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $uiuxTeam->users()->syncWithoutDetaching([$mary->user_id, $hana->user_id, $chaltu->user_id]);

        $frontendTeam = Team::firstOrCreate(
            ['team_name' => 'Frontend Team'],
            [
                'team_leader_id' => $sarah->user_id,
                'description' => 'Modern client-side web and mobile user interfaces using Tailwind, Vue/React, and Blade',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $frontendTeam->users()->syncWithoutDetaching([$sarah->user_id, $john->user_id, $david->user_id]);

        $backendTeam = Team::firstOrCreate(
            ['team_name' => 'Backend Team'],
            [
                'team_leader_id' => $ahmed->user_id,
                'description' => 'High-performance microservices, RESTful APIs, database architecture, and payment gateways',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $backendTeam->users()->syncWithoutDetaching([$ahmed->user_id, $michael->user_id, $daniel->user_id]);

        $infraTeam = Team::firstOrCreate(
            ['team_name' => 'Network & Infrastructure'],
            [
                'team_leader_id' => $abebe->user_id,
                'description' => 'Campus networks, data centers, server infrastructure, and telecommunications',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $infraTeam->users()->syncWithoutDetaching([$abebe->user_id]);

        $devTeam = Team::firstOrCreate(
            ['team_name' => 'Software Development Team'],
            [
                'team_leader_id' => $director->user_id,
                'description' => 'Builds and maintains web applications.',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $devTeam->users()->syncWithoutDetaching([$director->user_id, $member->user_id]);

        $netTeam = Team::firstOrCreate(
            ['team_name' => 'Network Infrastructure Team'],
            [
                'team_leader_id' => $director->user_id,
                'description' => 'Handles cabling, switching and wireless.',
                'status' => 'Active',
                'office_id' => $ictOfficeId,
            ]
        );
        $netTeam->users()->syncWithoutDetaching([$member->user_id]);

        // ---- Project 1: Sample PMS (First record for ProjectAndTaskTest) ----
        $samplePms = Project::firstOrCreate(
            ['project_name' => 'Sample PMS'],
            [
                'description' => 'Enterprise Project Management System',
                'client' => 'Internal Directorate',
                'project_type' => 'Software',
                'project_type_id' => $softwareTypeId,
                'team_id' => $seTeam->team_id,
                'project_manager_id' => $abebe->user_id,
                'created_by' => $director->user_id,
                'priority' => 'High',
                'status' => 'active',
                'progress' => 40,
                'start_date' => now()->subDays(15)->toDateString(),
                'end_date' => now()->addMonths(2)->toDateString(),
                'primary_office_id' => $ictOfficeId,
            ]
        );
        $samplePms->offices()->syncWithoutDetaching([$ictOfficeId => ['participation_type' => 'primary']]);
        $samplePms->teams()->syncWithoutDetaching([$seTeam->team_id]);

        ProjectBudget::firstOrCreate(
            ['project_id' => $samplePms->project_id],
            ['allocated_amount' => 500000, 'spent_amount' => 150000, 'currency' => 'ETB']
        );

        $planningPhase = Phase::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'phase_name' => 'Planning'],
            [
                'start_date' => now()->subDays(15)->toDateString(),
                'end_date' => now()->addWeeks(2)->toDateString(),
                'duration' => 20,
                'status' => 'In Progress',
                'sequence_order' => 1,
            ]
        );
        PhaseBudget::firstOrCreate(
            ['phase_id' => $planningPhase->phase_id],
            ['allocated_amount' => 100000, 'spent_amount' => 30000]
        );

        $executionPhasePms = Phase::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'phase_name' => 'Execution'],
            [
                'start_date' => now()->addWeeks(2)->toDateString(),
                'end_date' => now()->addMonths(2)->toDateString(),
                'duration' => 45,
                'status' => 'Not started',
                'sequence_order' => 2,
            ]
        );
        PhaseBudget::firstOrCreate(
            ['phase_id' => $executionPhasePms->phase_id],
            ['allocated_amount' => 250000, 'spent_amount' => 0]
        );

        // First task in database: Requirements Gathering
        Task::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'task_name' => 'Requirements Gathering'],
            [
                'phase_id' => $planningPhase->phase_id,
                'team_id' => $seTeam->team_id,
                'description' => 'Collect and document user stories and functional requirements.',
                'assigned_to' => $abebe->user_id,
                'status' => 'In Progress',
                'priority' => 'High',
                'progress' => 50,
                'budget' => 25000,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
            ]
        );

        Task::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'task_name' => 'System Design'],
            [
                'phase_id' => $planningPhase->phase_id,
                'team_id' => $seTeam->team_id,
                'description' => 'Architecture diagrams and entity relationship schema design.',
                'assigned_to' => $john->user_id,
                'status' => 'Pending',
                'priority' => 'Medium',
                'progress' => 0,
                'budget' => 15000,
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(20)->toDateString(),
            ]
        );

        // ---- Project 2: E-Commerce Website (for ProjectManagementFullWorkflowTest) ----
        $ecommerce = Project::firstOrCreate(
            ['project_name' => 'E-Commerce Website'],
            [
                'description' => 'A scalable, modern e-commerce platform with omnichannel catalog, seamless shopping cart, and multi-gateway payment processing.',
                'client' => 'Global Retail Corporation',
                'project_type' => 'Software',
                'project_type_id' => $softwareTypeId,
                'team_id' => $frontendTeam->team_id,
                'project_manager_id' => $johnSmith->user_id,
                'priority' => 'High',
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->addDays(60)->toDateString(),
                'status' => 'active',
                'progress' => 65,
                'created_by' => $johnSmith->user_id,
                'primary_office_id' => $ictOfficeId,
            ]
        );
        $ecommerce->offices()->syncWithoutDetaching([$ictOfficeId => ['participation_type' => 'primary']]);
        foreach ([$uiuxTeam, $frontendTeam, $backendTeam] as $t) {
            DB::table('project_teams')->insertOrIgnore([
                'project_id' => $ecommerce->project_id,
                'team_id' => $t->team_id,
                'assigned_date' => now()->subDays(30),
            ]);
        }

        ProjectBudget::firstOrCreate(
            ['project_id' => $ecommerce->project_id],
            ['allocated_amount' => 850000, 'spent_amount' => 450000, 'currency' => 'ETB']
        );

        $phaseNames = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
        $ecomPhases = [];
        foreach ($phaseNames as $i => $pName) {
            $ecomPhases[$pName] = Phase::firstOrCreate(
                ['project_id' => $ecommerce->project_id, 'phase_name' => $pName],
                [
                    'status' => $i <= 1 ? 'Done' : ($i === 2 ? 'In Progress' : 'Not started'),
                    'sequence_order' => $i,
                ]
            );
            PhaseBudget::firstOrCreate(
                ['phase_id' => $ecomPhases[$pName]->phase_id],
                ['allocated_amount' => 170000, 'spent_amount' => $i <= 1 ? 170000 : ($i === 2 ? 110000 : 0)]
            );
        }
        $executionPhase = $ecomPhases['Execution'];

        // UI/UX Tasks
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Homepage Wireframe'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $uiuxTeam->team_id,
                'description' => 'Create low and high-fidelity wireframes for the homepage hero and product categories.',
                'assigned_to' => $mary->user_id,
                'priority' => 'High',
                'status' => 'Completed',
                'progress' => 100,
                'start_date' => now()->subDays(28)->toDateString(),
                'end_date' => now()->subDays(20)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Product Page Design'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $uiuxTeam->team_id,
                'description' => 'Design responsive layout for product detail views, image galleries, and customer reviews.',
                'assigned_to' => $hana->user_id,
                'priority' => 'High',
                'status' => 'Completed',
                'progress' => 100,
                'start_date' => now()->subDays(20)->toDateString(),
                'end_date' => now()->subDays(10)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Checkout Design'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $uiuxTeam->team_id,
                'description' => 'Finalize 3-step checkout user interface prototypes with address autocomplete and payment form.',
                'assigned_to' => $hana->user_id,
                'priority' => 'Medium',
                'status' => 'To Do',
                'progress' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(12)->toDateString(),
            ]
        );

        // Frontend Tasks
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Build Homepage'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $frontendTeam->team_id,
                'description' => 'Implement responsive landing page, banner carousel, and trending products showcase.',
                'assigned_to' => $john->user_id,
                'priority' => 'High',
                'status' => 'Completed',
                'progress' => 100,
                'start_date' => now()->subDays(18)->toDateString(),
                'end_date' => now()->subDays(5)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Build Product Listing'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $frontendTeam->team_id,
                'description' => 'Develop dynamic filtering, sorting, pagination, and grid/list view switcher.',
                'assigned_to' => $sarah->user_id,
                'priority' => 'High',
                'status' => 'In Progress',
                'progress' => 50,
                'start_date' => now()->subDays(8)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Build Shopping Cart'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $frontendTeam->team_id,
                'description' => 'Create reactive cart drawer with quantity adjustments, coupon discounts, and instant subtotal calculate.',
                'assigned_to' => $david->user_id,
                'priority' => 'Medium',
                'status' => 'To Do',
                'progress' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(15)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Build Checkout Interface'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $frontendTeam->team_id,
                'description' => 'Integrate multi-step checkout form with validation and payment confirmation screen.',
                'assigned_to' => $john->user_id,
                'priority' => 'High',
                'status' => 'To Do',
                'progress' => 0,
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(25)->toDateString(),
            ]
        );

        // Backend Tasks
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Create Authentication API'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $backendTeam->team_id,
                'description' => 'Implement OAuth2/Sanctum authentication, role-based tokens, password resets, and 2FA.',
                'assigned_to' => $ahmed->user_id,
                'priority' => 'High',
                'status' => 'Completed',
                'progress' => 100,
                'start_date' => now()->subDays(25)->toDateString(),
                'end_date' => now()->subDays(12)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Create Product API'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $backendTeam->team_id,
                'description' => 'Build catalog management endpoints with full-text search, inventory tracking, and caching.',
                'assigned_to' => $michael->user_id,
                'priority' => 'High',
                'status' => 'In Progress',
                'progress' => 70,
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->addDays(8)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Create Order API'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $backendTeam->team_id,
                'description' => 'Implement transactional order placement, invoice generation, and status management.',
                'assigned_to' => $daniel->user_id,
                'priority' => 'High',
                'status' => 'To Do',
                'progress' => 0,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(20)->toDateString(),
            ]
        );
        Task::firstOrCreate(
            ['project_id' => $ecommerce->project_id, 'task_name' => 'Create Payment API'],
            [
                'phase_id' => $executionPhase->phase_id,
                'team_id' => $backendTeam->team_id,
                'description' => 'Integrate Telebirr, CBE Birr, Stripe, and webhook handlers for instant transaction reconciliation.',
                'assigned_to' => $ahmed->user_id,
                'priority' => 'High',
                'status' => 'To Do',
                'progress' => 0,
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(28)->toDateString(),
            ]
        );

        // ---- Additional Projects for Office Demonstrations ----
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
    }
}
