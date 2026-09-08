<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo teams, one per office, led by that office's Team Lead and staffed
 * with its members.
 */
class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $offices = Office::whereIn('office_name', [
            'ICT Directorate',
            'Academic Affairs',
            'Finance Directorate',
            'Procurement',
            'Research',
            'Student Services',
        ])->get();

        foreach ($offices as $office) {
            $team = Team::updateOrCreate(
                ['team_name' => "{$office->office_name} Team"],
                [
                    'description' => "Delivery team for the {$office->office_name}.",
                    'status' => 'Active',
                    'office_id' => $office->office_id,
                ]
            );

            $staff = User::where('office_id', $office->office_id)
                ->where('status', 'Active')
                ->orderBy('user_id')
                ->get();

            $leader = $staff->firstWhere('email', 'like', 'lead.%')
                ?? $staff->firstWhere('email', 'like', 'pm.%')
                ?? $staff->first();

            $team->update(['team_leader_id' => $leader?->user_id]);

            foreach ($staff as $member) {
                $team->members()->updateOrCreate(
                    ['user_id' => $member->user_id],
                    ['joined_date' => now()->toDateString()]
                );
            }
        }
    }
}
