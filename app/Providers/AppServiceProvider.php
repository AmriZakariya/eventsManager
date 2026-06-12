<?php

namespace App\Providers;

use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
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

        Event::listen(Login::class, LogUserLogin::class);
        Event::listen(Logout::class, LogUserLogout::class);

        Gate::define('access-admin-dashboard', function (User $user): bool {
            return $user->hasAccess('platform.index')
                && ($user->hasAccess('platform.systems.users') || $user->hasAccess('platform.audit-logs'));
        });
    }
}
