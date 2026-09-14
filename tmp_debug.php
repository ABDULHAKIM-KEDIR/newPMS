<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Office;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Services\OrgHierarchyService;
use Database\Seeders\RbacSeeder;
use Illuminate\Contracts\Console\Kernel;

$app->make('Illuminate\Database\DatabaseManager')->beginTransaction();

Artisan::call('db:wipe');
Artisan::call('migrate');

(new RbacSeeder)->run();

$officeA = Office::create(['office_name' => 'Office A', 'office_code' => 'OA', 'status' => 'Active']);
$officeB = Office::create(['office_name' => 'Office B', 'office_code' => 'OB', 'status' => 'Active']);
$officeHead = User::create(['full_name' => 'Office Head', 'email' => 'office-head@test.com', 'password_hash' => bcrypt('password'), 'status' => 'Active', 'office_id' => $officeA->office_id]);
$role = Role::where('role_name', 'Head of Office')->first();
$officeHead->roles()->attach($role->role_id);
app(OrgHierarchyService::class)->assignHead($officeHead, $officeA);

dump($officeHead->roles()->get()->map(fn ($r) => ['name' => $r->role_name, 'pivot' => $r->pivot->toArray()])->all());
dump($officeHead->headOfficeIds()->all());
dump($officeHead->officeScopeIds()->all());

$visibleTeam = Team::create(['team_name' => 'Visible Team', 'office_id' => $officeA->office_id, 'status' => 'Active']);
$hiddenTeam = Team::create(['team_name' => 'Hidden Team', 'office_id' => $officeB->office_id, 'status' => 'Active']);

$visibleProject = Project::create([
    'project_name' => 'Visible office project',
    'project_type' => 'Software',
    'team_id' => $visibleTeam->team_id,
    'primary_office_id' => $officeA->office_id,
    'created_by' => $officeHead->user_id,
    'status' => 'active',
]);
$visibleProject->offices()->attach($officeA->office_id, ['participation_type' => 'primary']);

$hiddenProject = Project::create([
    'project_name' => 'Outside office project',
    'project_type' => 'Software',
    'team_id' => $hiddenTeam->team_id,
    'primary_office_id' => $officeB->office_id,
    'created_by' => $officeHead->user_id,
    'status' => 'active',
]);
$hiddenProject->offices()->attach($officeB->office_id, ['participation_type' => 'primary']);

dump(Team::query()->visibleTo($officeHead)->pluck('team_id')->all());
dump(Project::query()->visibleTo($officeHead)->pluck('project_id')->all());
dump(app(ProjectPolicy::class)->view($officeHead, $visibleProject));
dump(app(ProjectPolicy::class)->view($officeHead, $hiddenProject));
