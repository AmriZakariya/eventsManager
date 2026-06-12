<?php

namespace App\Orchid\Layouts\Appointment;

use App\Models\Appointment;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Button;
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
                        '<div class="d-flex align-items-center">
                            <i class="%s me-2 text-muted"></i>
                            <div>
                                <div class="fw-bold">%s</div>
                                <small class="text-muted">%s</small>
                            </div>
                        </div>',
                        $icon,
                        $apt->scheduled_at->format('M d, Y'),
                        $apt->scheduled_at->format('h:i A')
                    );
                }),

            TD::make('booker', 'Visitor')
                ->render(function (Appointment $apt) {
                    $initial = strtoupper(substr($apt->booker->name ?? '?', 0, 1));
                    $profileBadge = $apt->booker?->profileCompletionBadgeHtml('ms-1') ?? '';

                    return sprintf(
                        '<div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 14px;">
                                %s
                            </div>
                            <div>%s %s</div>
                        </div>',
                        $initial,
                        e($apt->booker->full_name ?? '-'),
                        $profileBadge
                    );
                }),

            TD::make('targetUser', 'Exhibitor')
                ->render(function (Appointment $apt) {
                    $initial = strtoupper(substr($apt->targetUser->name ?? '?', 0, 1));
                    $companyBadge = $apt->targetUser->company
                        ? '<div><small class="badge bg-light text-dark border">' . e($apt->targetUser->company->name) . '</small></div>'
                        : '';
                    $profileBadge = $apt->targetUser?->profileCompletionBadgeHtml('ms-1') ?? '';

                    return sprintf(
                        '<div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 14px;">
                                %s
                            </div>
                            <div>
                                <div>%s %s</div>
                                %s
                            </div>
                        </div>',
                        $initial,
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

            TD::make('Actions')
                ->alignRight()
                ->width('150px')
                ->render(function (Appointment $apt) {
                    $editButton = ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editAppointmentModal')
                        ->modalTitle('Edit Appointment')
                        ->asyncParameters(['appointment' => $apt->id])
                        ->class('btn btn-sm btn-outline-primary');

                    // Quick action button based on status
                    $quickAction = '';
                    if ($apt->status === 'pending') {
                        $quickAction = Button::make('Confirm')
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
                            ])
                            ->class('btn btn-sm btn-success ms-1');
                    } elseif ($apt->status === 'confirmed' && $apt->scheduled_at->isPast()) {
                        $quickAction = Button::make('Complete')
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
                            ])
                            ->class('btn btn-sm btn-info ms-1');
                    }

                    return sprintf(
                        '<div class="btn-group" role="group">%s%s</div>',
                        $editButton,
                        $quickAction
                    );
                }),
        ];
    }
}
