<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WordPressUserSyncService
{
    private const DEFAULT_WORDPRESS_SYNC_URL = 'https://hygiecleanexpo.com/wp-json/hc-sync/v1/users';

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
        $payload = [
            'external_user_id' => 'app-user-'.$user->id,
            'email' => $user->email,
            'user_login' => $this->userLogin($user),
            'first_name' => $user->name,
            'last_name' => $user->last_name,
            'display_name' => trim($user->name.' '.$user->last_name),
            'role' => $this->wordpressRole($user),
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'company_name' => $user->company_name ?? ($user->company?->name ?? ''),
            'company_sector' => $user->company_sector,
            'country' => $user->country,
            'city' => $user->city,
            'badge_code' => $user->badge_code,
            'badge_pdf_url' => url('/api/generateBadge'),
            'badge_check_url' => $user->badge_code ? url('/admin/users/'.$user->id.'/edit') : null,
        ];

        if ($plainPassword !== null && $plainPassword !== '') {
            $payload['password'] = $plainPassword;
        }

        return $payload;
    }

    private function wordpressRole(User $user): string
    {
        return $user->role === User::APP_ROLE_ADMIN ? 'administrator' : 'subscriber';
    }

    private function userLogin(User $user): string
    {
        $base = Str::before($user->email, '@');
        $base = Str::of($base)
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '.')
            ->trim('.')
            ->value();

        return trim(($base !== '' ? $base : 'user').'.'.$user->id, '.');
    }

    public function url(): string
    {
        return (string) config('services.wordpress_sync.url', self::DEFAULT_WORDPRESS_SYNC_URL);
    }

    public function token(): string
    {
        return (string) config('services.wordpress_sync.outbound_token', '');
    }
}
