<?php

namespace App\Orchid\Screens\Appointment;

use App\Models\Appointment;
use App\Models\EventSetting;
use App\Models\User;
use App\Models\Company;
use App\Orchid\Layouts\Appointment\AppointmentListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Group;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Carbon\Carbon;

class AppointmentListScreen extends Screen
{
    public function name(): ?string
    {
        return 'B2B Appointments';
    }

    public function description(): ?string
    {
        return 'Schedule and manage B2B appointments with calendar and list views.';
    }

    public function query(Request $request): iterable
    {
        $settings = EventSetting::first();
        $eventStartDate = $settings?->start_date
            ? Carbon::parse($settings->start_date)->format('Y-m-d')
            : 0;

        // 1. Statistics (Fast COUNT queries)
        $pending = Appointment::where('status', 'pending')->count();
        $confirmed = Appointment::where('status', 'confirmed')->count();
        $cancelled = Appointment::where('status', 'cancelled')->count();
        $total = Appointment::count();

        // 2. Table Query with filters (For List View)
        // 🚀 EAGER LOADING: Prevents the N+1 query problem with 50,000 rows
        $tableQuery = Appointment::with(['booker', 'targetUser', 'targetUser.company'])
            ->defaultSort('scheduled_at', 'desc');

        // Apply filters
        if ($search = $request->get('search')) {
            $tableQuery->where(function ($q) use ($search) {
                $q->whereHas('booker', fn($b) => $b->where('name', 'like', "%$search%"))
                    ->orWhereHas('targetUser', fn($t) => $t->where('name', 'like', "%$search%"))
                    ->orWhere('table_location', 'like', "%$search%");
            });
        }
        if ($status = $request->get('status')) {
            $tableQuery->where('status', $status);
        }
        if ($companyId = $request->get('company_id')) {
            $tableQuery->whereHas('targetUser', fn($u) => $u->where('company_id', $companyId));
        }
        if ($dateFrom = $request->get('date_from')) {
            $tableQuery->whereDate('scheduled_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $tableQuery->whereDate('scheduled_at', '<=', $dateTo);
        }

        // 3. ── FIX: FORMAT EVENTS FOR FULLCALENDAR ──
        // 🛡️ MEMORY PROTECTION: We limit the calendar to max 1000 events at a time
        // to prevent PHP/Browser crashes when dealing with 50,000+ entries.
        // The List View below remains fully paginated.
        $calendarQuery = clone $tableQuery;
        $allAppointments = $calendarQuery->limit(1000)->get();

        $calendarEvents = $allAppointments->map(function ($apt) {
            $colorMap = [
                'pending'   => '#f59e0b', // Amber
                'confirmed' => '#10b981', // Green
                'completed' => '#3b82f6', // Blue
                'cancelled' => '#ef4444', // Red
                'declined'  => '#64748b', // Slate
            ];

            $start = $apt->scheduled_at ? Carbon::parse($apt->scheduled_at) : now();
            $duration = $apt->duration_minutes ?: 30;
            $end = $start->copy()->addMinutes($duration);

            return [
                'id'              => $apt->id,
                'title'           => ($apt->booker->name ?? 'Visitor') . ' & ' . ($apt->targetUser->name ?? 'Exhibitor'),
                'start'           => $start->toIso8601String(),
                'end'             => $end->toIso8601String(),
                'backgroundColor' => $colorMap[$apt->status] ?? '#64748b',
                'borderColor'     => $colorMap[$apt->status] ?? '#64748b',
                'extendedProps'   => [
                    'appointmentId' => $apt->id,
                    'booker'        => $apt->booker->name ?? 'N/A',
                    'target'        => $apt->targetUser->name ?? 'N/A',
                    'company'       => $apt->targetUser->company->name ?? '',
                    'location'      => $apt->table_location ?? 'TBD',
                    'duration'      => $duration,
                    'status'        => $apt->status,
                ]
            ];
        })->toArray();

        // 4. Return everything to the views
        return [
            // 🚀 STRICT PAGINATION: Only loads 30 rows in memory per page
            'appointments' => $tableQuery->paginate(30),
            'metrics' => [
                'pending'   => ['value' => number_format($pending)],
                'confirmed' => ['value' => number_format($confirmed)],
                'cancelled' => ['value' => number_format($cancelled)],
                'total'     => ['value' => number_format($total)],
            ],
            'calendarEvents' => $calendarEvents,
            'eventStartDate' => $eventStartDate,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Export CSV')
                ->icon('bs.download')
                ->method('exportCalendar')
                ->rawClick()
                ->novalidate(),

            ModalToggle::make('Book Meeting')
                ->modal('bookMeetingModal')
                ->icon('bs.calendar-plus')
                ->type(Color::PRIMARY),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Metrics
            Layout::metrics([
                'Pending'   => 'metrics.pending',
                'Confirmed' => 'metrics.confirmed',
                'Cancelled' => 'metrics.cancelled',
                'Total'     => 'metrics.total',
            ]),

            // Filters
            Layout::columns([
                Layout::rows([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Search by name or location...')
                        ->value(request('search')),

                    Select::make('status')
                        ->title('Status')
                        ->empty('All Statuses', '')
                        ->options([
                            'pending'   => 'Pending',
                            'confirmed' => 'Confirmed',
                            'cancelled' => 'Cancelled',
                            'completed' => 'Completed',
                            'declined'  => 'Declined',
                        ])
                        ->value(request('status')),
                ])->title(''),

                Layout::rows([
                    Relation::make('company_id')
                        ->title('Company')
                        ->fromModel(Company::class, 'name')
                        ->empty('All Companies', '')
                        ->value(request('company_id')),

                    DateTimer::make('date_from')
                        ->title('From Date')
                        ->format('Y-m-d')
                        ->allowInput()
                        ->value(request('date_from')),
                ])->title(''),

                Layout::rows([
                    DateTimer::make('date_to')
                        ->title('To Date')
                        ->format('Y-m-d')
                        ->allowInput()
                        ->value(request('date_to')),

                    Group::make([
                        Button::make('Apply')
                            ->icon('bs.funnel-fill')
                            ->method('applyFilters')
                            ->type(Color::PRIMARY())
                            ->class('btn btn-primary mt-4'),

                        Button::make('Clear Filters')
                            ->icon('bs.x-circle')
                            ->method('clearFilters')
                            ->class('btn btn-outline-secondary'),
                    ])->autoWidth(),
                ])->title(''),
            ]),

            // Tabs
            Layout::tabs([
                'List View' => [
                    AppointmentListLayout::class,
                ],
                'Calendar View' => [
                    Layout::view('admin.appointment.calendar'),
                ],
            ]),

            // Create Modal
            Layout::modal('bookMeetingModal', Layout::rows([
                Relation::make('appointment.booker_id')
                    ->title('Visitor (Booker)')
                    ->fromModel(User::class, 'email') // Use 'name' or 'email' depending on your model
                    ->required()
                    ->help('Select the visitor booking the appointment'),

                Relation::make('appointment.target_user_id')
                    ->title('Exhibitor (Target)')
                    ->fromModel(User::class, 'email')
                    ->required()
                    ->help('Select the exhibitor to meet'),

                DateTimer::make('appointment.scheduled_at')
                    ->title('Date & Time')
                    ->format('Y-m-d H:i:s')
                    ->enableTime()
                    ->required(),

                Input::make('appointment.duration_minutes')
                    ->title('Duration (Minutes)')
                    ->type('number')
                    ->value(30)
                    ->min(15)
                    ->max(240)
                    ->required(),

                Input::make('appointment.table_location')
                    ->title('Location')
                    ->placeholder('e.g., Booth A12'),

                TextArea::make('appointment.notes')
                    ->title('Notes')
                    ->rows(3),
            ]))
                ->title('Schedule New Meeting')
                ->applyButton('Book Appointment')
                ->method('createAppointment'),

            // Edit Modal
            Layout::modal('editAppointmentModal', Layout::rows([
                Input::make('appointment.id')
                    ->type('hidden'),

                Input::make('appointment_display')
                    ->title('Meeting')
                    ->disabled(),

                Select::make('appointment.status')
                    ->title('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'declined'  => 'Declined',
                    ])
                    ->required(),

                DateTimer::make('appointment.scheduled_at')
                    ->title('Date & Time')
                    ->format('Y-m-d H:i:s')
                    ->enableTime()
                    ->required(),

                Input::make('appointment.duration_minutes')
                    ->title('Duration (Minutes)')
                    ->type('number')
                    ->min(15)
                    ->max(240),

                Input::make('appointment.table_location')
                    ->title('Location'),

                TextArea::make('appointment.notes')
                    ->title('Notes')
                    ->rows(3),
            ]))
                ->title('Edit Appointment')
                ->async('asyncGetAppointment')
                ->applyButton('Save Changes')
                ->method('updateAppointment'),
        ];
    }

