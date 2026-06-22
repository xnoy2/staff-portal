<?php

namespace App\Console\Commands;

use App\Models\BoardCard;
use App\Models\CardChecklistItem;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecurDueCards extends Command
{
    protected $signature = 'cards:recur';

    protected $description = 'Spawn the next occurrence of recurring cards once they fall due';

    public function handle(): int
    {
        $now = now();

        $cards = BoardCard::query()
            ->where('recurring', '!=', 'never')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $now)
            ->whereNull('recurred_at')
            ->with(['labels', 'checklistItems'])
            ->get();

        $spawned = 0;

        foreach ($cards as $card) {
            // Advance the due date by whole intervals until it lands in the future,
            // so the successor doesn't immediately re-trigger.
            $nextDue = $card->due_date->copy();
            do {
                $nextDue = $this->advance($nextDue, $card->recurring);
            } while ($nextDue->lessThanOrEqualTo($now));

            // Keep the start→due gap (in days) on the new occurrence.
            $nextStart = null;
            if ($card->start_date) {
                $daysAdvanced = $card->due_date->copy()->startOfDay()
                    ->diffInDays($nextDue->copy()->startOfDay());
                $nextStart = $card->start_date->copy()->addDays($daysAdvanced);
            }

            $maxOrder = BoardCard::where('list_id', $card->list_id)->max('sort_order') ?? 0;

            $copy = BoardCard::create([
                'list_id'      => $card->list_id,
                'title'        => $card->title,
                'description'  => $card->description,
                'start_date'   => $nextStart,
                'due_date'     => $nextDue,
                'due_done'     => false,
                'due_reminder' => $card->due_reminder,
                'recurring'    => $card->recurring,
                'created_by'   => $card->created_by,
                'sort_order'   => $maxOrder + 1,
            ]);

            if ($card->labels->isNotEmpty()) {
                $copy->labels()->attach($card->labels->pluck('id')->all());
            }

            foreach ($card->checklistItems as $item) {
                CardChecklistItem::create([
                    'card_id'    => $copy->id,
                    'title'      => $item->title,
                    'is_done'    => false,
                    'sort_order' => $item->sort_order,
                ]);
            }

            // Mark the original so it only ever spawns one successor.
            $card->update(['recurred_at' => $now]);
            $spawned++;
        }

        $this->info("Spawned {$spawned} recurring card(s).");

        return self::SUCCESS;
    }

    private function advance(Carbon $date, string $freq): Carbon
    {
        return match ($freq) {
            'daily'   => $date->copy()->addDay(),
            'weekly'  => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonthNoOverflow(),
            default   => $date->copy()->addYear(),
        };
    }
}
