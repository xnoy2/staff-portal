<?php

namespace App\Console\Commands;

use App\Models\DailyLog;
use App\Models\Job;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\DailyLogReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendDailyLogReminders extends Command
{
    protected $signature = 'daily-log:remind';

    protected $description = 'Remind staff to submit their end-of-day activity log (fires at each worker local ~6pm)';

    /** Local hour at which to remind (24h). */
    private const REMIND_HOUR = 18;

    public function handle(): int
    {
        $sent = 0;

        User::where('is_active', true)->get()->each(function (User $u) use (&$sent) {
            $tz       = $u->timezone;
            $localNow = now()->copy()->setTimezone($tz);

            // Only fire in the worker's ~6pm hour, so an hourly schedule reminds once/day.
            if ((int) $localNow->format('G') !== self::REMIND_HOUR) {
                return;
            }

            $date = $localNow->toDateString();

            // Already submitted their log for today → nothing to do.
            if (DailyLog::where('user_id', $u->id)->where('log_date', $date)->where('status', 'submitted')->exists()) {
                return;
            }

            // Only remind people who actually worked today (attendance or an assigned job).
            $dayStart = Carbon::parse("{$date} 00:00:00", $tz)->utc();
            $dayEnd   = Carbon::parse("{$date} 23:59:59", $tz)->utc();

            $worked = TimeEntry::where('user_id', $u->id)
                    ->whereBetween('clock_in', [$dayStart, $dayEnd])->exists()
                || Job::whereDate('date', $date)
                    ->whereHas('staff', fn ($q) => $q->where('users.id', $u->id))->exists();

            if (! $worked) {
                return;
            }

            try {
                $u->notify(new DailyLogReminder($date));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Daily log reminder failed for {$u->id}: {$e->getMessage()}");
            }
        });

        $this->info("Sent daily-log reminders to {$sent} staff.");

        return self::SUCCESS;
    }
}
