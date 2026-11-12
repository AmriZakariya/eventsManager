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
        // Registrations = everyone who signed up as a visitor or exhibitor
        // (same total as the users list, admins excluded).
        $roleCounts = User::query()
            ->whereIn('app_role', [User::APP_ROLE_VISITOR, User::APP_ROLE_EXHIBITOR])
            ->selectRaw('app_role, count(*) as total')
            ->groupBy('app_role')
            ->pluck('total', 'app_role');
        $visitorCount       = (int) ($roleCounts[User::APP_ROLE_VISITOR] ?? 0);
        $exhibitorUserCount = (int) ($roleCounts[User::APP_ROLE_EXHIBITOR] ?? 0);
        $registrationCount  = $visitorCount + $exhibitorUserCount;
        $exhibitorCount     = Company::count();
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

        // ── Meetings on the event days ────────────────────────────────
        $eventMeetings = $this->eventMeetings($settings, $eventStatus);

        // ── Content & Network ─────────────────────────────────────────
        $conferenceCount    = Conference::count();
        $speakerCount       = Speaker::count();
        $productCount       = DB::table('products')->count();
        $connectionCount    = DB::table('connections')->where('status', 'accepted')->count();
        $pendingConnections = DB::table('connections')->where('status', 'pending')->count();

        $checkedInToday = User::query()
            ->whereIn('app_role', [User::APP_ROLE_VISITOR, User::APP_ROLE_EXHIBITOR])
            ->whereDate('created_at', today())
            ->count();

        // ── Chart: Registrations (last 14 days), visitors and exhibitors ──
        $last14 = collect(range(13, 0))->map(fn($d) => now()->subDays($d)->format('Y-m-d'));
        $dailyByRole = fn (string $role) => User::appRole($role)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->pluck('count', 'date');
        $rawVisitors   = $dailyByRole(User::APP_ROLE_VISITOR);
        $rawExhibitors = $dailyByRole(User::APP_ROLE_EXHIBITOR);
        $visitorChartLabels   = $last14->map(fn($d) => Carbon::parse($d)->format('d M'))->values()->toArray();
        $visitorChartValues   = $last14->map(fn($d) => (int) ($rawVisitors[$d] ?? 0))->values()->toArray();
        $exhibitorChartValues = $last14->map(fn($d) => (int) ($rawExhibitors[$d] ?? 0))->values()->toArray();

        // Index of the event's first day in the 14-day registration chart (marker line).
        $eventStartIndex = $settings?->start_date
            ? $last14->search(Carbon::parse($settings->start_date)->format('Y-m-d'))
            : false;
        $eventStartIndex = $eventStartIndex === false ? null : $eventStartIndex;

        // ── Chart: New registrations per week (last 8 weeks) ──────────
        $weeklyLabels = [];
        $weeklyValues = [];
        $weeklyExhibitorValues = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart      = now()->startOfWeek()->subWeeks($i);
            $weekEnd        = (clone $weekStart)->endOfWeek();
            $weeklyLabels[] = 'W' . $weekStart->week;
            $weekCounts = User::query()
                ->whereIn('app_role', [User::APP_ROLE_VISITOR, User::APP_ROLE_EXHIBITOR])
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->selectRaw('app_role, count(*) as total')
                ->groupBy('app_role')
                ->pluck('total', 'app_role');
            $weeklyValues[]          = (int) ($weekCounts[User::APP_ROLE_VISITOR] ?? 0);
            $weeklyExhibitorValues[] = (int) ($weekCounts[User::APP_ROLE_EXHIBITOR] ?? 0);
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

        // ── App users by platform (android / ios, recorded from the app) ──
        $platformCounts = User::query()
            ->whereIn('app_role', [User::APP_ROLE_VISITOR, User::APP_ROLE_EXHIBITOR])
            ->selectRaw("coalesce(platform, 'unknown') as p, count(*) as total")
            ->groupBy('p')
            ->pluck('total', 'p');

        return compact(
            'eventName', 'eventStatus', 'daysUntil', 'hoursUntil', 'minutesUntil', 'daysProgress', 'settings',
            'visitorCount', 'exhibitorCount', 'exhibitorUserCount', 'registrationCount',
            'unreadMessages', 'totalMeetings',
            'pendingMeetings', 'confirmedMeetings', 'completedMeetings', 'cancelledMeetings',
            'todayMeetings', 'checkedInToday',
            'conferenceCount', 'speakerCount', 'productCount', 'connectionCount', 'pendingConnections',
            'visitorChartLabels', 'visitorChartValues', 'exhibitorChartValues',
            'eventMeetings', 'eventStartIndex',
            'weeklyLabels', 'weeklyValues', 'weeklyExhibitorValues',
            'donutLabels', 'donutValues',
            'hourlyLabels', 'hourlyValues',
            'platformCounts'
        );
    }

    /**
     * Meetings grouped by event day (and by hour within each day), for the
     * Meeting Volume chart. Cancelled/declined meetings are left out; active
     * meetings booked outside the event dates are only counted.
     * Times are compared as stored (wall-clock), like the rest of the app.
     */
    private function eventMeetings(?EventSetting $settings, string $eventStatus): array
    {
        $empty = [
            'configured' => false, 'days' => [], 'hours' => [],
            'total' => 0, 'outside' => 0, 'focus' => null, 'currentDay' => null,
        ];
        if (!$settings?->start_date || !$settings?->end_date) {
            return $empty;
        }

        $firstDay = Carbon::parse($settings->start_date)->startOfDay();
        $lastDay  = Carbon::parse($settings->end_date)->startOfDay();
        if ($lastDay->lt($firstDay)) {
            $lastDay = $firstDay->copy();
        }
        // A show lasts a few days; cap to keep the chart readable.
        if ($firstDay->diffInDays($lastDay) > 13) {
            $lastDay = $firstDay->copy()->addDays(13);
        }

        $days = [];
        for ($d = $firstDay->copy(), $n = 1; $d->lte($lastDay); $d->addDay(), $n++) {
            $days[$d->format('Y-m-d')] = [
                'date'      => $d->format('Y-m-d'),
                'label'     => 'Day ' . $n . ' · ' . $d->format('D d M'),
                'confirmed' => 0,
                'pending'   => 0,
                'completed' => 0,
                'total'     => 0,
                'hours'     => [],
            ];
        }

        $active = Appointment::query()
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->whereNotNull('scheduled_at');

        $outside = (clone $active)
            ->where(fn ($q) => $q->where('scheduled_at', '<', $firstDay)
                ->orWhere('scheduled_at', '>', $lastDay->copy()->endOfDay()))
            ->count();

        $rows = (clone $active)
            ->whereBetween('scheduled_at', [$firstDay, $lastDay->copy()->endOfDay()])
            ->get(['status', 'scheduled_at']);

        foreach ($rows as $row) {
            $at  = Carbon::parse($row->scheduled_at);
            $key = $at->format('Y-m-d');
            if (!isset($days[$key])) {
                continue;
            }
            $days[$key][$row->status]++;
            $days[$key]['total']++;
            $hour = (int) $at->format('G');
            $days[$key]['hours'][$hour] = ($days[$key]['hours'][$hour] ?? 0) + 1;
        }

        // Hour axis: the event's usual opening hours, widened to fit any booking.
        $usedHours = collect($days)->flatMap(fn ($d) => array_keys($d['hours']));
        $fromHour  = min(9, $usedHours->min() ?? 9);
        $toHour    = max(18, $usedHours->max() ?? 18);
        $hours     = range($fromHour, $toHour);
        foreach ($days as &$day) {
            $day['hours'] = array_map(fn ($h) => $day['hours'][$h] ?? 0, $hours);
        }
        unset($day);

        $total = (int) $rows->count();
        $today = today()->format('Y-m-d');
        $dayKeys = array_keys($days);
        $currentDay = $eventStatus === 'live' && isset($days[$today])
            ? array_search($today, $dayKeys, true) + 1
            : null;

        // Headline for the meetings KPI, depending on where we are in the event.
        $focus = match (true) {
            $currentDay !== null => [
                'value' => $days[$today]['total'],
                'label' => 'meetings today (day ' . $currentDay . ')',
            ],
            $eventStatus === 'ended' => [
                'value' => $total,
                'label' => 'meetings held during the event',
            ],
            default => [
                'value' => $days[$dayKeys[0]]['total'],
                'label' => 'booked for day 1',
            ],
        };

        return [
            'configured' => true,
            'days'       => array_values($days),
            'hours'      => array_map(fn ($h) => str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00', $hours),
            'total'      => $total,
            'outside'    => $outside,
            'focus'      => $focus,
            'currentDay' => $currentDay,
        ];
    }

    public function name(): ?string        { return 'Dashboard'; }
    public function description(): ?string { return null; }

    public function layout(): iterable
    {
        return [
            Layout::view('orchid.dashboard.main'),
        ];
    }
}
