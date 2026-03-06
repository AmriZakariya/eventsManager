<?php

namespace App\Orchid\Screens\Appointment;

use App\Models\Appointment;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Carbon\Carbon;

class AppointmentDetailScreen extends Screen
{
    public $appointment;

    public function query(Appointment $appointment): iterable
    {
        // Load relationships
        $appointment->load(['booker', 'targetUser.company']);

        return [
            'appointment' => $appointment,
            'timeline'    => $this->generateTimeline($appointment),
        ];
    }

    public function name(): ?string
    {
        return 'Meeting Details';
    }

    public function description(): ?string
    {
        return 'View complete information, participants, and history for this meeting.';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Edit Meeting')
                ->icon('bs.pencil')
                ->route('platform.appointments') // You can update this to an edit screen if you build one later
                ->class('btn btn-outline-primary me-2'),

            Link::make('Back to Calendar')
                ->icon('bs.arrow-left')
                ->route('platform.appointments'),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Inject a little custom CSS just for these rich cards
            Layout::view('admin.appointment.detail-styles'),

            Layout::columns([
                // Left Column: Meeting Details
                Layout::legend('appointment', [

                    Sight::make('status', 'Current Status')->render(function ($apt) {
                        $colors = [
                            'pending'   => 'warning',
                            'confirmed' => 'success',
                            'completed' => 'primary',
                            'cancelled' => 'danger',
                            'declined'  => 'secondary',
                        ];
                        $color = $colors[$apt->status] ?? 'light';
                        return "<div class='mb-2 mt-1'><span class='badge bg-{$color} text-uppercase px-3 py-2' style='font-size: 12px; letter-spacing: 0.5px;'><i class='bi bi-record-circle-fill me-1'></i> {$apt->status}</span></div>";
                    }),

                    Sight::make('scheduled_at', 'Date & Time')->render(fn($apt) =>
                        "<div class='fs-6 mt-1 text-dark'>
                            <i class='bi bi-calendar2-week text-primary me-2'></i>" .
                        Carbon::parse($apt->scheduled_at)->format('l, F j, Y') .
                        "<br>
                            <i class='bi bi-clock text-primary me-2 mt-2'></i>" .
                        Carbon::parse($apt->scheduled_at)->format('g:i A') .
                        " <span class='text-muted small'>(" . ($apt->duration_minutes ?? 30) . " mins)</span>
                        </div>"
                    ),

                    Sight::make('table_location', 'Location')->render(fn($apt) =>
                        "<div class='fs-6 mt-1 text-dark'>
                            <i class='bi bi-geo-alt-fill text-danger me-2'></i>" .
                        ($apt->table_location ?? '<span class="text-muted fst-italic">To be determined</span>') .
                        "</div>"
                    ),

                    Sight::make('booker_id', 'Visitor (Requested By)')->render(function($apt) {
                        $user = $apt->booker;
                        if (!$user) return '<span class="text-muted">Unknown User</span>';

                        $url = route('platform.systems.users.edit', $user->id);
                        $initial = strtoupper(substr($user->name, 0, 1));

                        return "
                        <a href='{$url}' class='d-flex align-items-center text-decoration-none p-2 rounded participant-card'>
                            <div class='avatar-circle bg-primary text-white me-3'>{$initial}</div>
                            <div>
                                <strong class='d-block text-dark fs-6'>{$user->name}</strong>
                                <span class='text-primary small fw-semibold'>View Profile &rarr;</span>
                            </div>
                        </a>";
                    }),

                    Sight::make('target_user_id', 'Exhibitor (Target)')->render(function($apt) {
                        $user = $apt->targetUser;
                        if (!$user) return '<span class="text-muted">Unknown User</span>';

                        $url = route('platform.systems.users.edit', $user->id);
                        $company = $user->company->name ?? 'Independent';

                        return "
                        <a href='{$url}' class='d-flex align-items-center text-decoration-none p-2 rounded participant-card'>
                            <div class='avatar-circle bg-success text-white me-3'><i class='bi bi-shop'></i></div>
                            <div>
                                <strong class='d-block text-dark fs-6'>{$user->name}</strong>
                                <span class='text-muted small'><i class='bi bi-building me-1'></i> {$company}</span>
                            </div>
                        </a>";
                    }),

                    Sight::make('notes', 'Meeting Notes')->render(fn($apt) =>
                        "<div class='p-3 bg-light rounded border text-secondary mt-1' style='font-size: 14px;'>" .
                        ($apt->notes ? nl2br(e($apt->notes)) : '<em>No additional notes provided for this meeting.</em>') .
                        "</div>"
                    ),
                ])->title('Meeting Overview'),

                // Right Column: Timeline View
                Layout::view('admin.appointment.timeline'),
            ]),
        ];
    }

    private function generateTimeline(Appointment $apt): array
    {
        $timeline = [];

        $timeline[] = [
            'title'       => 'Appointment Requested',
            'description' => 'Meeting request was created in the system.',
            'date'        => $apt->created_at->diffForHumans(),
            'icon'        => 'bi-calendar-plus',
            'color'       => '#3b82f6',
        ];

        if ($apt->updated_at->gt($apt->created_at->addSeconds(10))) {
            $isFinal = in_array($apt->status, ['confirmed', 'cancelled', 'completed', 'declined']);
            $timeline[] = [
                'title'       => 'Status Updated',
                'description' => "Status was marked as <strong>" . ucfirst($apt->status) . "</strong>.",
                'date'        => $apt->updated_at->diffForHumans(),
                'icon'        => $isFinal ? 'bi-check-circle' : 'bi-arrow-repeat',
                'color'       => $apt->status === 'cancelled' ? '#ef4444' : ($apt->status === 'confirmed' ? '#10b981' : '#f59e0b'),
            ];
        }

        $scheduledTime = Carbon::parse($apt->scheduled_at);
        if ($scheduledTime->isFuture() && $apt->status === 'confirmed') {
            $timeline[] = [
                'title'       => 'Scheduled to occur',
                'description' => 'Waiting for the meeting to take place.',
                'date'        => 'In ' . clone $scheduledTime->diffForHumans(null, true),
                'icon'        => 'bi-hourglass-split',
                'color'       => '#94a3b8',
                'is_future'   => true,
            ];
        }

        return array_reverse($timeline);
    }
}
