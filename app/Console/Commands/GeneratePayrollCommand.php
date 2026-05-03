<?php

namespace App\Console\Commands;

use App\Models\PayrollRun;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GeneratePayrollCommand extends Command
{
    protected $signature = 'payroll:generate
                            {--force : Run even if today is not the cut-off day}
                            {--from= : Override period start date (YYYY-MM-DD)}
                            {--to=   : Override period end date (YYYY-MM-DD)}';

    protected $description = 'Auto-generate draft payroll runs for all active staff on the cut-off day';

    public function handle(): int
    {
        $cutoffDay = (int) Setting::get('payroll_cutoff_day', 25);
        $today     = now();

        if (! $this->option('force') && $today->day !== $cutoffDay) {
            $this->info("Today ({$today->format('d')}) is not the cut-off day ({$cutoffDay}). Use --force to override.");
            return self::SUCCESS;
        }

        if ($this->option('from') && $this->option('to')) {
            $from = Carbon::parse($this->option('from'))->startOfDay();
            $to   = Carbon::parse($this->option('to'))->endOfDay();
        } else {
            $period = PayrollRun::currentPeriod($cutoffDay);
            $from   = Carbon::parse($period['from'])->startOfDay();
            $to     = Carbon::parse($period['to'])->endOfDay();
        }

        $this->info("Generating payroll: {$from->toDateString()} → {$to->toDateString()}");

        $staff = User::where('is_active', true)
            ->get()
            ->filter(fn ($u) => ! $u->hasRole('admin'));

        $generated = 0;
        $skipped   = 0;

        foreach ($staff as $user) {
            $exists = PayrollRun::where('user_id', $user->id)
                ->where('period_from', $from->toDateString())
                ->where('period_to', $to->toDateString())
                ->exists();

            if ($exists) {
                $this->line("  SKIP  {$user->name} (already exists)");
                $skipped++;
                continue;
            }

            PayrollRun::generate($user, $from, $to, null);
            $this->line("  OK    {$user->name}");
            $generated++;
        }

        $this->newLine();
        $this->info("Done — generated: {$generated}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
