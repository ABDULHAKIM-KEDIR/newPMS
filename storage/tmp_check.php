<?php

use App\Models\Office;
use App\Models\Project;
use App\Models\User;

echo 'offices 1-5:'.PHP_EOL;
foreach (Office::whereIn('office_id', [1, 2, 3, 4, 5])->get(['office_id', 'office_name', 'parent_office_id']) as $o) {
    echo '  '.$o->office_id.' '.$o->office_name.' (parent: '.($o->parent_office_id ?? 'null').')'.PHP_EOL;
}

$head = User::whereHas('roles', fn ($q) => $q->where('role_name', 'Head of Office'))->first();

// Exactly what the projects index page runs:
$visible = Project::query()->visibleTo($head)->get(['project_id', 'project_name', 'primary_office_id']);
echo PHP_EOL.'projects index shows for head: '.$visible->count().PHP_EOL;
foreach ($visible->groupBy('primary_office_id') as $officeId => $group) {
    echo '  office '.$officeId.': '.$group->count().' projects'.PHP_EOL;
}