    // Async Load
    public function asyncGetAppointment(Appointment $appointment): array
    {
        $appointment->load(['booker', 'targetUser', 'targetUser.company']);

        $display = sprintf(
            '%s ↔ %s%s',
            $appointment->booker->name ?? 'Unknown',
            $appointment->targetUser->name ?? 'Unknown',
            $appointment->targetUser->company ? ' (' . $appointment->targetUser->company->name . ')' : ''
        );

        return [
            'appointment' => $appointment,
            'appointment_display' => $display,
        ];
    }

    // Actions
    public function createAppointment(Request $request)
    {
        $validated = $request->validate([
            'appointment.booker_id' => 'required|exists:users,id',
            'appointment.target_user_id' => 'required|exists:users,id|different:appointment.booker_id',
            'appointment.scheduled_at' => 'required|date|after:now',
            'appointment.duration_minutes' => 'required|integer|min:15|max:240',
            'appointment.table_location' => 'nullable|string|max:255',
            'appointment.notes' => 'nullable|string|max:1000',
        ]);

        $validated['appointment']['status'] = 'confirmed';

        Appointment::create($validated['appointment']);

        Toast::success('Appointment created successfully!');
    }

    public function updateAppointment(Request $request)
    {
        $validated = $request->validate([
            'appointment.id' => 'required|exists:appointments,id',
            'appointment.status' => 'required|in:pending,confirmed,cancelled,completed,declined',
            'appointment.scheduled_at' => 'required|date',
            'appointment.duration_minutes' => 'nullable|integer|min:15|max:240',
            'appointment.table_location' => 'nullable|string|max:255',
            'appointment.notes' => 'nullable|string|max:1000',
        ]);

        $appointment = Appointment::findOrFail($validated['appointment']['id']);
        $appointment->update($validated['appointment']);

        Toast::info('Appointment updated successfully!');
    }

    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.appointments', array_filter([
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'company_id' => $request->get('company_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ]));
    }

