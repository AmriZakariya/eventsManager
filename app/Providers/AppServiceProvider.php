<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $baseUrl = rtrim(
                config('app.password_reset_url') ?: (config('app.url') . '/reset-password-landing'),
                '/'
            );

            return $baseUrl . "?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });

        // LogUserLogin / LogUserLogout are registered by Laravel's automatic
        // event discovery (app/Listeners). Registering them here as well made
        // every login and logout appear twice in the audit log.

        Gate::define('access-admin-dashboard', function (User $user): bool {
            return $user->hasAccess('platform.index')
                && ($user->hasAccess('platform.systems.users') || $user->hasAccess('platform.audit-logs'));
        });
    }
}
