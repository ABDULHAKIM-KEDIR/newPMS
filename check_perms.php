<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo json_encode([
    'permissions' => App\Models\Permission::where('group', 'Administration')->pluck('permission_name')->all(),
    'sysadmin' => App\Models\Role::where('role_name', 'System Administrator')->with('permissions')->first()?->permissions->pluck('permission_name')->all(),
], JSON_PRETTY_PRINT), PHP_EOL;
