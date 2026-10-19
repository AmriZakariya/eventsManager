<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * User registrations per day and per week, with running totals.
 * Used by the admin "Registrations" page and its CSV export.
 */
class RegistrationStats
{
    public const TYPES = [
        User::APP_ROLE_VISITOR   => 'Visitors',
        User::APP_ROLE_EXHIBITOR => 'Exhibitors',
        'all'                    => 'All users',
    ];

    /** Longest range shown, so a typo in the year cannot load decades of days. */
    private const MAX_DAYS = 1100;

    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $type,
    ) {
    }

    /**
     * Builds the stats from request values; missing values default to the
     * first registration of that type up to today.
     */
    public static function fromInput(?string $from, ?string $to, ?string $type): self
    {
        $type = array_key_exists((string) $type, self::TYPES) ? $type : User::APP_ROLE_VISITOR;

        $to = self::parseDate($to) ?? now();
        $first = self::query($type)->min('created_at');
        $from = self::parseDate($from) ?? ($first ? Carbon::parse($first) : $to->copy()->subDays(29));

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > self::MAX_DAYS) {
            $from = $to->copy()->subDays(self::MAX_DAYS);
        }

        return new self($from->copy()->startOfDay(), $to->copy()->endOfDay(), $type);
    }

    /**
     * One row per day in the range (days without registrations included).
     *
     * @return array<int, array{date: string, label: string, weekday: string, count: int, total: int}>
     */
    public function daily(): array
    {
        $counts = self::query($this->type)
            ->whereBetween('created_at', [$this->from, $this->to])
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->pluck('count', 'day');

        $total = self::query($this->type)->where('created_at', '<', $this->from)->count();
        $rows = [];

        for ($day = $this->from->copy(); $day->lte($this->to); $day->addDay()) {
            $key = $day->format('Y-m-d');
            $count = (int) ($counts[$key] ?? 0);
            $total += $count;

            $rows[] = [
                'date'    => $key,
                'label'   => $day->format('d M Y'),
                'weekday' => $day->format('l'),
                'count'   => $count,
                'total'   => $total,
            ];
        }

        return $rows;
    }

    /**
     * Monday-to-Sunday weeks built from the daily rows. The first and last
     * weeks can be partial when the range starts or ends mid-week.
     *
     * @return array<int, array{week: string, start: string, end: string, range: string, count: int, total: int, partial: bool}>
     */
    public function weekly(?array $daily = null): array
    {
        $weeks = [];

        foreach ($daily ?? $this->daily() as $row) {
            $date = Carbon::parse($row['date']);
            $key = $date->format('o-\WW');

            $weeks[$key] ??= [
                'week'  => 'W' . $date->isoWeek() . ' ' . $date->isoWeekYear(),
                'start' => $date->copy()->startOfWeek()->format('Y-m-d'),
                'end'   => $date->copy()->endOfWeek()->format('Y-m-d'),
                'count' => 0,
                'total' => 0,
                'days'  => 0,
            ];

            $weeks[$key]['count'] += $row['count'];
            $weeks[$key]['total'] = $row['total'];
            $weeks[$key]['days']++;
        }

        return array_values(array_map(fn ($week) => [
            'week'    => $week['week'],
            'start'   => $week['start'],
            'end'     => $week['end'],
            'range'   => Carbon::parse($week['start'])->format('d M') . ' – ' . Carbon::parse($week['end'])->format('d M Y'),
            'count'   => $week['count'],
            'total'   => $week['total'],
            'partial' => $week['days'] < 7,
        ], $weeks));
    }

    private static function query(string $type)
    {
        return $type === 'all' ? User::query() : User::appRole($type);
    }

    private static function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
