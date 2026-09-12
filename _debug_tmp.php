<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$u = User::first();
echo $u->email, ' can-create: ', $u->can('create_projects') ? 'yes' : 'no', PHP_EOL;
