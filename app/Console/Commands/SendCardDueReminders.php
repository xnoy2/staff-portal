<?php

namespace App\Console\Commands;

use App\Models\BoardCard;
use App\Notifications\CardDueReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendCardDueReminders extends Command
{
    protected $signature = 'cards:due-reminders';

    protected $description = 'Notify workspace members of cards whose due-date reminder has arrived';

    public function handle(): int
    {
        $now = now();

        $cards = BoardCard::query()
            ->whereNotNull('due_date')
            ->whereNotNull('due_reminder')
            ->whereNull('due_reminder_sent_at')
            ->where('due_done', false)
            ->with('list.board.workspace.members')
            ->get();

        $sent = 0;

        foreach ($cards as $card) {
            $offset = BoardCard::REMINDER_OFFSETS[$card->due_reminder] ?? null;
            if ($offset === null) {
                continue;
            }

            // Fire once the reminder moment (due minus offset) has been reached.
            if ($card->due_date->copy()->subMinutes($offset)->greaterThan($now)) {
                continue;
            }

            $board   = $card->list?->board;
            $members = $board?->workspace?->members ?? collect();

            if ($board && $members->isNotEmpty()) {
                try {
                    Notification::send($members, new CardDueReminder(
                        $card->title,
                        $board->id,
                        $card->id,
                        $card->due_date->toIso8601String(),
                    ));
                    $sent++;
                } catch (\Throwable $e) {
                    // Don't let one bad card (e.g. SMTP hiccup) re-fire every minute.
                    Log::warning("Card due reminder failed for {$card->id}: {$e->getMessage()}");
                }
            }

            $card->update(['due_reminder_sent_at' => $now]);
        }

        $this->info("Sent due reminders for {$sent} card(s).");

        return self::SUCCESS;
    }
}
