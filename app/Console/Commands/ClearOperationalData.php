<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOperationalData extends Command
{
    protected $signature   = 'app:clear-operational-data {--force : Skip confirmation prompt}';
    protected $description = 'Clear test operational data before go-live (attendance, payroll, leave, overtime)';

    public function handle(): int
    {
        $tables = [
            'time_entry_breaks' => 'Attendance break logs',
            'time_entries'      => 'Attendance / clock records',
            'payroll_runs'      => 'Payroll runs',
            'leave_requests'    => 'Leave requests',
            'overtime_requests' => 'Overtime requests',
        ];

        $this->info('Tables to be cleared:');
        foreach ($tables as $table => $label) {
            $count = DB::table($table)->count();
            $this->line("  {$label} ({$table}): <comment>{$count} rows</comment>");
        }

        if (! $this->option('force')) {
            if (! $this->confirm('This will permanently delete all rows in these tables. Continue?')) {
                $this->line('Aborted.');
                return self::FAILURE;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table => $label) {
            DB::table($table)->truncate();
            $this->line("  Cleared: <info>{$label}</info>");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('Done. All operational data cleared.');

        return self::SUCCESS;
    }
}
