<?php

namespace App\Orchid\Screens\Company;

use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Group;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class CompanyListScreen extends Screen
{
    public function name(): ?string
    {
        return 'Exhibitors & Partners';
    }

    public function description(): ?string
    {
        return 'Directory of all participating companies, sponsors, and partners.';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY
    // ──────────────────────────────────────────────────────────────────────────

    public function query(Request $request): iterable
    {
        $query = Company::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('booth_number', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->whereJsonContains('type', $type);
        }

        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        if ($category = $request->get('category')) {
            $query->where('category', 'like', "%{$category}%");
        }

        $total    = Company::count();
        $sponsors = Company::whereJsonContains('type', 'SPONSOR')->count();
        $active   = Company::where('is_active', 1)->count();
        $featured = Company::where('is_featured', 1)->count();

        return [
            'countries' => Company::distinct()->whereNotNull('country')->pluck('country', 'country')->toArray(),
            'companies' => $query->latest()->paginate(15),
            'metrics'   => compact('total', 'sponsors', 'active', 'featured'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // COMMAND BAR
    // ──────────────────────────────────────────────────────────────────────────

    public function commandBar(): array
    {
        return [
            Link::make('New Company')
                ->icon('bs.plus-lg')
                ->type(Color::PRIMARY)
                ->route('platform.companies.create'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Deterministic pastel avatar (logo image or coloured initial).
     */
    private function companyAvatar(Company $company): string
    {
        $palettes = [
            ['#EFF6FF', '#1D4ED8'],
            ['#F0FDF4', '#15803D'],
            ['#FFF7ED', '#C2410C'],
            ['#FDF4FF', '#9333EA'],
            ['#FFF1F2', '#BE123C'],
            ['#F0FDFA', '#0F766E'],
            ['#FEFCE8', '#A16207'],
        ];
        [$bg, $fg] = $palettes[abs(crc32($company->name)) % count($palettes)];
        $initial   = mb_strtoupper(mb_substr($company->name, 0, 1));

        if ($company->logo) {
            return "<img src='" . asset($company->logo) . "'
                        style='width:42px;height:42px;border-radius:10px;
                               object-fit:contain;background:#fff;
                               border:1px solid #E5E7EB;flex-shrink:0;'
                        alt='" . e($company->name) . "'>";
        }

        return "<div style='
                    width:42px;height:42px;border-radius:10px;
                    background:{$bg};color:{$fg};font-weight:700;
                    font-size:1.05rem;display:flex;align-items:center;
                    justify-content:center;border:1px solid {$fg}2E;
                    flex-shrink:0;user-select:none;
                '>{$initial}</div>";
    }

    /**
     * Pill badge for a single role/type value.
     */
    private function roleBadge(string $type): string
    {
        [$bg, $color, $border] = match($type) {
            'SPONSOR'               => ['#FFF7ED', '#C2410C', '#FFEDD5'],
            'INSTITUTIONAL_PARTNER' => ['#ECFDF5', '#047857', '#D1FAE5'],
            'MEDIA_PARTNER'         => ['#FDF4FF', '#9333EA', '#F3E8FF'],
            'EXHIBITOR'             => ['#EFF6FF', '#1D4ED8', '#DBEAFE'],
            default                 => ['#F3F4F6', '#374151', '#E5E7EB'],
        };

        $label = ucwords(strtolower(str_replace('_', ' ', $type)));

        return "<span style='
                    display:inline-block;
                    background:{$bg};color:{$color};
                    border:1px solid {$border};
                    font-size:0.7rem;font-weight:600;
                    padding:3px 9px;border-radius:20px;
                    white-space:nowrap;letter-spacing:0.03em;
                '>{$label}</span>";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LAYOUT
    // ──────────────────────────────────────────────────────────────────────────

    public function layout(): iterable
    {
        $isFiltering = request('search') || request('type') || request('country') || request('category');

        return [

            // ── 1. METRICS ROW ───────────────────────────────────────────────
            Layout::metrics([
                'Total Companies' => 'metrics.total',
                'Active Sponsors' => 'metrics.sponsors',
                'Live on App'     => 'metrics.active',
                'Featured'        => 'metrics.featured',
            ]),

            // ── 2. SEARCH & FILTER PANEL ─────────────────────────────────────
            Layout::rows([

                // Active-filter indicator banner
                $isFiltering
                    ? \Orchid\Screen\Fields\Input::make('_filter_hint')
                    ->type('hidden')
                    ->attributes(['data-filter-active' => '1'])
                    : \Orchid\Screen\Fields\Input::make('_filter_hint')->type('hidden'),

                Group::make([
                    Input::make('search')
                        ->title('Keyword search')
                        ->placeholder('Name, email or booth number…')
                        ->value(request('search'))
                        ->icon('bs.search'),

                    Select::make('type')
                        ->title('Partner type')
                        ->options([
                            'EXHIBITOR'             => 'Exhibitor',
                            'SPONSOR'               => 'Sponsor',
                            'INSTITUTIONAL_PARTNER' => 'Institutional Partner',
                            'MEDIA_PARTNER'         => 'Media Partner',
                        ])
                        ->empty('All types')
                        ->value(request('type')),

                    Select::make('country')
                        ->title('Country')
                        ->options($this->query(request())['countries'])
                        ->empty('All countries')
                        ->value(request('country')),

                    Input::make('category')
                        ->title('Industry / Category')
                        ->placeholder('e.g. Technology')
                        ->value(request('category')),
                ]),

                Group::make([
                    Button::make('Apply filters')
                        ->icon('bs.funnel-fill')
                        ->type(Color::PRIMARY)
                        ->method('applyFilters'),

                    Link::make($isFiltering ? '✕  Clear filters' : 'Reset')
                        ->icon($isFiltering ? 'bs.x-circle-fill' : 'bs.x-circle')
                        ->route('platform.companies.list'),
                ])->autoWidth(),
            ]),

            // ── 3. DATA TABLE ─────────────────────────────────────────────────
            Layout::table('companies', [

                // ── COMPANY IDENTITY ──────────────────────────────────────────
                TD::make('name', 'Company')
                    ->sort()
                    ->width('300px')
                    ->render(function (Company $company) {

                        $avatar  = $this->companyAvatar($company);

                        $name = "<a href='" . route('platform.companies.edit', $company->id) . "'
                    style='
                        font-weight:600;
                        color:#111827;
                        font-size:0.875rem;
                        text-decoration:none;
                        white-space:nowrap;
                        overflow:hidden;
                        text-overflow:ellipsis;
                        max-width:210px;
                        display:block;
                    '
                    onmouseover=\"this.style.color='#1D4ED8'\"
                    onmouseout=\"this.style.color='#111827'\"
                >" . e($company->name) . "</a>";

                        $email = e($company->email ?? '');

                        $emailHtml = $email
                            ? "<a href='mailto:{$email}'
                  style='color:#6B7280;font-size:0.78rem;text-decoration:none;'>
                  {$email}
               </a>"
                            : "<span style='color:#CBD5E1;font-size:0.78rem;font-style:italic;'>No email</span>";

                        return "
        <div style='display:flex;align-items:center;gap:12px;padding:6px 0;'>
            {$avatar}
            <div style='min-width:0;'>
                {$name}
                <div style='margin-top:2px;'>{$emailHtml}</div>
            </div>
        </div>";
                    }),

                // ── ACCESS CODE ───────────────────────────────────────────────
                TD::make('passcode', 'Access Code')
                    ->sort()
                    ->alignCenter()
                    ->width('140px')
                    ->render(function (Company $company) {
                        if (!$company->passcode) {
                            return "<span style='color:#CBD5E1;font-size:0.8rem;'>—</span>";
                        }

                        $code = e($company->passcode);

                        return "
                            <div style='
                                    display:inline-flex;align-items:center;gap:6px;
                                    background:#F0FDF4;border:1px solid #BBF7D0;
                                    border-radius:8px;padding:5px 10px;cursor:pointer;
                                    transition:background 0.15s;
                                '
                                title='Click to copy'
                                onclick=\"
                                    navigator.clipboard.writeText('{$code}');
                                    var s=this.querySelector('.code-val');
                                    var orig=s.textContent;
                                    s.textContent='Copied!';
                                    this.style.background='#DCFCE7';
                                    setTimeout(function(){s.textContent=orig;this.style.background='#F0FDF4';}.bind(this),1500);
                                \">
                                <i class='bi bi-key-fill' style='color:#16A34A;font-size:0.75rem;'></i>
                                <span class='code-val' style='
                                    font-family:ui-monospace,SFMono-Regular,monospace;
                                    font-size:0.8rem;font-weight:600;color:#15803D;
                                    letter-spacing:0.06em;
                                '>{$code}</span>
                            </div>";
                    }),

                // ── ROLES / TYPES ─────────────────────────────────────────────
                TD::make('type', 'Roles')
                    ->width('220px')
                    ->render(function (Company $company) {
                        if (empty($company->type)) {
                            return "<span style='color:#CBD5E1;font-size:0.78rem;font-style:italic;'>No role assigned</span>";
                        }

                        $badges = implode('', array_map(fn($t) => $this->roleBadge($t), $company->type));

                        return "<div style='display:flex;flex-wrap:wrap;gap:4px;'>{$badges}</div>";
                    }),

                // ── LOCATION & BOOTH ──────────────────────────────────────────
                TD::make('booth_number', 'Location & Booth')
                    ->width('175px')
                    ->render(function (Company $c) {
                        $country = e($c->country ?? '');
                        $booth   = e($c->booth_number ?? '');

                        $countryHtml = $country
                            ? "<div style='display:flex;align-items:center;gap:5px;color:#374151;font-size:0.82rem;font-weight:500;'>
                                   <i class='bi bi-geo-alt-fill' style='color:#94A3B8;font-size:0.72rem;'></i>
                                   {$country}
                               </div>"
                            : "<span style='color:#CBD5E1;font-size:0.8rem;'>—</span>";

                        $boothHtml = $booth
                            ? "<span style='
                                  display:inline-flex;align-items:center;gap:4px;
                                  background:#F8FAFC;border:1px solid #E2E8F0;
                                  border-radius:6px;padding:2px 8px;margin-top:5px;
                               '>
                                   <i class='bi bi-shop' style='color:#94A3B8;font-size:0.68rem;'></i>
                                   <span style='font-size:0.73rem;font-weight:600;color:#475569;'>Booth&nbsp;{$booth}</span>
                               </span>"
                            : "<div style='margin-top:4px;color:#CBD5E1;font-size:0.73rem;'>No booth</div>";

                        return "<div style='line-height:1;'>{$countryHtml}{$boothHtml}</div>";
                    }),

                // ── STATUS ────────────────────────────────────────────────────
                TD::make('is_active', 'Status')
                    ->width('130px')
                    ->alignCenter()
                    ->render(function (Company $c) {
                        // Active / Hidden pill
                        if ($c->is_active) {
                            $pill = "
                                <span style='
                                    display:inline-flex;align-items:center;gap:5px;
                                    background:#F0FDF4;border:1px solid #BBF7D0;
                                    border-radius:20px;padding:3px 10px;
                                '>
                                    <span style='
                                        width:6px;height:6px;border-radius:50%;
                                        background:#22C55E;display:inline-block;
                                        box-shadow:0 0 0 2px #22C55E30;
                                    '></span>
                                    <span style='font-size:0.73rem;font-weight:600;color:#16A34A;'>Active</span>
                                </span>";
                        } else {
                            $pill = "
                                <span style='
                                    display:inline-flex;align-items:center;gap:5px;
                                    background:#F8FAFC;border:1px solid #E2E8F0;
                                    border-radius:20px;padding:3px 10px;
                                '>
                                    <span style='
                                        width:6px;height:6px;border-radius:50%;
                                        background:#94A3B8;display:inline-block;
                                    '></span>
                                    <span style='font-size:0.73rem;font-weight:600;color:#64748B;'>Hidden</span>
                                </span>";
                        }

                        // Optional featured badge
                        $featured = '';
                        if ($c->is_featured) {
                            $featured = "
                                <span style='
                                    display:inline-flex;align-items:center;gap:4px;
                                    background:#FFFBEB;border:1px solid #FDE68A;
                                    border-radius:20px;padding:3px 9px;margin-top:5px;
                                '>
                                    <i class='bi bi-star-fill' style='color:#D97706;font-size:0.62rem;'></i>
                                    <span style='font-size:0.7rem;font-weight:600;color:#B45309;'>Featured</span>
                                </span>";
                        }

                        return "<div style='display:flex;flex-direction:column;align-items:center;gap:0;'>{$pill}{$featured}</div>";
                    }),

                // ── ACTIONS ───────────────────────────────────────────────────
                TD::make('actions', '')
                    ->alignRight()
                    ->canSee(true)
                    ->width('60px')
                    ->render(fn(Company $company) =>
                    DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Link::make('Edit')
                                ->route('platform.companies.edit', $company->id)
                                ->icon('bs.pencil-square'),

                            Button::make('Delete')
                                ->icon('bs.trash3')
                                ->confirm(
                                    'Permanently delete "' . e($company->name) . '"? ' .
                                    'This action cannot be undone.'
                                )
                                ->method('remove', ['id' => $company->id])
                                ->class('text-danger'),
                        ])
                    ),
            ]),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ACTIONS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Redirect back with filter parameters applied.
     * Empty values are stripped to keep the URL clean.
     */
    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.companies.list', array_filter([
            'search'   => $request->get('search'),
            'type'     => $request->get('type'),
            'country'  => $request->get('country'),
            'category' => $request->get('category'),
        ]));
    }

    /**
     * Delete a company and show a contextual success toast.
     */
    public function remove(Request $request)
    {
        $company = Company::findOrFail($request->get('id'));
        $name    = $company->name;
        $company->delete();

        Toast::success("\"{$name}\" has been removed.");
    }
}
