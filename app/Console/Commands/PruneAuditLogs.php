<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit-logs:prune {--days=90 : Number of days of logs to retain}';

    protected $description = 'Delete audit log records older than the retention period';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleted = AuditLogService::prune($days);

        $this->info("Pruned {$deleted} audit log record(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
