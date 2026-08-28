<?php

use App\Models\Role;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sysAdmin = Role::where('role_name', 'System Administrator')->first();
echo 'System Administrator role id: '.$sysAdmin->role_id.PHP_EOL;
echo 'Permissions granted to role: '.$sysAdmin->permissions->count().PHP_EOL;
echo 'Grants: '.$sysAdmin->permissions->pluck('permission_name')->implode(', ').PHP_EOL;
