<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Registrations;

use App\Services\RegistrationStats;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;

/**
 * Full history behind the dashboard's Daily and Weekly Registrations charts.
 */
class RegistrationStatsScreen extends Screen
{
    public function query(Request $request): iterable
    {
        $stats = $this->stats($request);
        $daily = $stats->daily();
        $weekly = $stats->weekly($daily);

        $total = array_sum(array_column($daily, 'count'));
        $bestDay = collect($daily)->sortByDesc('count')->first();
        $bestWeek = collect($weekly)->sortByDesc('count')->first();

        return [
            'filters' => [
                'from' => $stats->from->format('Y-m-d'),
                'to'   => $stats->to->format('Y-m-d'),
                'type' => $stats->type,
            ],
            'metrics' => [
                'total'    => ['value' => number_format($total)],
                'average'  => ['value' => count($daily) ? number_format($total / count($daily), 1) : '0'],
                'bestDay'  => ['value' => $bestDay && $bestDay['count'] > 0 ? $bestDay['count'] . ' · ' . $bestDay['label'] : '—'],
                'bestWeek' => ['value' => $bestWeek && $bestWeek['count'] > 0 ? $bestWeek['count'] . ' · ' . $bestWeek['week'] : '—'],
            ],
            'chart' => [
                'dailyLabels'  => array_column($daily, 'label'),
                'dailyValues'  => array_column($daily, 'count'),
                'weeklyLabels' => array_column($weekly, 'week'),
                'weeklyValues' => array_column($weekly, 'count'),
            ],
            // Newest first in the tables; the charts read left to right in time.
            'dailyRows'  => collect(array_reverse($daily))->map(fn ($row) => new Repository($row)),
            'weeklyRows' => collect(array_reverse($weekly))->map(fn ($row) => new Repository($row)),
        ];
    }

    public function name(): ?string
    {
        return 'Registrations';
    }

    public function description(): ?string
    {
        return 'New users per day and per week, with running totals.';
    }

    public function commandBar(): iterable
    {
        return [
            DropDown::make('Export CSV')
                ->icon('bs.download')
                ->list([
                    Button::make('Daily (CSV)')
                        ->icon('bs.calendar-day')
                        ->method('export', array_merge(request()->query(), ['period' => 'daily']))
                        ->rawClick(),
                    Button::make('Weekly (CSV)')
                        ->icon('bs.calendar-week')
                        ->method('export', array_merge(request()->query(), ['period' => 'weekly']))
                        ->rawClick(),
                ]),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Group::make([
                    DateTimer::make('filters.from')
                        ->title('From')
                        ->format('Y-m-d')
                        ->allowInput(),
                    DateTimer::make('filters.to')
                        ->title('To')
                        ->format('Y-m-d')
                        ->allowInput(),
                    Select::make('filters.type')
                        ->title('Users')
                        ->options(RegistrationStats::TYPES),
                    Button::make('Apply')
                        ->icon('bs.funnel')
                        ->type(Color::PRIMARY)
                        ->method('applyFilters'),
                ])->alignEnd(),
            ]),

            Layout::metrics([
                'Registrations'   => 'metrics.total',
                'Average per day' => 'metrics.average',
                'Best day'        => 'metrics.bestDay',
                'Best week'       => 'metrics.bestWeek',
            ]),

            Layout::view('admin.registrations.charts'),

            Layout::tabs([
                'Daily' => Layout::table('dailyRows', [
                    TD::make('label', 'Date'),
                    TD::make('weekday', 'Day'),
                    TD::make('count', 'New registrations')->alignRight()
                        ->render(fn (Repository $row) => $row->get('count') > 0
                            ? '<strong>' . $row->get('count') . '</strong>'
                            : '<span class="text-muted">0</span>'),
                    TD::make('total', 'Running total')->alignRight(),
                ]),
                'Weekly' => Layout::table('weeklyRows', [
                    TD::make('week', 'Week'),
                    TD::make('range', 'Dates')
                        ->render(fn (Repository $row) => e($row->get('range'))
                            . ($row->get('partial') ? ' <span class="badge bg-light text-muted border">partial</span>' : '')),
                    TD::make('count', 'New registrations')->alignRight()
                        ->render(fn (Repository $row) => '<strong>' . $row->get('count') . '</strong>'),
                    TD::make('total', 'Running total')->alignRight(),
                ]),
            ]),
        ];
    }

    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.registrations', array_filter([
            'from' => $request->input('filters.from'),
            'to'   => $request->input('filters.to'),
            'type' => $request->input('filters.type'),
        ]));
    }

    public function export(Request $request)
    {
        $stats = $this->stats($request);
        $daily = $stats->daily();
        $weekly = $request->input('period') === 'weekly';
        $rows = $weekly ? $stats->weekly($daily) : $daily;

        $filename = sprintf(
            'registrations-%s-%s-%s-to-%s.csv',
            $weekly ? 'weekly' : 'daily',
            $stats->type,
            $stats->from->format('Y-m-d'),
            $stats->to->format('Y-m-d'),
        );

        return response()->streamDownload(function () use ($rows, $weekly) {
            $handle = fopen('php://output', 'w');

            if ($weekly) {
                fputcsv($handle, ['Week', 'Start', 'End', 'New registrations', 'Running total', 'Partial week']);
                foreach ($rows as $row) {
                    fputcsv($handle, [$row['week'], $row['start'], $row['end'], $row['count'], $row['total'], $row['partial'] ? 'yes' : 'no']);
                }
            } else {
                fputcsv($handle, ['Date', 'Day', 'New registrations', 'Running total']);
                foreach ($rows as $row) {
                    fputcsv($handle, [$row['date'], $row['weekday'], $row['count'], $row['total']]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function stats(Request $request): RegistrationStats
    {
        return RegistrationStats::fromInput(
            $request->query('from', $request->input('from')),
            $request->query('to', $request->input('to')),
            $request->query('type', $request->input('type')),
        );
    }
}
