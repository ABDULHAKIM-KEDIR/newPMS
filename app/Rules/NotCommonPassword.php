<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects passwords known to be common or appearing in public breaches.
 * Uses a local blacklist so validation works without external API calls.
 */
class NotCommonPassword implements ValidationRule
{
    /**
     * A short local list of the most commonly used / leaked passwords.
     */
    protected const COMMON = [
        'password', 'password1', 'password123', '123456', '123456789',
        '12345678', '1234567890', 'qwerty', 'qwerty123', 'abc123',
        'letmein', 'welcome', 'welcome1', 'admin', 'admin123',
        'iloveyou', 'monkey', 'dragon', 'football', 'baseball',
        'sunshine', 'princess', 'superman', 'trustno1', 'master',
        'p@ssw0rd', 'p@ssword', 'passw0rd', 'changeme', 'secret',
        '1234', '12345', '111111', '000000', '654321', '666666',
        'aaaaaa', 'qazwsx', 'michael', 'jennifer', 'jordan',
        'hunter2', 'freedom', 'whatever', 'starwars', 'summer',
        'password!1', 'password@1', 'p@ssword1', 'qwerty@123',
        'admin@123', 'admin@2024', 'admin@2025', 'root', 'toor',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (in_array(strtolower($value), self::COMMON, true)) {
            $fail('The :attribute is too common. Choose a more unique password.');

            return;
        }

        // Also reject passwords that are just a common word plus 1-3 digits.
        foreach (self::COMMON as $common) {
            if (strlen($common) >= 6
                && preg_match('/^'.preg_quote($common, '/').'[\d!@#$%^&*.]{0,3}$/i', $value)) {
                $fail('The :attribute is too common. Choose a more unique password.');

                return;
            }
        }
    }
}