    public function clearFilters()
    {
        Toast::info('Filters cleared');
        return redirect()->route('platform.appointments');
    }

    public function exportCalendar(Request $request)
    {
        // 🚀 EAGER LOADING used here too for fast CSV Generation
        $query = Appointment::with(['booker', 'targetUser', 'targetUser.company'])
            ->orderBy('scheduled_at');

        // Apply filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('booker', fn($b) => $b->where('name', 'like', "%$search%"))
                    ->orWhereHas('targetUser', fn($t) => $t->where('name', 'like', "%$search%"))
                    ->orWhere('table_location', 'like', "%$search%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($companyId = $request->get('company_id')) {
            $query->whereHas('targetUser', fn($u) => $u->where('company_id', $companyId));
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        $filename = 'appointments_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Date', 'Time', 'Duration (min)',
                'Visitor', 'Exhibitor', 'Company',
                'Location', 'Status', 'Notes'
            ]);

            // Memory-safe chunking handles 50,000 rows without RAM issues
            $query->chunk(500, function ($appointments) use ($handle) {
                foreach ($appointments as $apt) {
                    fputcsv($handle, [
                        $apt->id,
                        $apt->scheduled_at ? $apt->scheduled_at->format('Y-m-d') : '',
                        $apt->scheduled_at ? $apt->scheduled_at->format('H:i') : '',
                        $apt->duration_minutes,
                        $apt->booker->name ?? 'N/A',
                        $apt->targetUser->name ?? 'N/A',
                        $apt->targetUser->company->name ?? 'N/A',
                        $apt->table_location ?? 'TBD',
                        strtoupper($apt->status),
                        $apt->notes ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename);
    }
}
