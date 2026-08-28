<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectMemberRole;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles & permissions ----
        // The dynamic RBAC catalogue: permissions + default roles (with
        // inheritance and scopes) live in RbacSeeder, which also aliases
        // legacy directorate role names onto canonical ones.
        $this->call(RbacSeeder::class);
        $this->call(ProjectTypeSeeder::class);

        $roles = Role::whereIn('role_name', ['Administrator', 'Project Manager', 'Team Lead', 'Team Member'])
            ->get()->keyBy('role_name');

        // ---- The one bootstrap account ----
        // No demo users, teams, or projects — this is a clean install. The
        // System Administrator's job from here is exactly what their role
        // grants: create the first real users (via Users → + New User) and
        // assign someone an ICT Director / Team Leader role so they can in
        // turn create teams and projects.
        $admin = User::create([
            'full_name' => 'System Administrator',
            'email' => 'admin@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => null,
            'status' => 'Active',
        ]);
        $admin->roles()->attach($roles['Administrator']->role_id);

        // ---- Demo Accounts & Sample Data ----
        $director = User::create([
            'full_name' => 'ICT Director',
            'email' => 'director@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251911000000',
            'department' => 'ICT Directorate',
            'status' => 'Active',
        ]);
        $director->roles()->attach($roles['Administrator']->role_id);

        $leader = User::create([
            'full_name' => 'Team Leader',
            'email' => 'leader@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251912000000',
            'department' => 'Software Engineering',
            'status' => 'Active',
        ]);
        $leader->roles()->attach($roles['Team Lead']->role_id);

        // Project Manager - John Smith
        $johnSmith = User::create([
            'full_name' => 'John Smith',
            'email' => 'john.smith@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251911223344',
            'department' => 'Project Management Office',
            'status' => 'Active',
        ]);
        $johnSmith->roles()->attach($roles['Project Manager']->role_id);

        // UI/UX Team Members (Mary, Hana)
        $mary = User::create([
            'full_name' => 'Mary Johnson',
            'email' => 'mary@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251911556677',
            'department' => 'UI/UX Design',
            'status' => 'Active',
        ]);
        $mary->roles()->attach($roles['Team Lead']->role_id);

        $hana = User::create([
            'full_name' => 'Hana Girma',
            'email' => 'hana@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251911889900',
            'department' => 'UI/UX Design',
            'status' => 'Active',
        ]);
        $hana->roles()->attach($roles['Team Member']->role_id);

        // Frontend Team Members (Sarah, John Doe, David)
        $sarah = User::create([
            'full_name' => 'Sarah Connor',
            'email' => 'sarah@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251912112233',
            'department' => 'Frontend Engineering',
            'status' => 'Active',
        ]);
        $sarah->roles()->attach($roles['Team Lead']->role_id);

        $john = User::create([
            'full_name' => 'John Doe',
            'email' => 'john@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251955555555',
            'department' => 'Frontend Engineering',
            'status' => 'Active',
        ]);
        $john->roles()->attach($roles['Team Member']->role_id);

        $david = User::create([
            'full_name' => 'David Miller',
            'email' => 'david@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251912445566',
            'department' => 'Frontend Engineering',
            'status' => 'Active',
        ]);
        $david->roles()->attach($roles['Team Member']->role_id);

        // Backend Team Members (Ahmed, Michael, Daniel)
        $ahmed = User::create([
            'full_name' => 'Ahmed Ali',
            'email' => 'ahmed@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251913112233',
            'department' => 'Backend Engineering',
            'status' => 'Active',
        ]);
        $ahmed->roles()->attach($roles['Team Lead']->role_id);

        $michael = User::create([
            'full_name' => 'Michael Brown',
            'email' => 'michael@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251913445566',
            'department' => 'Backend Engineering',
            'status' => 'Active',
        ]);
        $michael->roles()->attach($roles['Team Member']->role_id);

        $daniel = User::create([
            'full_name' => 'Daniel Wilson',
            'email' => 'daniel@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251913778899',
            'department' => 'Backend Engineering',
            'status' => 'Active',
        ]);
        $daniel->roles()->attach($roles['Team Member']->role_id);

        // Professional Staff & Project Members (Abebe, Kebede, Chaltu, Caala)
        $abebe = User::create([
            'full_name' => 'Abebe Bikila',
            'email' => 'abebe@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251911111111',
            'department' => 'Network & Infrastructure',
            'status' => 'Active',
        ]);
        $abebe->roles()->attach($roles['Team Lead']->role_id);

        $kebede = User::create([
            'full_name' => 'Kebede Michael',
            'email' => 'kebede@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251922222222',
            'department' => 'Software Engineering',
            'status' => 'Active',
        ]);
        $kebede->roles()->attach($roles['Team Lead']->role_id);

        $chaltu = User::create([
            'full_name' => 'Chaltu Dibaba',
            'email' => 'chaltu@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251933333333',
            'department' => 'UI/UX Design',
            'status' => 'Active',
        ]);
        $chaltu->roles()->attach($roles['Team Member']->role_id);

        $caala = User::create([
            'full_name' => 'Caala Banti',
            'email' => 'caala@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251944444444',
            'department' => 'Software Engineering',
            'status' => 'Active',
        ]);
        $caala->roles()->attach($roles['Team Member']->role_id);

        // ---- Teams ----
        // 1. UI/UX Team
        $uiuxTeam = Team::create([
            'team_name' => 'UI/UX Team',
            'description' => 'User experience research, wireframing, UI prototyping, and design system creation',
            'team_leader_id' => $mary->user_id,
            'status' => 'Active',
        ]);
        foreach ([$mary, $hana, $chaltu] as $tm) {
            TeamMember::create(['team_id' => $uiuxTeam->team_id, 'user_id' => $tm->user_id, 'joined_date' => now()->toDateString()]);
        }

        // 2. Frontend Team
        $frontendTeam = Team::create([
            'team_name' => 'Frontend Team',
            'description' => 'Modern client-side web and mobile user interfaces using Tailwind, Vue/React, and Blade',
            'team_leader_id' => $sarah->user_id,
            'status' => 'Active',
        ]);
        foreach ([$sarah, $john, $david] as $tm) {
            TeamMember::create(['team_id' => $frontendTeam->team_id, 'user_id' => $tm->user_id, 'joined_date' => now()->toDateString()]);
        }

        // 3. Backend Team
        $backendTeam = Team::create([
            'team_name' => 'Backend Team',
            'description' => 'High-performance microservices, RESTful APIs, database architecture, and payment gateways',
            'team_leader_id' => $ahmed->user_id,
            'status' => 'Active',
        ]);
        foreach ([$ahmed, $michael, $daniel] as $tm) {
            TeamMember::create(['team_id' => $backendTeam->team_id, 'user_id' => $tm->user_id, 'joined_date' => now()->toDateString()]);
        }

        // 4. Software Engineering Team
        $team = Team::create([
            'team_name' => 'Software Engineering',
            'description' => 'Core university software development team',
            'team_leader_id' => $kebede->user_id,
            'status' => 'Active',
        ]);
        foreach ([$kebede, $abebe, $chaltu, $caala, $john, $leader] as $tm) {
            TeamMember::create(['team_id' => $team->team_id, 'user_id' => $tm->user_id, 'joined_date' => now()->toDateString()]);
        }

        // 5. Network & Infrastructure
        $infraTeam = Team::create([
            'team_name' => 'Network & Infrastructure',
            'description' => 'Campus networks, data centers, server administration, and cloud infrastructure',
            'team_leader_id' => $abebe->user_id,
            'status' => 'Active',
        ]);
        foreach ([$abebe, $caala, $john] as $tm) {
            TeamMember::create(['team_id' => $infraTeam->team_id, 'user_id' => $tm->user_id, 'joined_date' => now()->toDateString()]);
        }

        // ---- Project 1: Jimma University PMS ----
        $project = Project::create([
            'project_name' => 'Jimma University PMS',
            'description' => 'Development of ICT Project Management System with flexible team allocation',
            'client' => 'Jimma University ICT Directorate',
            'project_type' => 'Software',
            'team_id' => $team->team_id,
            'project_manager_id' => $abebe->user_id,
            'priority' => 'High',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
            'progress' => 45,
            'created_by' => $director->user_id,
        ]);

        DB::table('project_teams')->insertOrIgnore([
            'project_id' => $project->project_id,
            'team_id' => $team->team_id,
            'assigned_date' => now(),
        ]);

        // Flexible Project Member Roles
        ProjectMemberRole::create([
            'project_id' => $project->project_id,
            'user_id' => $chaltu->user_id,
            'specialty' => 'UI/UX Designer',
            'assigned_date' => now()->toDateString(),
        ]);
        ProjectMemberRole::create([
            'project_id' => $project->project_id,
            'user_id' => $caala->user_id,
            'specialty' => 'Backend Developer',
            'assigned_date' => now()->toDateString(),
        ]);
        ProjectMemberRole::create([
            'project_id' => $project->project_id,
            'user_id' => $john->user_id,
            'specialty' => 'QA / Test Engineer',
            'assigned_date' => now()->toDateString(),
        ]);
        ProjectMemberRole::create([
            'project_id' => $project->project_id,
            'user_id' => $kebede->user_id,
            'specialty' => 'Technical Lead',
            'assigned_date' => now()->toDateString(),
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'allocated_amount' => 500000,
            'spent_amount' => 120000,
            'currency' => 'ETB',
        ]);

        $phaseNames = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
        $phases = [];

        foreach ($phaseNames as $i => $phaseName) {
            $phase = Phase::create([
                'project_id' => $project->project_id,
                'phase_name' => $phaseName,
                'status' => $i === 0 ? 'In Progress' : 'Not started',
                'sequence_order' => $i,
            ]);

            PhaseBudget::create([
                'phase_id' => $phase->phase_id,
                'allocated_amount' => 100000,
                'spent_amount' => $i === 0 ? 30000 : 0,
            ]);

            $phases[$phaseName] = $phase;
        }

        // Project 1 Tasks
        Task::create([
            'project_id' => $project->project_id,
            'phase_id' => $phases['Initiation']->phase_id,
            'team_id' => $team->team_id,
            'task_name' => 'Requirements Gathering',
            'description' => 'Gather initial system requirements and finalize SRS specification',
            'assigned_to' => $kebede->user_id,
            'status' => 'In Progress',
            'priority' => 'High',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
        ]);

        Task::create([
            'project_id' => $project->project_id,
            'phase_id' => $phases['Initiation']->phase_id,
            'team_id' => $team->team_id,
            'task_name' => 'UI/UX Design Mockups & Design System',
            'description' => 'Design wireframes and interactive prototypes for PMS dashboard',
            'assigned_to' => $chaltu->user_id,
            'status' => 'Completed',
            'priority' => 'High',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
        ]);

        Task::create([
            'project_id' => $project->project_id,
            'phase_id' => $phases['Initiation']->phase_id,
            'team_id' => $team->team_id,
            'task_name' => 'Database Schema & Core Models Implementation',
            'description' => 'Design and migrate the database schema for projects, tasks, and users',
            'assigned_to' => $caala->user_id,
            'status' => 'In Progress',
            'priority' => 'Medium',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
        ]);

        Task::create([
            'project_id' => $project->project_id,
            'phase_id' => $phases['Initiation']->phase_id,
            'team_id' => $team->team_id,
            'task_name' => 'QA Test Plan & Test Case Suite',
            'description' => 'Prepare functional and integration test matrix',
            'assigned_to' => $john->user_id,
            'status' => 'To Do',
            'priority' => 'Medium',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
        ]);

        // ==========================================
        // PROJECT: E-Commerce Website (Primary Demonstration Project)
        // ==========================================
        $ecommerce = Project::create([
            'project_name' => 'E-Commerce Website',
            'description' => 'A scalable, modern e-commerce platform with omnichannel catalog, seamless shopping cart, and multi-gateway payment processing.',
            'client' => 'Global Retail Corporation',
            'project_type' => 'Software',
            'team_id' => $frontendTeam->team_id,
            'project_manager_id' => $johnSmith->user_id,
            'priority' => 'High',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'status' => 'active',
            'progress' => 65,
            'created_by' => $johnSmith->user_id,
        ]);

        // Assign all 3 teams to E-Commerce Website
        foreach ([$uiuxTeam, $frontendTeam, $backendTeam] as $t) {
            DB::table('project_teams')->insertOrIgnore([
                'project_id' => $ecommerce->project_id,
                'team_id' => $t->team_id,
                'assigned_date' => now()->subDays(30),
            ]);
        }

        ProjectBudget::create([
            'project_id' => $ecommerce->project_id,
            'allocated_amount' => 850000,
            'spent_amount' => 450000,
            'currency' => 'ETB',
        ]);

        $phaseNames = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
        $ecomPhases = [];
        foreach ($phaseNames as $i => $pName) {
            $ecomPhases[$pName] = Phase::create([
                'project_id' => $ecommerce->project_id,
                'phase_name' => $pName,
                'status' => $i <= 1 ? 'Done' : ($i === 2 ? 'In Progress' : 'Not started'),
                'sequence_order' => $i,
            ]);

            PhaseBudget::create([
                'phase_id' => $ecomPhases[$pName]->phase_id,
                'allocated_amount' => 170000,
                'spent_amount' => $i <= 1 ? 170000 : ($i === 2 ? 110000 : 0),
            ]);
        }

        $executionPhase = $ecomPhases['Execution'];

        // UI/UX Team Tasks
        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $uiuxTeam->team_id,
            'task_name' => 'Homepage Wireframe',
            'description' => 'Create low and high-fidelity wireframes for the homepage hero and product categories.',
            'assigned_to' => $mary->user_id,
            'priority' => 'High',
            'status' => 'Completed',
            'progress' => 100,
            'start_date' => now()->subDays(28)->toDateString(),
            'end_date' => now()->subDays(20)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $uiuxTeam->team_id,
            'task_name' => 'Product Page Design',
            'description' => 'Design responsive layout for product detail views, image galleries, and customer reviews.',
            'assigned_to' => $hana->user_id,
            'priority' => 'High',
            'status' => 'Completed',
            'progress' => 100,
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $uiuxTeam->team_id,
            'task_name' => 'Checkout Design',
            'description' => 'Finalize 3-step checkout user interface prototypes with address autocomplete and payment form.',
            'assigned_to' => $hana->user_id,
            'priority' => 'Medium',
            'status' => 'To Do',
            'progress' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
        ]);

        // Frontend Team Tasks
        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $frontendTeam->team_id,
            'task_name' => 'Build Homepage',
            'description' => 'Implement responsive landing page, banner carousel, and trending products showcase.',
            'assigned_to' => $john->user_id,
            'priority' => 'High',
            'status' => 'Completed',
            'progress' => 100,
            'start_date' => now()->subDays(18)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $frontendTeam->team_id,
            'task_name' => 'Build Product Listing',
            'description' => 'Develop dynamic filtering, sorting, pagination, and grid/list view switcher.',
            'assigned_to' => $sarah->user_id,
            'priority' => 'High',
            'status' => 'In Progress',
            'progress' => 50,
            'start_date' => now()->subDays(8)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $frontendTeam->team_id,
            'task_name' => 'Build Shopping Cart',
            'description' => 'Create reactive cart drawer with quantity adjustments, coupon discounts, and instant subtotal calculate.',
            'assigned_to' => $david->user_id,
            'priority' => 'Medium',
            'status' => 'To Do',
            'progress' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $frontendTeam->team_id,
            'task_name' => 'Build Checkout Interface',
            'description' => 'Integrate multi-step checkout form with validation and payment confirmation screen.',
            'assigned_to' => $john->user_id,
            'priority' => 'High',
            'status' => 'To Do',
            'progress' => 0,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
        ]);

        // Backend Team Tasks
        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $backendTeam->team_id,
            'task_name' => 'Create Authentication API',
            'description' => 'Implement OAuth2/Sanctum authentication, role-based tokens, password resets, and 2FA.',
            'assigned_to' => $ahmed->user_id,
            'priority' => 'High',
            'status' => 'Completed',
            'progress' => 100,
            'start_date' => now()->subDays(25)->toDateString(),
            'end_date' => now()->subDays(12)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $backendTeam->team_id,
            'task_name' => 'Create Product API',
            'description' => 'Build catalog management endpoints with full-text search, inventory tracking, and caching.',
            'assigned_to' => $michael->user_id,
            'priority' => 'High',
            'status' => 'In Progress',
            'progress' => 70,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $backendTeam->team_id,
            'task_name' => 'Create Order API',
            'description' => 'Implement transactional order placement, invoice generation, and status management.',
            'assigned_to' => $daniel->user_id,
            'priority' => 'High',
            'status' => 'To Do',
            'progress' => 0,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
        ]);

        Task::create([
            'project_id' => $ecommerce->project_id,
            'phase_id' => $executionPhase->phase_id,
            'team_id' => $backendTeam->team_id,
            'task_name' => 'Create Payment API',
            'description' => 'Integrate Telebirr, CBE Birr, Stripe, and webhook handlers for instant transaction reconciliation.',
            'assigned_to' => $ahmed->user_id,
            'priority' => 'High',
            'status' => 'To Do',
            'progress' => 0,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(28)->toDateString(),
        ]);

        $ecommerce->recalculateProgress();
    }
}
