<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::query()->first();
$user->password_hash = Hash::make('ChangeMe123!');
$user->status = 'Active';
$user->save();

// Verify login works exactly like the AuthController does
$ok = Auth::attempt([
    'email' => $user->email,
    'password' => 'ChangeMe123!',
]);

echo 'Email: '.$user->email.PHP_EOL;
echo 'Status: '.$user->status.PHP_EOL;
echo 'Login attempt: '.($ok ? 'SUCCESS' : 'FAILED').PHP_EOL;
