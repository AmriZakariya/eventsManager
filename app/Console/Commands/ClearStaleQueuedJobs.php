<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearStaleQueuedJobs extends Command
{
    protected $signature = 'queue:clear-stale
        {--hours=24 : Delete queued jobs created more than this many hours ago}
        {--dry-run : Only show what would be deleted}';

    protected $description = 'Delete old jobs from the database queue so a newly started worker does not send months-old notifications';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours)->getTimestamp();

        $stale = DB::table('jobs')->where('created_at', '<', $cutoff);

        $byType = (clone $stale)->pluck('payload')
            ->map(fn ($payload) => json_decode($payload, true)['displayName'] ?? 'unknown')
            ->countBy()
            ->sortDesc();

        if ($byType->isEmpty()) {
            $this->info("No queued jobs older than {$hours} hour(s).");

            return self::SUCCESS;
        }

        $this->table(['Job', 'Count'], $byType->map(fn ($count, $job) => [$job, $count])->values());

        $total = $byType->sum();

        if ($this->option('dry-run')) {
            $this->info("Would delete {$total} job(s) older than {$hours} hour(s).");

            return self::SUCCESS;
        }

        $deleted = $stale->delete();
        $this->info("Deleted {$deleted} job(s) older than {$hours} hour(s).");

        return self::SUCCESS;
    }
}
