<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class ProjectTypeSeeder extends Seeder
{
    /**
     * Supplementary project types beyond the canonical ones seeded by
     * DatabaseSeeder::seedProjectTypes() (which owns the 2 global types and
     * the core office-scoped types).
     *
     * @var array<string, array{description: string, office: ?string}>
     *                                                                 office = office_name the type is scoped to; NULL = global (all offices).
     */
    public const DEFAULTS = [
        // Office-scoped types.
        'Network Infrastructure' => [
            'description' => 'Cabling, switching, wireless, and network capacity expansion work.',
            'office' => 'ICT Directorate',
        ],
        'System Maintenance' => [
            'description' => 'Upgrades, patching and preventive maintenance of existing systems.',
            'office' => 'ICT Directorate',
        ],
        'Financial Management Systems' => [
            'description' => 'Budgeting, accounting and financial reporting systems.',
            'office' => 'Finance Directorate',
        ],
        'Procurement Systems' => [
            'description' => 'Tendering, supplier and supplies management systems.',
            'office' => 'Procurement',
        ],
        'Student Services Platforms' => [
            'description' => 'Welfare, clearance and student-facing service platforms.',
            'office' => 'Student Services',
        ],
        'Records & Registry Systems' => [
            'description' => 'Document, records and registry management systems.',
            'office' => 'Registrar',
        ],

        // Legacy catalogue names kept as active types so historical projects
        // and old form payloads continue to validate.
        'Software' => [
            'description' => 'Legacy alias for general software projects.',
            'office' => 'ICT Directorate',
        ],
        'Training & Consultancy' => [
            'description' => 'Legacy alias for training and consultancy projects.',
            'office' => 'Human Resources',
        ],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $name => $def) {
            $officeId = $def['office']
                ? Office::where('office_name', $def['office'])->value('office_id')
                : null;

            ProjectType::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $def['description'],
                    'is_active' => true,
                    'office_id' => $officeId,
                ]
            );
        }

        /*
         * Backfill: map any legacy string values in projects.project_type
         * onto the matching catalog row so the FK column is never orphaned.
         */
        $types = ProjectType::pluck('project_type_id', 'name');

        $byLower = $types->mapWithKeys(
            fn ($id, $name) => [strtolower($name) => $id]
        );

        Project::whereNull('project_type_id')
            ->orderBy('project_id')
            ->chunkById(200, function ($projects) use ($types, $byLower) {
                foreach ($projects as $project) {
                    $legacy = trim((string) $project->project_type);

                    $project->project_type_id =
                        $types[$legacy] ?? $byLower[strtolower($legacy)] ?? null;

                    if (! is_null($project->project_type_id)) {
                        $project->save();
                    }
                }
            });
    }
}
