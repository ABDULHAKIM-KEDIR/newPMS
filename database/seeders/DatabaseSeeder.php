<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles & permissions ----
        // The dynamic RBAC catalogue: permissions + default roles (with
        // inheritance and scopes) live in RbacSeeder, which also aliases
        // legacy role names onto canonical ones.
        $this->call(RbacSeeder::class);
        $this->call(OfficeSeeder::class);

        $this->seedProjectTypes();
        $this->call(ProjectTypeSeeder::class);

        $adminRole = Role::where('role_name', 'Administrator')->firstOrFail();

        // ---- The one bootstrap account ----
        // No demo users, teams, or projects — this is a clean install. The
        // System Administrator's job from here is exactly what their role
        // grants: create the first real users (via Users → + New User) and
        // assign someone an Administrator / Team Lead role so they can in
        // turn create teams and projects.
        $admin = User::create([
            'full_name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => null,
            'status' => 'Active',
        ]);
        $admin->roles()->attach($adminRole->role_id);

        // The bootstrap account belongs to no office (it oversees them all);
        // assign specific offices to users via Users → Edit.
        $admin->office_id = null;
        $admin->save();
    }

    /**
     * Deterministically seed the canonical project types with explicit
     * office associations. Two types are global (office_id = null) and
     * available to every office; the rest are scoped to a seeded office.
     */
    private function seedProjectTypes(): void
    {
        // Fetch office IDs dynamically after seeding offices.
        $ictOffice = Office::where('office_name', 'like', '%ICT%')->first();
        $academicOffice = Office::where('office_name', 'like', '%Academic%')->first();
        $financeOffice = Office::where('office_name', 'like', '%Finance%')
            ->orWhere('office_name', 'like', '%Procurement%')->first();
        $researchOffice = Office::where('office_name', 'like', '%Research%')->first();
        $studentOffice = Office::where('office_name', 'like', '%Student%')->first();

        $projectTypes = [
            // 2 GLOBAL PROJECT TYPES (office_id = null)
            [
                'name' => 'Hardware Procurement',
                'description' => 'Purchase, rollout and lifecycle management of hardware assets.',
                'office_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'IT Support',
                'description' => 'Helpdesk improvement, support tooling and end-user service initiatives.',
                'office_id' => null,
                'is_active' => true,
            ],

            // OFFICE-SPECIFIC PROJECT TYPES
            [
                'name' => 'Software Development',
                'description' => 'Application and platform build-outs, custom software and integrations.',
                'office_id' => $ictOffice?->office_id,
                'is_active' => true,
            ],
            [
                'name' => 'Network & Infrastructure',
                'description' => 'Cabling, switching, wireless, and network capacity expansion work.',
                'office_id' => $ictOffice?->office_id,
                'is_active' => true,
            ],
            [
                'name' => 'Academic Systems',
                'description' => 'Curriculum, grading, student record and academic workflow tools.',
                'office_id' => $academicOffice?->office_id,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Systems',
                'description' => 'Financial tracking, ERP, and procurement management platforms.',
                'office_id' => $financeOffice?->office_id,
                'is_active' => true,
            ],
            [
                'name' => 'Research & Development',
                'description' => 'Grants management, research portals, and institutional study systems.',
                'office_id' => $researchOffice?->office_id,
                'is_active' => true,
            ],
            [
                'name' => 'Student Services Tech',
                'description' => 'Student portal, housing, and campus life automation projects.',
                'office_id' => $studentOffice?->office_id,
                'is_active' => true,
            ],
        ];

        foreach ($projectTypes as $type) {
            ProjectType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
