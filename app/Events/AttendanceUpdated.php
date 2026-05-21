<?php

namespace App\Events;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AttendanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $clockedInStaff;

    public function __construct()
    {
        $this->clockedInStaff = $this->computeList();
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.attendance')];
    }

    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    private function computeList(): array
    {
        $activeEntries = TimeEntry::active()
            ->get(['user_id', 'clock_in', 'clock_state', 'ot_type'])
            ->keyBy('user_id');

        if ($activeEntries->isEmpty()) {
            return [];
        }

        return User::with('roles')
            ->whereIn('id', $activeEntries->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'avatar'])
            ->map(function ($u) use ($activeEntries) {
                $entry = $activeEntries[$u->id];
                return [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'avatar_url'  => $u->avatar_url,
                    'since'       => Carbon::parse($entry->clock_in)->format('H:i'),
                    'clock_in'    => Carbon::parse($entry->clock_in)->toIso8601String(),
                    'clock_state' => $entry->clock_state ?? 'working',
                    'ot_type'     => $entry->ot_type,
                    'role'        => $u->getRoleNames()->first() ?? 'staff',
                ];
            })
            ->values()
            ->toArray();
    }
}
