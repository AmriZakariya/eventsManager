<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetUserPassword extends Command
{
    protected $signature = 'user:set-password {email} {password}';

    protected $description = 'Set (hash) a user\'s password directly. For support/debugging on hosts without tinker.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $users = User::where('email', $email)->get();

        if ($users->isEmpty()) {
            $this->error("No user found with email: {$email}");
            return self::FAILURE;
        }

        if ($users->count() > 1) {
            $this->warn("Heads up: {$users->count()} users share this email — updating all of them.");
        }

        foreach ($users as $user) {
            $user->password = Hash::make($password);
            $user->password_is_set = true;
            $user->save();
            $this->info("Password set for user #{$user->id} ({$user->email}).");
        }

        $this->info('Done. Log in with the new password (clear any autofilled value first).');

        return self::SUCCESS;
    }
}
