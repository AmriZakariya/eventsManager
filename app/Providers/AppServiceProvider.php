<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
            // OPTION 1: Link to a Web Landing Page (Recommended)
            // This page should handle the Deep Linking to your app or simply show a form.
            return config('app.url') . "/reset-password-landing?token={$token}&email={$notifiable->getEmailForPasswordReset()}";

            // OPTION 2: Direct App Deep Link (If supported by email client)
            // return "eventsmanager://reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
