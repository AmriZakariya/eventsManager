<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LookupUser extends Command
{
    protected $signature = 'user:lookup {query}';

    protected $description = 'Find users by (partial) email or name — for support/debugging.';

    public function handle(): int
    {
        $query = $this->argument('query');

        $users = User::where('email', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orderBy('id')
            ->get(['id', 'name', 'last_name', 'email', 'created_source', 'password_is_set', 'created_at']);

        if ($users->isEmpty()) {
            $this->error("No users match: {$query}");
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Source', 'Pwd set?', 'Created'],
            $users->map(fn ($u) => [
                $u->id,
                trim($u->name.' '.$u->last_name),
                // Wrap the email in quotes to expose any leading/trailing spaces.
                '"'.$u->email.'"',
                $u->created_source,
                $u->password_is_set ? 'yes' : 'no',
                optional($u->created_at)->format('Y-m-d H:i'),
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
