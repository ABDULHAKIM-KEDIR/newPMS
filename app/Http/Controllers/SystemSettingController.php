<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSettingController extends Controller
{
    public function edit()
    {
        abort_unless(Auth::user()->can('manage_system_settings'), 403);

        $values = SystemSetting::pluck('value', 'key');

        return view('admin.settings', ['values' => $values]);
    }

    public function update(Request $request)
    {
        abort_unless(Auth::user()->can('manage_system_settings'), 403);

        $data = $request->validate([
            'directorate_name' => ['nullable', 'string', 'max:150'],
            'default_currency' => ['nullable', 'string', 'max:10'],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'support_email' => ['nullable', 'email', 'max:150'],
        ]);

        $changed = [];
        foreach (SystemSetting::KEYS as $key => $label) {
            $newValue = $data[$key] ?? null;
            $oldValue = SystemSetting::get($key);
            if ((string) $oldValue !== (string) $newValue) {
                $changed[] = $label;
            }
            SystemSetting::set($key, $newValue);
        }

        if ($changed) {
            Activity::log('Updated system settings', 'SystemSetting', null, implode(', ', $changed));
        }

        return back()->with('status', 'Settings saved.');
    }
}
