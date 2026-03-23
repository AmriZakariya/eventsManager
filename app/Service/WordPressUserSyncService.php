<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class WordPressUserSyncService
{
    private const DEFAULT_WORDPRESS_SYNC_URL = 'https://hygiecleanexpo.com/wp-json/hc-sync/v1/users';
    private const DEFAULT_WORDPRESS_SYNC_TOKEN = 'your-secret-sync-key-123';

    public function syncUser(User $user, ?string $plainPassword = null, string $source = User::WORDPRESS_SYNC_SOURCE_APP): bool
    {
        try {
            $response = Http::withToken($this->token())
                ->timeout(10)
                ->post($this->url(), $this->payload($user, $plainPassword));

            if ($response->successful()) {
                $user->markWordPressSyncSuccess($source);

                return true;
            }

            $user->markWordPressSyncFailure(
                sprintf('HTTP %s: %s', $response->status(), $response->body()),
                $source
            );

            return false;
        } catch (\Throwable $exception) {
            $user->markWordPressSyncFailure($exception->getMessage(), $source);

            return false;
        }
    }

    public function payload(User $user, ?string $plainPassword = null): array
    {
        return [
            'email' => $user->email,
            'first_name' => $user->name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'company_name' => $user->company_name ?? ($user->company?->name ?? ''),
            'company_sector' => $user->company_sector,
            'country' => $user->country,
            'city' => $user->city,
            'badge_code' => $user->badge_code,
            'role' => $user->role,
            'password' => $plainPassword,
        ];
    }

    public function url(): string
    {
        return (string) config('services.wordpress_sync.url', self::DEFAULT_WORDPRESS_SYNC_URL);
    }

    public function token(): string
    {
        return (string) config('services.wordpress_sync.token', self::DEFAULT_WORDPRESS_SYNC_TOKEN);
    }
}
