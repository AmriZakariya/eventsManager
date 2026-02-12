<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Color;
use Illuminate\Support\Facades\DB;

// Models
use App\Models\User;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\EventSetting; // Changed from Event

// Layouts
use App\Orchid\Layouts\Dashboard\RegistrationLineChart;

class PlatformScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        // 1. Get Global Settings (Single Event Logic)
        $settings = EventSetting::first();
        $eventName = $settings ? $settings->event_name : '⚠️ Event Not Configured';

        // 2. Calculate KPIs
        // Visitors: Users with 'visitor' role
        $visitorCount = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))->count();

        // Exhibitors: Total Companies
        $exhibitorCount = Company::count();

        // 3. Action Items
        // B2B: Appointments in 'pending' status (Table is now exclusive to B2B)
        $pendingMeetings = Appointment::where('status', 'pending')->count();

        // Inbox: Unhandled support requests
        $unreadMessages = ContactRequest::where('is_handled', false)->count();

        // 4. Chart Data: New Visitors (Last 14 Days)
        $visitorChartData = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'event_status'   => $eventName,
            'visitor_count'  => $visitorCount,
            'exhibitor_count'=> $exhibitorCount,
            'pending_b2b'    => $pendingMeetings,
            'unread_msg'     => $unreadMessages,

            // Data for the RegistrationLineChart
            'visitor_growth' => [
                [
                    'name'   => 'New Registrations',
                    'values' => $visitorChartData,
                    'labels' => array_keys($visitorChartData),
                ],
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Command Center';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Overview of Hygie Clean Expo logistics and attendance.';
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            // ROW 1: Event Status Banner
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('event_status')
                    ->title('Active Event Profile')
                    ->popover('To change this name, go to Event Configuration.'),
            ]),

            // ROW 2: The 4 Key Metrics
            Layout::metrics([
                'Total Visitors'    => 'visitor_count',
                'Exhibitors'        => 'exhibitor_count',
                'Pending Meetings'  => 'pending_b2b', // Needs attention
                'Inbox Messages'    => 'unread_msg',  // Needs attention
            ]),

            // ROW 3: Charts and Quick Links
            Layout::columns([
                // Left Column: The Analytics Chart
                RegistrationLineChart::class,

                // Right Column: Quick Action Buttons
                Layout::rows([
                    Link::make('Manage Appointments')
                        ->icon('bs.briefcase')
                        ->route('platform.appointments') // Route verified
                        ->type(Color::INFO)
                        ->block(),

                    Link::make('Go to Inbox')
                        ->icon('bs.envelope')
                        ->route('platform.contacts') // Route verified
                        ->type(Color::DANGER)
                        ->block(),

                    Link::make('Manage Companies')
                        ->icon('bs.building')
                        ->route('platform.companies.list') // Route verified (Updated)
                        ->type(Color::PRIMARY)
                        ->block(),
                ])->title('Quick Actions'),
            ]),
        ];
    }
}
