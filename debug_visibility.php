<?php

use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$heads = \App\Models\User::whereHas('roles', fn ($r) => $r->where('role_name', 'Head of Office'))->get();

foreach ($heads as $u) {
    echo "=== {$u->full_name} (user_id={$u->user_id}, office_id={$u->office_id}) ===\n";
    echo 'isOfficeHead: '.var_export($u->isOfficeHead(), true)."\n";
    echo 'headOfficeIds: '.$u->headOfficeIds()->implode(',')."\n";
    echo 'officeScopeIds: '.$u->officeScopeIds()->implode(',')."\n";
    echo 'isTeamMember: '.var_export($u->isTeamMember(), true)."\n";
    echo 'visibleTo projects: '.Project::visibleTo($u)->count()."\n";
    echo 'total projects: '.Project::count()."\n\n";

    foreach ($u->roles as $r) {
        echo "  role: {$r->role_name} | scope_type=".var_export($r->pivot->scope_type, true).' | scope_id='.var_export($r->pivot->scope_id, true)."\n";
    }
    echo "\n";
}

echo "=== Projects -> offices ===\n";
foreach (Project::with('offices')->take(10)->get() as $p) {
    echo "project {$p->project_id} '{$p->project_name}' | primary_office_id=".var_export($p->primary_office_id, true).' | offices='.$p->offices->pluck('office_id')->implode(',')."\n";
}
