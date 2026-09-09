<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * Idempotent default offices — safe to re-run (no duplicates thanks to
 * firstOrCreate). Existing seeded users can be assigned to offices here
 * once those accounts exist.
 */
class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $offices = [
            ['office_name' => 'ICT Directorate', 'office_code' => 'ICT', 'description' => 'Information and Communication Technology'],
            ['office_name' => 'Human Resources', 'office_code' => 'HR', 'description' => 'Human Resource Management'],
            ['office_name' => 'Finance Directorate', 'office_code' => 'FIN', 'description' => 'Finance and Accounting'],
            ['office_name' => 'Procurement', 'office_code' => 'PROC', 'description' => 'Procurement and Supplies'],
            ['office_name' => 'Planning', 'office_code' => 'PLAN', 'description' => 'Planning and Development'],
            ['office_name' => 'Academic Affairs', 'office_code' => 'ACAD', 'description' => 'Academic Affairs'],
            ['office_name' => 'Research', 'office_code' => 'RES', 'description' => 'Research and Publication'],
            ['office_name' => 'Student Services', 'office_code' => 'STU', 'description' => 'Student Services'],
            ['office_name' => 'Registrar', 'office_code' => 'REG', 'description' => 'Office of the Registrar'],
        ];

        foreach ($offices as $office) {
            Office::firstOrCreate(
                ['office_code' => $office['office_code']],
                $office
            );
        }
    }
}
