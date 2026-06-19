<?php

namespace App\Console\Commands;

use App\Models\BoardCard;
use App\Models\BoardList;
use App\Models\Workspace;
use Illuminate\Console\Command;

class PruneDefaultWorkspacesCommand extends Command
{
    protected $signature = 'workspaces:prune-defaults
                            {--dry-run : List what would be removed without deleting anything}';

    protected $description = 'Remove auto-provisioned personal "default" workspaces left over from the old auto-create behaviour (workspaces are now admin-assigned only).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // A "default" workspace is one that was auto-created for a single user:
        //   - auto-named "<First>'s Workspace", AND
        //   - has exactly one member, who is its owner.
        // Shared / admin-assigned workspaces (more than one member, a member who
        // is not the owner, or a deliberate name) are never matched.
        $candidates = Workspace::query()
            ->withCount('members')
            ->where('name', 'like', "%'s Workspace")
            ->get()
            ->filter(fn (Workspace $w) => $w->members_count === 1
                && $w->members()
                    ->where('users.id', $w->owner_id)
                    ->wherePivot('role', 'owner')
                    ->exists());

        if ($candidates->isEmpty()) {
            $this->info('No default workspaces found. Nothing to prune.');
            return self::SUCCESS;
        }

        $rows = $candidates->map(function (Workspace $w) {
            $boardIds = $w->boards()->pluck('id');
            $listIds  = BoardList::whereIn('board_id', $boardIds)->pluck('id');
            $cards    = BoardCard::whereIn('list_id', $listIds)->count();

            return [$w->name, optional($w->owner)->name, $boardIds->count(), $cards];
        });

        $this->table(['Workspace', 'Owner', 'Boards', 'Cards'], $rows);

        if ($dryRun) {
            $this->warn("[dry-run] {$candidates->count()} workspace(s) would be deleted along with the boards/lists/cards shown above. Re-run without --dry-run to apply.");
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($candidates as $w) {
            $w->delete(); // cascades to boards -> lists -> cards via FK constraints
            $deleted++;
        }

        $this->info("Deleted {$deleted} default workspace(s).");
        return self::SUCCESS;
    }
}
