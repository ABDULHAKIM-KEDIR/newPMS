<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class ProjectTypeSeeder extends Seeder
{
    /** Standard project types every installation starts with. */
    public const DEFAULTS = [
        'Software Development' => 'Application and platform build-outs, custom software and integrations.',
        'Network Infrastructure' => 'Cabling, switching, wireless, and network capacity expansion work.',
        'System Maintenance' => 'Upgrades, patching and preventive maintenance of existing systems.',
        'Hardware Procurement' => 'Purchase, rollout and lifecycle management of hardware assets.',
        'IT Support' => 'Helpdesk improvement, support tooling and end-user service initiatives.',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $name => $description) {
            ProjectType::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true]
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
