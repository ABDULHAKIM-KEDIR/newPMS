<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::where('email', 'admin@ju.edu.et')->first();
$role = Role::where('role_name', 'System Administrator')->first();

// Exact same linkage the DatabaseSeeder performs for the bootstrap account.
$user->roles()->syncWithoutDetaching([$role->role_id]);

echo 'User: '.$user->email.PHP_EOL;
echo 'Assigned roles: '.$user->roles->pluck('role_name')->implode(', ').PHP_EOL;
echo 'has view_projects: '.($user->hasPermission('view_projects') ? 'YES' : 'NO').PHP_EOL;
echo 'has manage_users: '.($user->hasPermission('manage_users') ? 'YES' : 'NO').PHP_EOL;
