<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $primaryKey = 'setting_id';
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'updated_at'];

    protected $casts = ['updated_at' => 'datetime'];

    // The fixed set of keys the settings form edits — kept small and
    // explicit rather than an open-ended key/value form.
    public const KEYS = [
        'directorate_name' => 'Directorate name',
        'default_currency' => 'Default currency',
        'session_timeout_minutes' => 'Session timeout notice (minutes)',
        'support_email' => 'Support contact email',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_at' => now()]);
    }
}
