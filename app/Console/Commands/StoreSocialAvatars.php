<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SocialAvatarStore;
use Illuminate\Console\Command;

/**
 * One-off cleanup of avatars saved as social provider URLs before sign-in
 * started storing a copy. Valid ones are downloaded so they never expire;
 * expired or unreachable ones are cleared (the user shows initials until their
 * next social login stores a fresh copy).
 */
class StoreSocialAvatars extends Command
{
    protected $signature = 'avatars:store-social {--dry-run : Show what would happen without changing anything}';

    protected $aliases = ['avatars:clear-expired'];

    protected $description = 'Store social provider avatars locally and clear expired ones';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['stored' => 0, 'cleared' => 0, 'skipped' => 0];

        User::query()
            ->where('avatar', 'like', 'http%')
            ->select(['id', 'avatar'])
            ->chunkById(100, function ($users) use ($dryRun, &$counts) {
                foreach ($users as $user) {
                    $result = $this->handleUser($user, $dryRun);
                    $counts[$result]++;
                }
            });

        $verb = $dryRun ? 'Would' : 'Done:';
        $this->info(sprintf(
            '%s store %d, clear %d, leave %d (not a social provider URL).',
            $verb,
            $counts['stored'],
            $counts['cleared'],
            $counts['skipped'],
        ));

        return self::SUCCESS;
    }

    private function handleUser(User $user, bool $dryRun): string
    {
        $url = $user->avatar;
        $host = parse_url($url, PHP_URL_HOST);

        // Other URLs (e.g. a site's own images) are not ours to touch.
        if (! SocialAvatarStore::isProviderUrl($url)) {
            $this->line("User #{$user->id}: leave ({$host})");

            return 'skipped';
        }

        $expiresAt = SocialAvatarStore::expiresAt($url);
        if ($expiresAt !== null && $expiresAt <= time()) {
            $this->line("User #{$user->id}: clear (expired " . date('Y-m-d', $expiresAt) . ')');
            $this->save($user, null, $dryRun);

            return 'cleared';
        }

        if ($dryRun) {
            $this->line("User #{$user->id}: store ({$host})");

            return 'stored';
        }

        $path = SocialAvatarStore::download($url);
        if ($path === null) {
            $this->line("User #{$user->id}: clear (download failed, {$host})");
            $this->save($user, null, $dryRun);

            return 'cleared';
        }

        $this->line("User #{$user->id}: stored as {$path}");
        $this->save($user, $path, $dryRun);

        return 'stored';
    }

    private function save(User $user, ?string $avatar, bool $dryRun): void
    {
        if (! $dryRun) {
            $user->forceFill(['avatar' => $avatar])->saveQuietly();
        }
    }
}
