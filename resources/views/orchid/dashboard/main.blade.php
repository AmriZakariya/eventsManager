{{-- resources/views/orchid/dashboard/main.blade.php --}}
<style>
    /* ═══════════════════════════════════════════════════════════
       COMMAND CENTER — Premium Light SaaS Design System
       Font: Inter
       Palette: Slate (Light Mode) + Indigo Accents
    ═══════════════════════════════════════════════════════════ */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --bg-base:     #f8fafc; /* Slate 50 - Main background */
        --bg-surface:  #ffffff; /* White - Cards and panels */
        --bg-elevated: #ffffff;
        --bg-border:   #e2e8f0; /* Slate 200 - Soft borders */

        --text-primary:   #0f172a; /* Slate 900 - Headings */
        --text-secondary: #475569; /* Slate 600 - Body text */
        --text-muted:     #64748b; /* Slate 500 - Labels */

        --accent-primary: #4f46e5; /* Indigo 600 - Main brand color */
        --accent-blue:    #2563eb; /* Blue 600 */
        --accent-green:   #059669; /* Emerald 600 */
        --accent-red:     #dc2626; /* Red 600 */
        --accent-amber:   #d97706; /* Amber 600 */

        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;

        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
    }

    /* ── Base Overrides ─────────────────────────────────────── */
    .cc-wrapper * { box-sizing: border-box; }
    .cc-wrapper {
        font-family: 'Inter', sans-serif;
        color: var(--text-primary);
        background: var(--bg-base);
        min-height: 100%;
        padding: 24px;
    }

    /* ── Section Header ─────────────────────────────────────── */
    .cc-section { margin-bottom: 40px; }
    .cc-section-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .cc-section-label i {
        color: var(--text-muted);
        font-size: 16px;
    }

    /* ── Hero / Event Banner ────────────────────────────────── */
    .cc-hero {
        background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-lg);
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
    }
    .cc-hero-eyebrow {
        font-size: 12px;
        font-weight: 600;
        color: var(--accent-primary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .cc-hero-title {
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--text-primary);
        margin: 0 0 20px;
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
        font-size: 13px;
        font-weight: 500;
        color: var(--text-secondary);
        background: var(--bg-surface);
        padding: 6px 14px;
        border-radius: 99px;
        border: 1px solid var(--bg-border);
        box-shadow: var(--shadow-sm);
    }

    .cc-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: var(--shadow-sm);
    }
    .cc-status-badge.live { background: #ecfdf5; color: var(--accent-green); border: 1px solid #a7f3d0; }
    .cc-status-badge.upcoming { background: #eff6ff; color: var(--accent-blue); border: 1px solid #bfdbfe; }
    .cc-status-badge.ended { background: #f8fafc; color: var(--text-secondary); border: 1px solid var(--bg-border); }

    /* Progress & Countdown */
    .cc-progress-track {
        height: 8px;
        background: #e2e8f0;
        border-radius: 99px;
        overflow: hidden;
        margin-top: 24px;
        max-width: 400px;
    }
    .cc-progress-fill {
        height: 100%;
        background: var(--accent-primary);
        border-radius: 99px;
        transition: width 1s ease;
    }
    .cc-countdown {
        display: flex;
        gap: 24px;
        margin-top: 24px;
    }
    .cc-countdown-unit { display: flex; flex-direction: column; gap: 4px; }
    .cc-countdown-num { font-size: 28px; font-weight: 700; color: var(--text-primary); line-height: 1; letter-spacing: -0.02em; }
    .cc-countdown-lbl { font-size: 12px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }

    /* ── Grids ─────────────────────────────────────────────── */
    .cc-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .cc-grid-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .cc-grid-charts-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

    /* ── KPI Cards ───────────────────────────────────────────── */
    .cc-kpi {
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-md);
        padding: 24px;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .cc-kpi:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .cc-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .cc-kpi-icon {
        width: 40px; height: 40px;
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    /* Soft tinted backgrounds for icons */
    .cc-icon-indigo { background: #e0e7ff; color: var(--accent-primary); }
    .cc-icon-amber  { background: #fef3c7; color: var(--accent-amber); }
    .cc-icon-blue   { background: #dbeafe; color: var(--accent-blue); }
    .cc-icon-red    { background: #fee2e2; color: var(--accent-red); }

    .cc-kpi-label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.02em; }
    .cc-kpi-value { font-size: 32px; font-weight: 700; color: var(--text-primary); line-height: 1; letter-spacing: -0.03em; }
    .cc-kpi-sub { font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-top: 10px; display: flex; align-items: center; gap: 4px; }

    /* ── Status Breakdown Row ─────────────────────────────── */
    .cc-status-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .cc-status-mini {
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-md);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--shadow-sm);
    }
    .cc-status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .cc-status-mini-val { font-size: 20px; font-weight: 700; color: var(--text-primary); line-height: 1; }
    .cc-status-mini-lbl { font-size: 13px; font-weight: 500; color: var(--text-muted); margin-top: 4px; }

    /* ── Chart Card ─────────────────────────────────────────── */
    .cc-chart-card {
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-md);
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }
    .cc-chart-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; }
    .cc-chart-title { font-size: 15px; font-weight: 600; color: var(--text-primary); }
    .cc-chart-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

    /* ── Quick Actions ───────────────────────────────────────── */
    .cc-actions-grid { display: flex; gap: 12px; flex-wrap: wrap; }
    .cc-action-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background: var(--bg-surface);
        border: 1px solid var(--bg-border);
        border-radius: var(--radius-sm);
        text-decoration: none;
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }
    .cc-action-btn:hover {
        background: var(--bg-base);
        border-color: #cbd5e1;
        text-decoration: none;
        transform: translateY(-1px);
    }
    .cc-action-btn i { color: var(--accent-primary); font-size: 16px; }

    /* ── Responsive ─────────────────────────────────────────── */
    @media (max-width: 1024px) {
        .cc-grid-4, .cc-status-row { grid-template-columns: repeat(2, 1fr); }
        .cc-grid-charts, .cc-grid-charts-3 { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .cc-grid-4, .cc-status-row { grid-template-columns: 1fr; }
        .cc-hero { padding: 24px; }
    }
</style>

<div class="cc-wrapper">

    {{-- ── HERO: Event Status ────────────────────────────── --}}
    <div class="cc-hero">
        <div class="cc-hero-eyebrow">Event Dashboard</div>
        <h1 class="cc-hero-title">{{ $eventName }}</h1>

        <div class="cc-hero-meta">
            @php
                $statusMap = ['live' => 'live', 'upcoming' => 'upcoming', 'ended' => 'ended'];
                $statusLabelMap = ['live' => 'Live Now', 'upcoming' => 'Upcoming', 'ended' => 'Ended'];
            @endphp
            <span class="cc-status-badge {{ $statusMap[$eventStatus] ?? 'upcoming' }}">
                <span class="cc-status-dot" style="background: currentColor;"></span>
                {{ $statusLabelMap[$eventStatus] ?? ucfirst($eventStatus) }}
            </span>

            @if($settings?->start_date)
                <div class="cc-hero-meta-item">
                    <i class="bi bi-calendar-event text-muted"></i>
                    {{ \Carbon\Carbon::parse($settings->start_date)->format('M d, Y') }}
                    @if($settings?->end_date)
                        &nbsp;—&nbsp;{{ \Carbon\Carbon::parse($settings->end_date)->format('M d, Y') }}
                    @endif
                </div>
            @endif

            @if($settings?->location_name)
                <div class="cc-hero-meta-item">
                    <i class="bi bi-geo-alt text-muted"></i> {{ $settings->location_name }}
                </div>
            @endif

            @if($settings?->maintenance_mode)
                <span class="cc-status-badge ended" style="background: #fee2e2; color: var(--accent-red); border-color: #fca5a5;">
                    Maintenance Mode
                </span>
            @endif
        </div>

        @if($eventStatus === 'live' && $daysUntil !== null)
            <div class="cc-progress-track">
                <div class="cc-progress-fill" style="width:{{ $daysProgress }}%"></div>
            </div>
            <div style="margin-top:10px;font-size:13px;font-weight:500;color:var(--text-secondary);">
                {{ $daysProgress }}% completed &nbsp;&middot;&nbsp; {{ $daysUntil }} days remaining
            </div>
        @elseif($eventStatus === 'upcoming' && $daysUntil !== null)
            <div class="cc-countdown">
                <div class="cc-countdown-unit">
                    <div class="cc-countdown-num">{{ $daysUntil }}</div>
                    <div class="cc-countdown-lbl">Days</div>
                </div>
                <div class="cc-countdown-unit">
                    <div class="cc-countdown-num">{{ now()->diffInHours(\Carbon\Carbon::parse($settings->start_date)) % 24 }}</div>
                    <div class="cc-countdown-lbl">Hours</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ── SECTION 1: Core Overview ─────────────────────── --}}
    <div class="cc-section">
        <div class="cc-section-label"><i class="bi bi-bar-chart-fill"></i> Overview</div>
        <div class="cc-grid-4">
            <div class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">Registrations</div>
                    <div class="cc-kpi-icon cc-icon-indigo"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $visitorCount }}">0</div>
                <div class="cc-kpi-sub"><span style="color: var(--accent-green);">+{{ $checkedInToday }}</span> joined today</div>
            </div>

            <div class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">Exhibitors</div>
                    <div class="cc-kpi-icon cc-icon-amber"><i class="bi bi-shop"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $exhibitorCount }}">0</div>
                <div class="cc-kpi-sub">Total active booths</div>
            </div>

            <div class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">B2B Meetings</div>
                    <div class="cc-kpi-icon cc-icon-blue"><i class="bi bi-briefcase-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $totalMeetings }}">0</div>
                <div class="cc-kpi-sub"><span style="color: var(--accent-primary);">{{ $todayMeetings }}</span> scheduled today</div>
            </div>

            <div class="cc-kpi">
                <div class="cc-kpi-top">
                    <div class="cc-kpi-label">Support Inbox</div>
                    <div class="cc-kpi-icon cc-icon-red"><i class="bi bi-envelope-fill"></i></div>
                </div>
                <div class="cc-kpi-value cc-counter" data-target="{{ $unreadMessages }}">0</div>
                <div class="cc-kpi-sub" style="color: {{ $unreadMessages > 0 ? 'var(--accent-red)' : 'var(--text-muted)' }}">
                    {!! $unreadMessages > 0 ? '<i class="bi bi-exclamation-circle-fill"></i> Needs attention' : '<i class="bi bi-check-circle-fill" style="color: var(--accent-green);"></i> All caught up' !!}
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 2: B2B Meetings ──────────────────────── --}}
    <div class="cc-section">
        <div class="cc-section-label"><i class="bi bi-calendar2-check-fill"></i> Meeting Pipeline</div>

        <div class="cc-status-row" style="margin-bottom:20px;">
            <div class="cc-status-mini">
                <div class="cc-status-dot" style="background:var(--text-muted);"></div>
                <div>
                    <div class="cc-status-mini-val">{{ $pendingMeetings }}</div>
                    <div class="cc-status-mini-lbl">Pending</div>
                </div>
            </div>
            <div class="cc-status-mini">
                <div class="cc-status-dot" style="background:var(--accent-blue);"></div>
                <div>
                    <div class="cc-status-mini-val">{{ $confirmedMeetings }}</div>
                    <div class="cc-status-mini-lbl">Confirmed</div>
                </div>
            </div>
            <div class="cc-status-mini">
                <div class="cc-status-dot" style="background:var(--accent-green);"></div>
                <div>
                    <div class="cc-status-mini-val">{{ $completedMeetings }}</div>
                    <div class="cc-status-mini-lbl">Completed</div>
                </div>
            </div>
            <div class="cc-status-mini">
                <div class="cc-status-dot" style="background:var(--accent-red);"></div>
                <div>
                    <div class="cc-status-mini-val">{{ $cancelledMeetings }}</div>
                    <div class="cc-status-mini-lbl">Cancelled</div>
                </div>
            </div>
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
                <canvas id="chart-donut" height="180"></canvas>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: Growth & Analytics ────────────────── --}}
    <div class="cc-section">
        <div class="cc-section-label"><i class="bi bi-graph-up-arrow"></i> Traffic & Registrations</div>

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

    {{-- ── SECTION 4: Quick Links ───────────────────────── --}}
    <div class="cc-section">
        <div class="cc-section-label"><i class="bi bi-lightning-charge-fill"></i> Shortcuts</div>
        <div class="cc-actions-grid">
            <a href="{{ route('platform.appointments') }}" class="cc-action-btn">
                <i class="bi bi-briefcase-fill"></i> Appointments
            </a>
            <a href="{{ route('platform.contacts') }}" class="cc-action-btn">
                <i class="bi bi-envelope-fill"></i> Inbox
                @if($unreadMessages > 0)
                    <span style="background:var(--accent-red);color:white;padding:2px 8px;border-radius:99px;font-size:12px;font-weight:600;">{{ $unreadMessages }}</span>
                @endif
            </a>
            <a href="{{ route('platform.companies.list') }}" class="cc-action-btn">
                <i class="bi bi-building"></i> Companies
            </a>
            <a href="{{ route('platform.conferences.list') }}" class="cc-action-btn">
                <i class="bi bi-mic-fill"></i> Conferences
            </a>
            <a href="{{ route('platform.event.settings') }}" class="cc-action-btn">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </div>
    </div>

