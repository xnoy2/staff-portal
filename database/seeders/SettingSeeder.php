<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // ── Business ──────────────────────────────────────────────────────
            ['key' => 'company_name',     'value' => 'BCF',         'type' => 'string',  'group' => 'business'],
            ['key' => 'company_timezone', 'value' => 'UTC',         'type' => 'string',  'group' => 'business'],
            ['key' => 'date_format',      'value' => 'DD/MM/YYYY',  'type' => 'string',  'group' => 'business'],
            ['key' => 'currency_symbol',  'value' => '£',           'type' => 'string',  'group' => 'business'],

            // ── Attendance ────────────────────────────────────────────────────
            ['key' => 'overtime_threshold_hours',   'value' => '8',  'type' => 'integer', 'group' => 'attendance'],
            ['key' => 'clock_grace_period_minutes', 'value' => '0',  'type' => 'integer', 'group' => 'attendance'],
            ['key' => 'max_shift_hours',            'value' => '16', 'type' => 'integer', 'group' => 'attendance'],
            ['key' => 'auto_clockout_enabled',      'value' => '0',  'type' => 'boolean', 'group' => 'attendance'],
            ['key' => 'auto_clockout_after_hours',  'value' => '12', 'type' => 'integer', 'group' => 'attendance'],

            // ── Leave ─────────────────────────────────────────────────────────
            ['key' => 'default_annual_leave_days',    'value' => '14', 'type' => 'integer', 'group' => 'leave'],
            ['key' => 'default_sick_leave_days',      'value' => '5',  'type' => 'integer', 'group' => 'leave'],
            ['key' => 'default_emergency_leave_days', 'value' => '3',  'type' => 'integer', 'group' => 'leave'],
            ['key' => 'leave_approval_required',      'value' => '1',  'type' => 'boolean', 'group' => 'leave'],

            // ── Appearance ────────────────────────────────────────────────────
            ['key' => 'app_name',      'value' => 'Staff Portal', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'primary_color', 'value' => '#EF233C',      'type' => 'string', 'group' => 'appearance'],
            ['key' => 'sidebar_color', 'value' => '#2B2D42',      'type' => 'string', 'group' => 'appearance'],

            // ── Payroll ───────────────────────────────────────────────────────
            ['key' => 'payroll_cutoff_day', 'value' => '25', 'type' => 'integer', 'group' => 'payroll'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
