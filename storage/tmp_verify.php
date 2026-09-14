<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

$headA = User::where('email', 'va@t.io')->first();
$teamA = Team::where('team_name', 'Team A')->first();
$teamB = Team::where('team_name', 'Team B')->first();
$projectA = Project::where('project_name', 'Project A')->first();
$projectB = Project::where('project_name', 'Project B')->first();

echo 'own office project view: '.($headA->can('view', $projectA) ? 'YES' : 'NO')."\n";
echo 'other office project view: '.($headA->can('view', $projectB) ? 'YES (BAD)' : 'NO')."\n";
echo 'own office project update: '.($headA->can('update', $projectA) ? 'YES' : 'NO')."\n";
echo 'other office project update: '.($headA->can('update', $projectB) ? 'YES (BAD)' : 'NO')."\n";
echo 'own office team view: '.($headA->can('view', $teamA) ? 'YES' : 'NO')."\n";
echo 'other office team view: '.($headA->can('view', $teamB) ? 'YES (BAD)' : 'NO')."\n";
echo 'own office team update: '.($headA->can('update', $teamA) ? 'YES' : 'NO')."\n";
echo 'other office team update: '.($headA->can('update', $teamB) ? 'YES (BAD)' : 'NO')."\n";
echo 'nav view_projects (no context): '.($headA->can('view_projects') ? 'YES' : 'NO')."\n";
echo 'nav view_offices: '.($headA->can('view_offices') ? 'YES' : 'NO')."\n";
