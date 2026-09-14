define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$u = App\Models\User::find(41);
echo 'name='.$u->name.PHP_EOL;
echo 'office_id='.var_export($u->office_id, true).PHP_EOL;
echo 'headOfficeIds='.json_encode($u->headOfficeIds()).PHP_EOL;
foreach(DB::table('model_has_roles')->where('model_id',41)->get() as $r){
  echo 'role_id='.$r->role_id.' scope_type='.var_export($r->scope_type,true).' scope_id='.var_export($r->scope_id,true).PHP_EOL;
}
<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(41);
echo 'name='.$u->name.PHP_EOL;
echo 'office_id='.var_export($u->office_id, true).PHP_EOL;
echo 'headOfficeIds='.json_encode($u->headOfficeIds()).PHP_EOL;
echo 'officeScopeIds='.json_encode($u->officeScopeIds()).PHP_EOL;
foreach(DB::table('model_has_roles')->where('model_id',41)->get() as $r){
  echo 'role_id='.$r->role_id.' scope_type='.var_export($r->scope_type,true).' scope_id='.var_export($r->scope_id,true).PHP_EOL;
}
echo 'visibleTo='.App\Models\Project::visibleTo($u)->count().' of '.App\Models\Project::count().PHP_EOL;
