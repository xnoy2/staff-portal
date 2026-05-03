<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-generate payroll runs at midnight on the cut-off day.
// The command checks internally whether today matches the configured cut-off day.
Schedule::command('payroll:generate')->dailyAt('00:05');
