<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use Carbon\Carbon;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\EventSetting;
use App\Models\Conference;
use App\Models\Speaker;

class PlatformScreen extends Screen
{
    public function query(): iterable
    {
        // ── Event Settings ─────────────────────────────────────────────
        $settings  = EventSetting::first();
        $eventName = $settings?->event_name ?? 'Event Not Configured';

        $eventStatus  = 'upcoming';
        $daysUntil    = null;
        $hoursUntil   = null;
        $minutesUntil = null;
        $daysProgress = 0;

        if ($settings?->start_date && $settings?->end_date) {
            $start = Carbon::parse($settings->start_date);
            $end   = Carbon::parse($settings->end_date);
            $now   = now();

            if ($now->lt($start)) {
                // Single diff object — days/hours/minutes always consistent
                $diff         = $now->diff($start);
                $eventStatus  = 'upcoming';
                $daysUntil    = $diff->days;   // total full days remaining
                $hoursUntil   = $diff->h;      // remaining hours after full days
                $minutesUntil = $diff->i;      // remaining minutes after full hours
            } elseif ($now->between($start, $end)) {
                $diff         = $now->diff($end);
                $eventStatus  = 'live';
                $daysUntil    = $diff->days;
                $hoursUntil   = $diff->h;
                $minutesUntil = $diff->i;
                $totalSeconds = $start->diffInSeconds($end) ?: 1;
                $elapsed      = $start->diffInSeconds($now);
                $daysProgress = min(100, (int) round(($elapsed / $totalSeconds) * 100));
            } else {
                $eventStatus = 'ended';
            }
        }

        // ── Core KPIs ─────────────────────────────────────────────────
        $visitorCount   = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))->count();
        $exhibitorCount = Company::count();
        $unreadMessages = ContactRequest::where('is_handled', false)->count();

        // ── Appointment Stats ─────────────────────────────────────────
        $apptStats = Appointment::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingMeetings   = (int) $apptStats->get('pending', 0);
        $confirmedMeetings = (int) $apptStats->get('confirmed', 0);
        $completedMeetings = (int) $apptStats->get('completed', 0);
        $cancelledMeetings = (int) ($apptStats->get('cancelled', 0) + $apptStats->get('declined', 0));
        $totalMeetings     = (int) $apptStats->sum();

        $todayMeetings = Appointment::whereDate('scheduled_at', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        // ── Content & Network ─────────────────────────────────────────
        $conferenceCount    = Conference::count();
        $speakerCount       = Speaker::count();
        $productCount       = DB::table('products')->count();
        $connectionCount    = DB::table('connections')->where('status', 'accepted')->count();
        $pendingConnections = DB::table('connections')->where('status', 'pending')->count();

        $checkedInToday = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))
            ->whereDate('created_at', today())
            ->count();

        // ── Chart: Visitor Registrations (last 14 days) ───────────────
        $last14 = collect(range(13, 0))->map(fn($d) => now()->subDays($d)->format('Y-m-d'));
        $rawVisitors = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->pluck('count', 'date');
        $visitorChartLabels = $last14->map(fn($d) => Carbon::parse($d)->format('d M'))->values()->toArray();
        $visitorChartValues = $last14->map(fn($d) => (int) ($rawVisitors[$d] ?? 0))->values()->toArray();

        // ── Chart: Appointments per day (last 14 days) ────────────────
        $rawAppts = Appointment::select(DB::raw('DATE(scheduled_at) as date'), DB::raw('count(*) as count'))
            ->where('scheduled_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->pluck('count', 'date');
        $apptChartLabels = $last14->map(fn($d) => Carbon::parse($d)->format('d M'))->values()->toArray();
        $apptChartValues = $last14->map(fn($d) => (int) ($rawAppts[$d] ?? 0))->values()->toArray();

        // ── Chart: Weekly cumulative growth (last 8 weeks) ───────────
        $weeklyLabels = [];
        $weeklyValues = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart      = now()->startOfWeek()->subWeeks($i);
            $weekEnd        = (clone $weekStart)->endOfWeek();
            $weeklyLabels[] = 'W' . $weekStart->week;
            $weeklyValues[] = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();
        }

        // ── Chart: Appointment status donut ───────────────────────────
        $donutLabels = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
        $donutValues = [$pendingMeetings, $confirmedMeetings, $completedMeetings, $cancelledMeetings];

        // ── Chart: Hourly registrations today ─────────────────────────
        // Uses strftime('%H') for SQLite compatibility (HOUR() is MySQL-only)
//        $hourlyRaw = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))
//            ->whereDate('created_at', today())
//            ->select(DB::raw("strftime('%H', created_at) as hour"), DB::raw('count(*) as count'))
//            ->groupBy('hour')
//            ->pluck('count', 'hour');
        // strftime returns zero-padded strings ('00'..'23'), cast keys to int for lookup
//        $hourlyRawInt = [];
//        foreach ($hourlyRaw as $h => $cnt) {
//            $hourlyRawInt[(int) $h] = (int) $cnt;
//        }
//        $hourlyLabels = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
//        $hourlyValues = array_map(fn($h) => $hourlyRawInt[$h] ?? 0, range(0, 23));

        $hourlyLabels = null;
        $hourlyValues = null;

        return compact(
            'eventName', 'eventStatus', 'daysUntil', 'hoursUntil', 'minutesUntil', 'daysProgress', 'settings',
            'visitorCount', 'exhibitorCount', 'unreadMessages', 'totalMeetings',
            'pendingMeetings', 'confirmedMeetings', 'completedMeetings', 'cancelledMeetings',
            'todayMeetings', 'checkedInToday',
            'conferenceCount', 'speakerCount', 'productCount', 'connectionCount', 'pendingConnections',
            'visitorChartLabels', 'visitorChartValues',
            'apptChartLabels', 'apptChartValues',
            'weeklyLabels', 'weeklyValues',
            'donutLabels', 'donutValues',
            'hourlyLabels', 'hourlyValues'
        );
    }

    public function name(): ?string        { return 'Command Center'; }
    public function description(): ?string { return null; }

    public function layout(): iterable
    {
        return [
            Layout::view('orchid.dashboard.main'),
        ];
    }
}
