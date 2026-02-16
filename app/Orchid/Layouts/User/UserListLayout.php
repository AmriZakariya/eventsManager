<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Persona;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class UserListLayout extends Table
{
    public $target = 'users';

    public function columns(): array
    {
        return [
            TD::make('__checkbox', '')
                ->width('30px')
                ->cantHide()
                ->render(fn(User $user) =>
                "<input type='checkbox' class='form-check-input' name='users[]' value='{$user->id}'>"
                ),

            TD::make('avatar', 'Avatar')
                ->width('60px')
                ->render(fn (User $user) =>
                $user->avatar
                    ? "<img src='" . asset($user->avatar_url) . "' class='rounded-circle' style='width: 40px; height: 40px; object-fit: cover;'>"
                    : "<div class='bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white' style='width: 40px; height: 40px; font-weight: bold;'>{$user->name[0]}</div>"
                ),

            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) =>
                    '<div>' .
                    Link::make($user->name . ' ' . ($user->last_name ?? ''))
                        ->route('platform.systems.users.edit', $user->id)
                        ->class('fw-bold text-decoration-none') .
                    ($user->is_visible ? '' : ' <span class="badge bg-secondary ms-1">Hidden</span>') .
                    '</div>'
                ),

            TD::make('email', __('Email'))
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) =>
                    '<div>' .
                    '<div>' . $user->email . '</div>' .
                    ($user->phone ? '<small class="text-muted"><i class="bi bi-phone"></i> ' . $user->phone . '</small>' : '') .
                    '</div>'
                ),

            TD::make('job_title', 'Job Title')
                ->sort()
                ->defaultHidden()
                ->render(fn (User $user) => $user->job_title ?? '<span class="text-muted">—</span>'),

            TD::make('company_id', 'Company')
                ->sort()
                ->render(fn (User $user) =>
                $user->company
                    ? Link::make($user->company->name)
                    ->route('platform.companies.edit', $user->company->id)
                    ->icon('bs.building')
                    : '<span class="text-muted">—</span>'
                ),

            TD::make('roles', 'Role')
                ->render(fn (User $user) =>
                $user->roles->map(function($role) {
                    $color = match($role->slug) {
                        'admin' => 'bg-danger',
                        'exhibitor' => 'bg-primary',
                        'visitor' => 'bg-info',
                        default => 'bg-secondary',
                    };
                    return "<span class='badge {$color}'>{$role->name}</span>";
                })->implode(' ')
                ),

            TD::make('is_visible', 'Visibility')
                ->sort()
                ->alignCenter()
                ->render(fn (User $user) =>
                $user->is_visible
                    ? '<span class="badge bg-success"><i class="bi bi-eye"></i> Visible</span>'
                    : '<span class="badge bg-secondary"><i class="bi bi-eye-slash"></i> Hidden</span>'
                ),

            TD::make('linkedin_url', 'LinkedIn')
                ->defaultHidden()
                ->render(fn (User $user) =>
                $user->linkedin_url
                    ? Link::make('<i class="bi bi-linkedin"></i>')
                    ->href($user->linkedin_url)
                    ->target('_blank')
                    : '<span class="text-muted">—</span>'
                ),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('updated_at', __('Last edit'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->cantHide()
                ->width('100px')
                ->render(fn (User $user) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->route('platform.systems.users.edit', $user->id)
                            ->icon('bs.pencil'),

                        ModalToggle::make('Quick Edit')
                            ->modal('editUserModal')
                            ->modalTitle('Edit ' . $user->name)
                            ->method('saveUser')
                            ->asyncParameters(['user' => $user->id])
                            ->icon('bs.lightning'),

                        Button::make($user->is_visible ? 'Hide' : 'Show')
                            ->icon($user->is_visible ? 'bs.eye-slash' : 'bs.eye')
                            ->method('toggleVisibility', ['id' => $user->id]),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm('Once the account is deleted, all of its resources and data will be permanently deleted.')
                            ->method('remove', ['id' => $user->id]),
                    ])),
        ];
    }

    public function toggleVisibility(User $user)
    {
        $user->is_visible = !$user->is_visible;
        $user->save();

        Toast::success('Visibility updated.');
    }
}
