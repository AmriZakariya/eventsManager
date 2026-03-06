{{-- resources/views/orchid/dashboard/main.blade.php --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    @import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css');

    :root {
        --bg-base:     #f8fafc;
        --bg-surface:  #ffffff;
        --bg-border:   #e2e8f0;

        --text-primary:   #0f172a;
        --text-secondary: #475569;
        --text-muted:     #64748b;

        --accent-primary: #4f46e5;
        --accent-indigo:  #6366f1;
        --accent-blue:    #3b82f6;
        --accent-green:   #10b981;
        --accent-red:     #ef4444;
        --accent-amber:   #f59e0b;
        --accent-purple:  #8b5cf6;

        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 20px;

        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.025);
    }

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-up {
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    .cc-wrapper * { box-sizing: border-box; }
    .cc-wrapper {
        font-family: 'Inter', sans-serif;
        color: var(--text-primary);
        background: transparent;
        padding: 12px;
    }

    /* Section Headers */
    .cc-section { margin-bottom: 40px; }
    .cc-section-label {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .cc-section-label i {
        color: var(--accent-primary);
        font-size: 18px;
        background: #e0e7ff;
        padding: 6px;
        border-radius: 8px;
    }

    /* Hero Command Center */
    .cc-hero {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-lg);
        padding: 40px;
        margin-bottom: 40px;
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }
    .cc-hero::before {
        content: '';
        position: absolute;
        top: 0; right: 0; width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(79,70,229,0.08) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }
    .cc-hero-eyebrow {
        font-size: 13px;
        font-weight: 700;
        color: var(--accent-indigo);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cc-hero-title {
        font-size: 38px;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--text-primary);
        margin: 0 0 24px;
        line-height: 1.1;
    }
    .cc-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
    }
    .cc-hero-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-secondary);
        background: var(--bg-surface);
        padding: 8px 16px;
        border-radius: 99px;
        border: 1px solid var(--bg-border);
        box-shadow: var(--shadow-sm);
    }

    /* Status Badges */
    .cc-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 99px;
        font-size: 14px;
        font-weight: 700;
        box-shadow: var(--shadow-sm);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .cc-status-badge.live     { background: #ecfdf5; color: var(--accent-green); border: 1px solid #a7f3d0; }
    .cc-status-badge.upcoming { background: #eff6ff; color: var(--accent-blue);  border: 1px solid #bfdbfe; }
    .cc-status-badge.ended    { background: #f8fafc; color: var(--text-secondary); border: 1px solid var(--bg-border); }
    .cc-status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; animation: pulse 2s infinite; }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Progress & Countdowns */
    .cc-progress-track {
        height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-top: 32px; max-width: 500px;
    }
    .cc-progress-fill {
        height: 100%; background: linear-gradient(90deg, var(--accent-indigo), var(--accent-blue));
        border-radius: 99px; transition: width 1.5s ease-out;
    }
    .cc-countdown { display: flex; gap: 24px; margin-top: 32px; align-items: flex-start; }
    .cc-countdown-unit {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        background: var(--bg-surface); border: 1px solid var(--bg-border); border-radius: var(--radius-md);
        min-width: 80px; padding: 12px; box-shadow: var(--shadow-sm);
    }
    .cc-countdown-num  { font-size: 32px; font-weight: 800; color: var(--accent-primary); line-height: 1; }
    .cc-countdown-lbl  { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-top: 6px; letter-spacing: 0.05em; }

    /* Grids */
    .cc-grid-4        { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
    .cc-grid-charts   { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .cc-grid-charts-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }

    /* KPI Cards (Now configured for <a> tags) */
    .cc-kpi {
        display: block; /* Important for a tags */
        text-decoration: none !important;
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-lg);
        padding: 24px;
        box-shadow: var(--shadow-md);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .cc-kpi:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: #cbd5e1; }
    .cc-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; }
    .cc-kpi-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
    }
    .cc-icon-indigo { background: #e0e7ff; color: var(--accent-primary); }
    .cc-icon-amber  { background: #fef3c7; color: var(--accent-amber); }
    .cc-icon-blue   { background: #dbeafe; color: var(--accent-blue); }
    .cc-icon-red    { background: #fee2e2; color: var(--accent-red); }
    .cc-icon-green  { background: #d1fae5; color: var(--accent-green); }
    .cc-icon-purple { background: #ede9fe; color: var(--accent-purple); }

    .cc-kpi-label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .cc-kpi-value { font-size: 36px; font-weight: 800; color: var(--text-primary); line-height: 1; letter-spacing: -0.03em; }
    .cc-kpi-sub   { font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-top: 12px; display: flex; align-items: center; gap: 6px; }

    /* Mini Status Cards (Now configured for <a> tags) */
    .cc-status-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;}
    .cc-status-mini {
        text-decoration: none !important;
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-md);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .cc-status-mini:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: #cbd5e1;}
    .cc-status-mini-left { display: flex; align-items: center; gap: 12px; }
    .cc-status-mini-val  { font-size: 24px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .cc-status-mini-lbl  { font-size: 13px; font-weight: 600; color: var(--text-muted); }
    .cc-status-mini-icon { font-size: 20px; opacity: 0.5; }

    /* Chart Cards */
    .cc-chart-card {
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-lg);
        padding: 24px;
        box-shadow: var(--shadow-md);
    }
    .cc-chart-header   { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; }
    .cc-chart-title    { font-size: 16px; font-weight: 700; color: var(--text-primary); }
    .cc-chart-subtitle { font-size: 13px; font-weight: 500; color: var(--text-muted); margin-top: 4px; }

    /* Shortcuts */
    .cc-actions-grid { display: flex; gap: 16px; flex-wrap: wrap; }
    .cc-action-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 24px;
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-md);
        text-decoration: none;
        color: var(--text-primary);
        font-size: 15px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }
    .cc-action-btn:hover { background: #f8fafc; border-color: #cbd5e1; text-decoration: none; transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .cc-action-btn i { color: var(--accent-primary); font-size: 18px; }

    /* Responsive */
    @media (max-width: 1024px) {
        .cc-grid-4, .cc-status-row { grid-template-columns: repeat(2, 1fr); }
        .cc-grid-charts, .cc-grid-charts-3 { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .cc-grid-4, .cc-status-row { grid-template-columns: 1fr; }
        .cc-hero { padding: 24px; }
        .cc-hero-title { font-size: 28px; }
        .cc-countdown { gap: 12px; }
    }
</style>

<div class="cc-wrapper">

    {{-- ── HERO: Event Command Center ────────────────────── --}}
    <div class="cc-hero animate-up">
        <div class="cc-hero-eyebrow"><i class="bi bi-broadcast"></i> Event Command Center</div>
        <h1 class="cc-hero-title">{{ $eventName }}</h1>

        <div class="cc-hero-meta">
            @php
                $statusMap      = ['live' => 'live', 'upcoming' => 'upcoming', 'ended' => 'ended'];
                $statusLabelMap = ['live' => 'Live Now', 'upcoming' => 'Upcoming', 'ended' => 'Ended'];
            @endphp
            <span class="cc-status-badge {{ $statusMap[$eventStatus] ?? 'upcoming' }}">
                <span class="cc-status-dot" style="background: currentColor;"></span>
                {{ $statusLabelMap[$eventStatus] ?? ucfirst($eventStatus) }}
            </span>

            @if($settings?->start_date)
                <div class="cc-hero-meta-item">
                    <i class="bi bi-calendar-event"></i>
                    {{ \Carbon\Carbon::parse($settings->start_date)->format('M d, Y') }}
                    @if($settings?->end_date)
                        &nbsp;—&nbsp;{{ \Carbon\Carbon::parse($settings->end_date)->format('M d, Y') }}
                    @endif
                </div>
            @endif

            @if($settings?->location_name)
                <div class="cc-hero-meta-item">
                    <i class="bi bi-geo-alt"></i> {{ $settings->location_name }}
                </div>
            @endif

            @if($settings?->maintenance_mode)
                <span class="cc-status-badge ended" style="background: #fee2e2; color: var(--accent-red); border-color: #fca5a5;">
                    <i class="bi bi-tools"></i> Maintenance Mode
                </span>
            @endif
        </div>

        @if($eventStatus === 'live' && $daysUntil !== null)
            <div class="cc-progress-track">
                <div class="cc-progress-fill" style="width:{{ $daysProgress }}%"></div>
            </div>
            <div style="margin-top:12px;font-size:14px;font-weight:600;color:var(--text-secondary);">
                <i class="bi bi-lightning-charge-fill" style="color: var(--accent-amber)"></i> {{ $daysProgress }}% completed &nbsp;&middot;&nbsp;
                {{ $daysUntil }}d {{ $hoursUntil }}h remaining
            </div>

        @elseif($eventStatus === 'upcoming' && $daysUntil !== null)
            <div class="cc-countdown">
                <div class="cc-countdown-unit">
                    <div class="cc-countdown-num">{{ $daysUntil }}</div>
                    <div class="cc-countdown-lbl">Days</div>
                </div>
                <div class="cc-countdown-unit">
                    <div class="cc-countdown-num">{{ str_pad($hoursUntil, 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="cc-countdown-lbl">Hours</div>
                </div>
                <div class="cc-countdown-unit">
                    <div class="cc-countdown-num">{{ str_pad($minutesUntil, 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="cc-countdown-lbl">Mins</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ── SECTION 1: Core Overview ─────────────────────── --}}
    <div class="cc-section animate-up delay-1">
        <div class="cc-section-label"><i class="bi bi-activity"></i> Live Pulse</div>
        <div class="cc-grid-4">
            {{-- Registrations -> Links to Users List --}}
            <a href="{{ route('platform.systems.users') }}" class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">Registrations</div>
                    <div class="cc-kpi-icon cc-icon-indigo"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $visitorCount }}">0</div>
                <div class="cc-kpi-sub"><i class="bi bi-graph-up-arrow" style="color: var(--accent-green);"></i> <span style="color: var(--accent-green); font-weight: 700;">+{{ $checkedInToday }}</span> &nbsp;joined today</div>
            </a>

            {{-- Exhibitors -> Links to Companies List --}}
            <a href="{{ route('platform.companies.list') }}" class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">Exhibitors</div>
                    <div class="cc-kpi-icon cc-icon-amber"><i class="bi bi-shop"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $exhibitorCount }}">0</div>
                <div class="cc-kpi-sub"><i class="bi bi-check2-circle"></i> Total active booths</div>
            </a>

            {{-- B2B Meetings -> Links to Appointments --}}
            <a href="{{ route('platform.appointments') }}" class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">B2B Meetings</div>
                    <div class="cc-kpi-icon cc-icon-blue"><i class="bi bi-briefcase-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $totalMeetings }}">0</div>
                <div class="cc-kpi-sub"><i class="bi bi-calendar-event"></i> <span style="color: var(--accent-primary); font-weight: 700;">{{ $todayMeetings }}</span> &nbsp;scheduled today</div>
            </a>

            {{-- Support Inbox -> Links to Contacts List --}}
            <a href="{{ route('platform.contacts') }}" class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">Support Inbox</div>
                    <div class="cc-kpi-icon cc-icon-red"><i class="bi bi-envelope-paper-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $unreadMessages }}">0</div>
                <div class="cc-kpi-sub" style="color: {{ $unreadMessages > 0 ? 'var(--accent-red)' : 'var(--text-muted)' }}">
                    {!! $unreadMessages > 0 ? '<i class="bi bi-exclamation-triangle-fill"></i> Needs attention' : '<i class="bi bi-shield-check" style="color: var(--accent-green);"></i> All caught up' !!}
                </div>
            </a>
        </div>
    </div>

    {{-- ── NEW SECTION: Content & Network ───────────────── --}}
    <div class="cc-section animate-up delay-2">
        <div class="cc-section-label"><i class="bi bi-diagram-3-fill"></i> Content & Networking</div>
        <div class="cc-grid-4">

            {{-- Conferences -> Links to Agenda/Conferences List --}}
            <a href="{{ route('platform.conferences.list') }}" class="cc-kpi" style="padding: 20px;">
                <div class="cc-kpi-top" style="margin-bottom: 12px;">
                    <div class="cc-kpi-label">Conferences</div>
                    <div class="cc-kpi-icon cc-icon-purple" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-mic-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" style="font-size: 28px;" data-target="{{ $conferenceCount }}">0</div>
            </a>

            {{-- Speakers -> Links to Speakers List --}}
            <a href="{{ route('platform.speakers.list') }}" class="cc-kpi" style="padding: 20px;">
                <div class="cc-kpi-top" style="margin-bottom: 12px;">
                    <div class="cc-kpi-label">Speakers</div>
                    <div class="cc-kpi-icon cc-icon-green" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-person-video3"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" style="font-size: 28px;" data-target="{{ $speakerCount }}">0</div>
            </a>

            {{-- Products Showcased -> Links to Products List --}}
            <a href="{{ route('platform.products.list') }}" class="cc-kpi" style="padding: 20px;">
                <div class="cc-kpi-top" style="margin-bottom: 12px;">
                    <div class="cc-kpi-label">Products Showcased</div>
                    <div class="cc-kpi-icon cc-icon-blue" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-box-seam-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" style="font-size: 28px;" data-target="{{ $productCount }}">0</div>
            </a>

            {{-- User Connections -> Links to Networking Requests --}}
            <a href="{{ route('platform.networking.requests') }}" class="cc-kpi" style="padding: 20px;">
                <div class="cc-kpi-top" style="margin-bottom: 12px;">
                    <div class="cc-kpi-label">User Connections</div>
                    <div class="cc-kpi-icon cc-icon-indigo" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-link-45deg"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" style="font-size: 28px;" data-target="{{ $connectionCount }}">0</div>
                <div class="cc-kpi-sub" style="font-size: 11px; margin-top: 8px;">+{{ $pendingConnections }} pending</div>
            </a>
        </div>
    </div>

    {{-- ── SECTION 2: B2B Meetings ──────────────────────── --}}
    <div class="cc-section animate-up delay-2">
        <div class="cc-section-label"><i class="bi bi-calendar2-week-fill"></i> Meeting Pipeline</div>

        <div class="cc-status-row">
            {{-- Wrap each mini status in a link pointing to the Appointments list --}}
            <a href="{{ route('platform.appointments') }}" class="cc-status-mini" style="border-left: 4px solid var(--text-muted);">
                <div class="cc-status-mini-left">
                    <div>
                        <div class="cc-status-mini-val">{{ $pendingMeetings }}</div>
                        <div class="cc-status-mini-lbl">Pending</div>
                    </div>
                </div>
                <i class="bi bi-hourglass-split cc-status-mini-icon" style="color: var(--text-muted)"></i>
            </a>

            <a href="{{ route('platform.appointments') }}" class="cc-status-mini" style="border-left: 4px solid var(--accent-blue);">
                <div class="cc-status-mini-left">
                    <div>
                        <div class="cc-status-mini-val">{{ $confirmedMeetings }}</div>
                        <div class="cc-status-mini-lbl">Confirmed</div>
                    </div>
                </div>
                <i class="bi bi-calendar-check cc-status-mini-icon" style="color: var(--accent-blue)"></i>
            </a>

            <a href="{{ route('platform.appointments') }}" class="cc-status-mini" style="border-left: 4px solid var(--accent-green);">
                <div class="cc-status-mini-left">
                    <div>
                        <div class="cc-status-mini-val">{{ $completedMeetings }}</div>
                        <div class="cc-status-mini-lbl">Completed</div>
                    </div>
                </div>
                <i class="bi bi-check2-all cc-status-mini-icon" style="color: var(--accent-green)"></i>
            </a>

            <a href="{{ route('platform.appointments') }}" class="cc-status-mini" style="border-left: 4px solid var(--accent-red);">
                <div class="cc-status-mini-left">
                    <div>
                        <div class="cc-status-mini-val">{{ $cancelledMeetings }}</div>
                        <div class="cc-status-mini-lbl">Cancelled</div>
                    </div>
                </div>
                <i class="bi bi-x-circle cc-status-mini-icon" style="color: var(--accent-red)"></i>
            </a>
        </div>

        <div class="cc-grid-charts-3">
            <div class="cc-chart-card">
                <div class="cc-chart-header">
                    <div>
                        <div class="cc-chart-title">Meeting Volume</div>
                        <div class="cc-chart-subtitle">Last 14 days</div>
                    </div>
                </div>
                <canvas id="chart-appt" height="90"></canvas>
            </div>
            <div class="cc-chart-card">
                <div class="cc-chart-header">
                    <div>
                        <div class="cc-chart-title">Status Breakdown</div>
                    </div>
                </div>
                <div style="position: relative; height: 180px; width: 100%; display: flex; justify-content: center;">
                    <canvas id="chart-donut"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: Growth & Analytics ────────────────── --}}
    <div class="cc-section animate-up delay-3">
        <div class="cc-section-label"><i class="bi bi-bar-chart-line-fill"></i> Traffic & Growth</div>

        <div class="cc-grid-charts">
            <div class="cc-chart-card">
                <div class="cc-chart-header">
                    <div>
                        <div class="cc-chart-title">Daily Registrations</div>
                        <div class="cc-chart-subtitle">Last 14 days</div>
                    </div>
                </div>
                <canvas id="chart-visitors" height="90"></canvas>
            </div>

            <div class="cc-chart-card">
                <div class="cc-chart-header">
                    <div>
                        <div class="cc-chart-title">Weekly Cumulative</div>
                        <div class="cc-chart-subtitle">Last 8 weeks</div>
                    </div>
                </div>
                <canvas id="chart-weekly" height="90"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- ══ CHART.JS + SCRIPTS ══════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('turbo:load', function () {
        if (!document.getElementById('chart-visitors')) return;

        Chart.defaults.color           = '#64748b';
        Chart.defaults.borderColor     = '#f1f5f9';
        Chart.defaults.font.family     = "'Inter', sans-serif";
        Chart.defaults.font.size       = 12;
        Chart.defaults.plugins.legend.labels.boxWidth        = 10;
        Chart.defaults.plugins.legend.labels.usePointStyle   = true;
        Chart.defaults.plugins.tooltip.backgroundColor       = '#0f172a';
        Chart.defaults.plugins.tooltip.titleColor            = '#ffffff';
        Chart.defaults.plugins.tooltip.bodyColor             = '#cbd5e1';
        Chart.defaults.plugins.tooltip.padding               = 12;
        Chart.defaults.plugins.tooltip.cornerRadius          = 8;
        Chart.defaults.plugins.tooltip.displayColors         = false;

        const COLOR_INDIGO = '#4f46e5';
        const COLOR_BLUE   = '#3b82f6';
        const COLOR_GREEN  = '#10b981';
        const COLOR_RED    = '#ef4444';
        const COLOR_MUTED  = '#94a3b8';

        // Add a nice gradient fill to the line chart
        const ctxVisitors = document.getElementById('chart-visitors').getContext('2d');
        const gradientFill = ctxVisitors.createLinearGradient(0, 0, 0, 300);
        gradientFill.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
        gradientFill.addColorStop(1, 'rgba(79, 70, 229, 0)');

        new Chart(ctxVisitors, {
            type: 'line',
            data: {
                labels: @json($visitorChartLabels),
                datasets: [{
                    label: 'Registrations',
                    data:  @json($visitorChartValues),
                    borderColor: COLOR_INDIGO,
                    backgroundColor: gradientFill,
                    fill: true,
                    tension: 0.4, // Curved lines
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointBackgroundColor: COLOR_INDIGO,
                    borderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { dash: [4, 4] } }
                },
                interaction: { intersect: false, mode: 'index' },
            }
        });

        new Chart(document.getElementById('chart-appt'), {
            type: 'bar',
            data: {
                labels: @json($apptChartLabels),
                datasets: [{
                    label: 'Appointments',
                    data:  @json($apptChartValues),
                    backgroundColor: COLOR_BLUE,
                    hoverBackgroundColor: '#2563eb',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { dash: [4, 4] } }
                }
            }
        });

        new Chart(document.getElementById('chart-donut'), {
            type: 'doughnut',
            data: {
                labels: @json($donutLabels),
                datasets: [{
                    data: @json($donutValues),
                    backgroundColor: [COLOR_MUTED, COLOR_BLUE, COLOR_GREEN, COLOR_RED],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } }
                }
            }
        });

        new Chart(document.getElementById('chart-weekly'), {
            type: 'bar',
            data: {
                labels: @json($weeklyLabels),
                datasets: [{
                    label: 'Visitors',
                    data:  @json($weeklyValues),
                    backgroundColor: COLOR_INDIGO,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { dash: [4, 4] } }
                }
            }
        });

        function animateCounter(el) {
            const target = parseInt(el.dataset.target) || 0;
            if (target === 0) { el.textContent = '0'; return; }
            let current = 0;
            const timer = setInterval(() => {
                current += Math.max(1, Math.ceil(target / 30));
                if (current >= target) {
                    el.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    el.textContent = current.toLocaleString();
                }
            }, 30);
        }

        // Trigger counters slightly delayed for effect
        setTimeout(() => {
            document.querySelectorAll('.cc-counter').forEach(animateCounter);
        }, 200);

    });
</script>
