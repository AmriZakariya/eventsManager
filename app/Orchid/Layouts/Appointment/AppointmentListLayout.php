<?php

namespace App\Orchid\Layouts\Appointment;

use App\Models\Appointment;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Support\Color;

class AppointmentListLayout extends Table
{
    protected $target = 'appointments';

    protected function columns(): iterable
    {
        return [
            TD::make('scheduled_at', 'Date & Time')
                ->sort()
                ->render(function (Appointment $apt) {
                    $icon = $apt->scheduled_at->isFuture() ? 'bs.calendar-event' : 'bs.calendar-check';

                    return sprintf(
                        '<div class="d-flex align-items-center appointment-row-link" data-detail-url="%s" title="Double-click to open details">
                            <i class="%s me-2 text-muted"></i>
                            <div>
                                <div class="fw-bold">%s</div>
                                <small class="text-muted">%s</small>
                            </div>
                        </div>',
                        route('platform.appointments.detail', $apt),
                        $icon,
                        $apt->scheduled_at->format('M d, Y'),
                        $apt->scheduled_at->format('h:i A')
                    );
                }),

            TD::make('booker', 'Visitor')
                ->render(function (Appointment $apt) {
                    if (!$apt->booker) {
                        return '<span class="text-muted">—</span>';
                    }
                    $avatar = $apt->booker->avatarThumbHtml(32, 'bg-primary');
                    $profileBadge = $apt->booker->profileCompletionBadgeHtml('ms-1');

                    return sprintf(
                        '<div class="d-flex align-items-center">
                            <span class="me-2">%s</span>
                            <div>%s %s</div>
                        </div>',
                        $avatar,
                        e($apt->booker->full_name ?? '-'),
                        $profileBadge
                    );
                }),

            TD::make('targetUser', 'Exhibitor')
                ->render(function (Appointment $apt) {
                    if (!$apt->targetUser) {
                        return '<span class="text-muted">—</span>';
                    }
                    $avatar = $apt->targetUser->avatarThumbHtml(32, 'bg-success');
                    $companyBadge = $apt->targetUser->company
                        ? '<div><small class="badge bg-light text-dark border">' . e($apt->targetUser->company->name) . '</small></div>'
                        : '';
                    $profileBadge = $apt->targetUser->profileCompletionBadgeHtml('ms-1');

                    return sprintf(
                        '<div class="d-flex align-items-center">
                            <span class="me-2">%s</span>
                            <div>
                                <div>%s %s</div>
                                %s
                            </div>
                        </div>',
                        $avatar,
                        e($apt->targetUser->full_name ?? '-'),
                        $profileBadge,
                        $companyBadge
                    );
                }),

//            TD::make('duration_minutes', 'Duration')
//                ->render(function (Appointment $apt) {
//                    $duration = $apt->duration_minutes ?? 30;
//                    $hours = floor($duration / 60);
//                    $minutes = $duration % 60;
//
//                    if ($hours > 0) {
//                        return sprintf('<span class="text-muted"><i class="bs.clock me-1"></i>%dh %dm</span>', $hours, $minutes);
//                    }
//
//                    return sprintf('<span class="text-muted"><i class="bs.clock me-1"></i>%d min</span>', $minutes);
//                }),

            TD::make('status', 'Status')
                ->sort()
                ->render(function (Appointment $apt) {
                    $color = match ($apt->status) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        'declined' => 'secondary',
                        default => 'light',
                    };

                    return ModalToggle::make(strtoupper($apt->status))
                        ->modal('editAppointmentModal')
                        ->modalTitle('Edit Appointment')
                        ->asyncParameters(['appointment' => $apt->id])
                        ->class("badge bg-$color text-white border-0")
                        ->style('cursor: pointer;');
                }),

            TD::make('table_location', 'Location')
                ->render(function (Appointment $apt) {
                    if (empty($apt->table_location)) {
                        return '<span class="text-muted fst-italic"><i class="bs.geo-alt me-1"></i>TBD</span>';
                    }

                    return sprintf(
                        '<span><i class="bs.geo-alt-fill me-1 text-primary"></i>%s</span>',
                        e($apt->table_location)
                    );
                }),

            TD::make('created_at', 'Created')
                ->sort()
                ->width('140px')
                ->render(function (Appointment $apt) {
                    if (!$apt->created_at) {
                        return '<span class="text-muted">—</span>';
                    }

                    return sprintf(
                        '<div><div class="fw-semibold" style="font-size:0.8rem;">%s</div>'
                        . '<small class="text-muted">(%s)</small></div>',
                        $apt->created_at->diffForHumans(),
                        $apt->created_at->format('M d, Y H:i')
                    );
                }),

            TD::make('Actions')
                ->alignRight()
                ->width('80px')
                ->render(function (Appointment $apt) {
                    $actions = [];

                    $actions[] = ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editAppointmentModal')
                        ->modalTitle('Edit Appointment')
                        ->asyncParameters(['appointment' => $apt->id]);

                    // Quick action based on status
                    if ($apt->status === 'pending') {
                        $actions[] = Button::make('Confirm')
                            ->icon('bs.check-lg')
                            ->confirm('Confirm this appointment?')
                            ->method('updateAppointment', [
                                'appointment' => [
                                    'id' => $apt->id,
                                    'status' => 'confirmed',
                                    'scheduled_at' => $apt->scheduled_at->format('Y-m-d H:i:s'),
                                    'duration_minutes' => $apt->duration_minutes,
                                    'table_location' => $apt->table_location,
                                    'notes' => $apt->notes,
                                ]
                            ]);
                    } elseif ($apt->status === 'confirmed' && $apt->scheduled_at->isPast()) {
                        $actions[] = Button::make('Complete')
                            ->icon('bs.check2-all')
                            ->confirm('Mark as completed?')
                            ->method('updateAppointment', [
                                'appointment' => [
                                    'id' => $apt->id,
                                    'status' => 'completed',
                                    'scheduled_at' => $apt->scheduled_at->format('Y-m-d H:i:s'),
                                    'duration_minutes' => $apt->duration_minutes,
                                    'table_location' => $apt->table_location,
                                    'notes' => $apt->notes,
                                ]
                            ]);
                    }

                    $actions[] = Button::make('Delete')
                        ->icon('bs.trash3')
                        ->confirm('Delete this meeting permanently? This cannot be undone.')
                        ->method('deleteAppointment', ['id' => $apt->id]);

                    return DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list($actions);
                }),
        ];
    }
}
