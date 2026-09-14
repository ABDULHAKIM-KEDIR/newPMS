<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

foreach (['offices', 'sub_teams', 'task_assignments', 'payments', 'tasks', 'users'] as $t) {
    echo $t.': '.(Schema::hasTable($t) ? 'yes' : 'no')."\n";
}
if (Schema::hasTable('offices')) {
    echo 'offices cols: '.implode(',', Schema::getColumnListing('offices'))."\n";
}
if (Schema::hasTable('payments')) {
    echo 'payments cols: '.implode(',', Schema::getColumnListing('payments'))."\n";
}
