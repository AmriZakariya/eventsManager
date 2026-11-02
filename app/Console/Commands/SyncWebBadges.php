<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Service\WordPressBadgeService;
use Illuminate\Console\Command;

/**
 * Fills users.web_badge_code from the WordPress badge lookup API.
 * Safe to run again: by default only users without a code are looked up.
 */
class SyncWebBadges extends Command
{
    protected $signature = 'badges:sync-web
        {--all : Look up every user again, also those who already have a code}
        {--email= : Only this user}';

    protected $description = 'Get each user\'s website badge code (HCX-…) from WordPress';

    public function handle(WordPressBadgeService $badges): int
    {
        $query = User::query()
            ->when($this->option('email'), fn ($q, $email) => $q->where('email', $email))
            ->when(! $this->option('all') && ! $this->option('email'), fn ($q) => $q->whereNull('web_badge_code'));

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to do: every user already has a website badge code.');

            return self::SUCCESS;
        }

        $this->info("Looking up {$total} user(s)…");
        $found = 0;
        $bar = $this->output->createProgressBar($total);

        $query->orderBy('id')->chunkById(100, function ($users) use ($badges, &$found, $bar) {
            foreach ($users as $user) {
                if ($badges->refresh($user)) {
                    $found++;
                }
                $bar->advance();
                usleep(100_000); // stay gentle with the WordPress server
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$found} of {$total} user(s) have a website badge code.");

        if ($found < $total) {
            $this->line('The others have no badge on the website yet (or it could not be reached). Their badge keeps the app code; they are looked up again when they open their badge.');
        }

        return self::SUCCESS;
    }
}
