<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * Ban / Unban actions shared by the user view and edit screens.
 * Banning keeps the account (so its email and social logins stay blocked),
 * logs it out and hides it and its conversations from the app.
 */
trait BansUsers
{
    protected function banActions(?User $user): array
    {
        $exists = $user?->exists ?? false;

        return [
            ModalToggle::make('Ban user')
                ->icon('bs.slash-circle')
                ->modal('banUserModal')
                ->method('banUser')
                ->type(Color::DANGER)
                ->canSee($exists && ! $user->isBanned()),

            Button::make('Unban user')
                ->icon('bs.arrow-counterclockwise')
                ->method('unbanUser')
                ->confirm('The user can log in again, and their profile and conversations become visible again.')
                ->canSee($exists && $user->isBanned()),
        ];
    }

    protected function banModal(): \Orchid\Screen\Layout
    {
        return Layout::modal('banUserModal', [
            Layout::rows([
                TextArea::make('ban_reason')
                    ->title('Reason')
                    ->required()
                    ->maxlength(500)
                    ->rows(3)
                    ->placeholder('e.g. Competitor sending spam messages to exhibitors')
                    ->help('Only visible to admins. The user is logged out at once, cannot log in or sign up again with this email or social account, and disappears from the app together with their conversations. Nothing is deleted; you can unban later.'),
            ]),
        ])->title('Ban this user?')->applyButton('Ban user');
    }

    public function banUser(User $user, Request $request): void
    {
        $request->validate(['ban_reason' => 'required|string|max:500']);

        if ($user->id === $request->user()->id) {
            Toast::error('You cannot ban your own account.');
            return;
        }

        if ($user->roles()->exists()) {
            Toast::error('This user has admin panel roles. Remove them first, then ban.');
            return;
        }

        $user->ban(trim($request->input('ban_reason')), $request->user()->id);

        Toast::warning(trim($user->name . ' ' . $user->last_name) . ' is banned and was logged out.');
    }

    public function unbanUser(User $user): void
    {
        $user->unban();

        Toast::success(trim($user->name . ' ' . $user->last_name) . ' is no longer banned.');
    }
}
