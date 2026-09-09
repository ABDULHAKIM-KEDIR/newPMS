<?php

use App\Models\User;
use App\Rules\NotCommonPassword;

use function Pest\Laravel\post;

it('rejects weak passwords during public registration', function () {
    $weak = [
        'Password1!',              // too short (< 12)
        'password1234',            // no uppercase / number rule fails
        'Passwordpassword!',       // no number
        'Password1234',            // no special character
    ];

    foreach ($weak as $password) {
        post(route('register.attempt', [], false), [
            'full_name' => 'Weak Pass',
            'email' => 'weak-'.md5($password).'@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');
    }

    expect(User::where('email', 'like', 'weak-%')->count())->toBe(0);
});

it('accepts a strong password during public registration', function () {
    $response = post(route('register.attempt', [], false), [
        'full_name' => 'Strong Pass',
        'email' => 'strong@example.com',
        'password' => 'Tr0ub4dor&3xKq9',
        'password_confirmation' => 'Tr0ub4dor&3xKq9',
    ]);

    $response->assertSessionHasNoErrors();
    expect(User::where('email', 'strong@example.com')->exists())->toBeTrue();
});

it('rejects common passwords via the NotCommonPassword rule', function () {
    $rule = new NotCommonPassword;

    $fails = 0;
    $rule->validate('password', 'P@ssw0rd123', function () use (&$fails) {
        $fails++;
    });
    $rule->validate('password', 'Admin@123', function () use (&$fails) {
        $fails++;
    });
    $rule->validate('password', 'Tr0ub4dor&3xKq9', function () use (&$fails) {
        $fails++;
    });

    expect($fails)->toBe(2);
});
