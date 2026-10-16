<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ClearExpiredSocialAvatars extends Command
{
    protected $signature = 'avatars:clear-expired {--dry-run : List the affected users without changing anything}';

    protected $description = 'Clear LinkedIn avatar URLs that have expired (they return 403 and break images in the app)';

    public function handle(): int
    {
        $now = time();
        $cleared = 0;

        User::query()
            ->where('avatar', 'like', 'https://media.licdn.com/%')
            ->select(['id', 'avatar'])
            ->chunkById(200, function ($users) use ($now, &$cleared) {
                foreach ($users as $user) {
                    parse_str((string) parse_url($user->avatar, PHP_URL_QUERY), $query);
                    $expiresAt = isset($query['e']) ? (int) $query['e'] : null;

                    // LinkedIn puts the expiry timestamp in the "e" parameter.
                    // Without it we cannot tell, so leave the URL alone.
                    if ($expiresAt === null || $expiresAt > $now) {
                        continue;
                    }

                    $cleared++;
                    $this->line("User #{$user->id}: expired " . date('Y-m-d', $expiresAt));

                    if (! $this->option('dry-run')) {
                        $user->forceFill(['avatar' => null])->saveQuietly();
                    }
                }
            });

        $this->info(($this->option('dry-run') ? 'Would clear' : 'Cleared') . " {$cleared} expired LinkedIn avatar(s).");

        return self::SUCCESS;
    }
}