</div>

{{-- ══ CHART.JS + SCRIPTS ══════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    (function () {
        /* ── Light Mode Chart Config ────────────────────────────── */
        Chart.defaults.color           = '#64748b'; // Slate 500
        Chart.defaults.borderColor     = '#f1f5f9'; // Slate 100 for very subtle gridlines
        Chart.defaults.font.family     = "'Inter', sans-serif";
        Chart.defaults.font.size       = 12;
        Chart.defaults.plugins.legend.labels.boxWidth  = 8;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;

        // Dark premium tooltips
        Chart.defaults.plugins.tooltip.backgroundColor = '#0f172a'; // Slate 900
        Chart.defaults.plugins.tooltip.titleColor      = '#ffffff';
        Chart.defaults.plugins.tooltip.bodyColor       = '#cbd5e1'; // Slate 300
        Chart.defaults.plugins.tooltip.padding         = 12;
        Chart.defaults.plugins.tooltip.cornerRadius    = 8;

        const COLOR_INDIGO  = '#4f46e5';
        const COLOR_BLUE    = '#2563eb';
        const COLOR_GREEN   = '#059669';
        const COLOR_RED     = '#dc2626';
        const COLOR_MUTED   = '#94a3b8'; // Slate 400
        const COLOR_FILL    = 'rgba(79, 70, 229, 0.08)'; // Very light indigo fill

        /* ── Visitor Line Chart ─────────────────────────────────── */
        new Chart(document.getElementById('chart-visitors'), {
            type: 'line',
            data: {
                labels: @json($visitorChartLabels),
                datasets: [{
                    label: 'Registrations',
                    data:  @json($visitorChartValues),
                    borderColor: COLOR_INDIGO,
                    backgroundColor: COLOR_FILL,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointBackgroundColor: COLOR_INDIGO,
                    borderWidth: 2,
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

        /* ── Appointments Bar Chart ─────────────────────────────── */
        new Chart(document.getElementById('chart-appt'), {
            type: 'bar',
            data: {
                labels: @json($apptChartLabels),
                datasets: [{
                    label: 'Appointments',
                    data:  @json($apptChartValues),
                    backgroundColor: COLOR_BLUE,
                    hoverBackgroundColor: '#1d4ed8', // Darker blue on hover
                    borderRadius: 4,
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

        /* ── Status Donut ───────────────────────────────────────── */
        new Chart(document.getElementById('chart-donut'), {
            type: 'doughnut',
            data: {
                labels: @json($donutLabels),
                datasets: [{
                    data: @json($donutValues),
                    backgroundColor: [COLOR_MUTED, COLOR_BLUE, COLOR_GREEN, COLOR_RED],
                    borderWidth: 2,
                    borderColor: '#ffffff', // Match card background to create gaps
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        /* ── Weekly Bar Chart ───────────────────────────────────── */
        new Chart(document.getElementById('chart-weekly'), {
            type: 'bar',
            data: {
                labels: @json($weeklyLabels),
                datasets: [{
                    label: 'Visitors',
                    data:  @json($weeklyValues),
                    backgroundColor: COLOR_INDIGO,
                    borderRadius: 4,
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

        /* ── Animated Counters ──────────────────────────────────── */
        function animateCounter(el) {
            const target = parseInt(el.dataset.target) || 0;
            if (target === 0) { el.textContent = '0'; return; }
            const duration = 800;
            let current    = 0;
            const timer = setInterval(() => {
                current += Math.max(1, Math.ceil(target / 40));
                if (current >= target) { el.textContent = target.toLocaleString(); clearInterval(timer); }
                else el.textContent = current.toLocaleString();
            }, 16);
        }
        document.querySelectorAll('.cc-counter').forEach(animateCounter);

    })();
</script>
