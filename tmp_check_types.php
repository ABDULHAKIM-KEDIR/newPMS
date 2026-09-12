<?php
foreach (App\Models\ProjectType::with('office')->orderBy('name')->get() as $t) {
    echo $t->name . ' => ' . ($t->office?->office_name ?? 'GLOBAL') . PHP_EOL;
}
