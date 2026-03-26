{{-- resources/views/admin/appointment/calendar.blade.php --}}
<div class="mb-3">
    <div class="card border-0 shadow-sm appointment-calendar-shell">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1 fw-bold appointment-calendar-title">
                        <i class="bi bi-calendar3 text-primary me-2"></i> Meeting Schedule
                    </h5>
                    <div class="small text-muted">
                        {{ count($calendarEvents ?? []) }} scheduled meetings in the current filtered calendar set.
                    </div>
                </div>
                <div class="btn-group shadow-sm" role="group">
                    <button type="button" class="btn btn-light border" id="calendarPrev">‹ Prev</button>
                    <button type="button" class="btn btn-light border fw-bold" id="calendarToday">Today</button>
                    <button type="button" class="btn btn-light border" id="calendarNext">Next ›</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="appointment-calendar-help px-4 py-3 border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="small text-muted">
                        Click any meeting to open its detail page. Week and day views show participants, company, location, and status directly on each card.
                    </div>
                    <div class="appointment-calendar-legend">
                        <span class="appointment-legend-item is-pending">Pending</span>
                        <span class="appointment-legend-item is-confirmed">Confirmed</span>
                        <span class="appointment-legend-item is-completed">Completed</span>
                        <span class="appointment-legend-item is-cancelled">Cancelled</span>
                        <span class="appointment-legend-item is-declined">Declined</span>
                    </div>
                </div>
            </div>
            <div id="appointment-calendar" class="p-3"></div>
        </div>
    </div>
</div>

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <style>
        .appointment-calendar-shell {
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .appointment-calendar-title {
            color: #0f172a;
        }

        .appointment-calendar-help {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .appointment-calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .appointment-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .appointment-legend-item::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
        }

        .appointment-legend-item.is-pending { color: #9a3412; background: #fff7ed; border-color: #fdba74; }
        .appointment-legend-item.is-confirmed { color: #166534; background: #ecfdf3; border-color: #86efac; }
        .appointment-legend-item.is-completed { color: #1d4ed8; background: #eff6ff; border-color: #93c5fd; }
        .appointment-legend-item.is-cancelled { color: #b91c1c; background: #fef2f2; border-color: #fca5a5; }
        .appointment-legend-item.is-declined { color: #475569; background: #f8fafc; border-color: #cbd5e1; }

        #appointment-calendar {
            min-height: 650px;
        }

        .fc-theme-standard td, .fc-theme-standard th { border-color: #e2e8f0; }
        .fc-col-header-cell { background-color: #f8fafc; padding: 12px 0; font-weight: 600; color: #475569; text-transform: uppercase; font-size: 13px;}
        .fc-scrollgrid,
        .fc-theme-standard .fc-scrollgrid {
            border-color: #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }
        .fc .fc-view-harness {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }
        .fc .fc-timegrid-slot-label-cushion,
        .fc .fc-timegrid-axis-cushion {
            color: #64748b;
            font-weight: 600;
        }
        .fc .fc-daygrid-day-number {
            color: #0f172a;
            font-weight: 600;
            padding: 10px;
        }

        .fc-event,
        .fc-h-event,
        .fc-v-event,
        .fc-daygrid-event,
        .fc-timegrid-event {
            cursor: pointer !important;
            border: 1px solid var(--appointment-border, #334155) !important;
            background: linear-gradient(180deg, var(--appointment-bg-soft, #475569) 0%, var(--appointment-bg, #334155) 100%) !important;
            color: var(--appointment-text, #ffffff) !important;
            padding: 0 !important;
            font-size: 0.85rem;
            border-radius: 14px !important;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
            overflow: hidden;
        }
        .fc-event:hover,
        .fc-h-event:hover,
        .fc-v-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .fc-event-main {
            padding: 0 !important;
        }

        .fc-daygrid-event-dot {
            display: none !important;
        }

        .appointment-event {
            color: var(--appointment-text, #ffffff);
            padding: 8px 10px;
            min-height: 100%;
            line-height: 1.25;
            background: linear-gradient(135deg, rgba(255,255,255,0.16) 0%, rgba(255,255,255,0.04) 100%);
        }

        .appointment-event--timegrid {
            padding: 8px 10px 9px;
        }

        .appointment-event-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }

        .appointment-event-location {
            font-size: 11px;
            color: var(--appointment-muted, rgba(255,255,255,0.86));
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .appointment-event-time {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--appointment-muted, rgba(255,255,255,0.88));
            margin-bottom: 4px;
        }

        .appointment-event-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--appointment-text, #ffffff);
            margin-bottom: 4px;
            white-space: normal;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .appointment-event-meta {
            font-size: 11px;
            color: var(--appointment-muted, rgba(255,255,255,0.86));
            white-space: normal;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .appointment-event-status {
            margin-top: 6px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--appointment-text, #ffffff);
            background: var(--appointment-chip-bg, rgba(255,255,255,0.18));
            border: 1px solid var(--appointment-chip-border, rgba(255,255,255,0.26));
            border-radius: 999px;
            display: inline-block;
            padding: 2px 7px;
        }

        .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700; color: #0f172a; }
        .fc-button-primary { background-color: #ffffff !important; color: #475569 !important; border-color: #e2e8f0 !important; text-transform: capitalize !important;}
        .fc-button-active { background-color: #f1f5f9 !important; color: #0f172a !important; font-weight: 600; }
        .fc-day-today { background-color: rgba(79, 70, 229, 0.03) !important; }
        .fc-timegrid-slot { height: 3.5em; }
        .fc-timegrid-event-harness-inset .fc-timegrid-event,
        .fc-timegrid-event {
            inset-inline: 2px;
        }
        .fc-timegrid-event .fc-event-main {
            height: 100%;
        }
        .fc-timegrid-event .appointment-event {
            height: 100%;
        }
        .fc-timegrid-event .appointment-event-title {
            -webkit-line-clamp: 1;
        }
        .fc-timegrid-event .appointment-event-meta {
            -webkit-line-clamp: 1;
        }
        .fc-list-event-title a,
        .fc-list-event-time {
            color: #0f172a !important;
        }
        .fc-list-event:hover td {
            background: #f8fafc !important;
        }
        .fc-list-event-dot {
            border-color: var(--fc-event-border-color, #475569) !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        let appointmentCalendar;
        const appointmentStatusThemes = {
            pending: {
                bg: '#d97706',
                bgSoft: '#f59e0b',
                border: '#b45309',
                text: '#fffaf0',
                muted: 'rgba(255, 247, 237, 0.88)',
                chipBg: 'rgba(255, 255, 255, 0.14)',
                chipBorder: 'rgba(255, 255, 255, 0.22)',
            },
            confirmed: {
                bg: '#15803d',
                bgSoft: '#22c55e',
                border: '#166534',
                text: '#f0fdf4',
                muted: 'rgba(240, 253, 244, 0.86)',
                chipBg: 'rgba(255, 255, 255, 0.16)',
                chipBorder: 'rgba(255, 255, 255, 0.24)',
            },
            completed: {
                bg: '#1d4ed8',
                bgSoft: '#3b82f6',
                border: '#1e40af',
                text: '#eff6ff',
                muted: 'rgba(239, 246, 255, 0.88)',
                chipBg: 'rgba(255, 255, 255, 0.18)',
                chipBorder: 'rgba(255, 255, 255, 0.25)',
            },
            cancelled: {
                bg: '#b91c1c',
                bgSoft: '#ef4444',
                border: '#991b1b',
                text: '#fef2f2',
                muted: 'rgba(254, 242, 242, 0.88)',
                chipBg: 'rgba(255, 255, 255, 0.14)',
                chipBorder: 'rgba(255, 255, 255, 0.22)',
            },
            declined: {
                bg: '#475569',
                bgSoft: '#64748b',
                border: '#334155',
                text: '#f8fafc',
                muted: 'rgba(248, 250, 252, 0.86)',
                chipBg: 'rgba(255, 255, 255, 0.14)',
                chipBorder: 'rgba(255, 255, 255, 0.22)',
            },
        };

        // Changed to turbo:load for SPA support
        document.addEventListener('turbo:load', function() {
            const calendarEl = document.getElementById('appointment-calendar');
            if (!calendarEl) return;

            // Use $calendarEvents from the PHP Screen query
            let calendarEvents = [];
            let eventStartDate = @json(isset($eventStartDate) ? $eventStartDate : null);

            try {
                calendarEvents = @json($calendarEvents ?? []);
            } catch (e) {
                console.error('Failed to parse calendar events:', e);
            }

            // Destroy previous instance if it exists (prevents duplicates on turbo back/forward)
            if (appointmentCalendar) {
                appointmentCalendar.destroy();
            }

            appointmentCalendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek', // Default to week view for appointments (better UX)
                initialDate: eventStartDate || new Date(),
                headerToolbar: {
                    left: '',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List'
                },
                events: calendarEvents,
                eventMinHeight: 68,
                eventShortHeight: 56,
                slotEventOverlap: false,
                eventContent: function(arg) {
                    const props = arg.event.extendedProps || {};
                    const title = escapeHtml(arg.event.title || 'Meeting');
                    const company = props.company ? escapeHtml(props.company) : 'Independent';
                    const location = escapeHtml(props.location || 'TBD');
                    const status = escapeHtml((props.status || 'scheduled').toUpperCase());
                    const viewType = arg.view.type;
                    const participantLine = escapeHtml(`${props.booker || 'Visitor'} -> ${props.target || 'Exhibitor'}`);

                    if (viewType.startsWith('list')) {
                        return {
                            html: `
                                <div class="py-1">
                                    <div class="fw-semibold">${title}</div>
                                    <div class="small text-muted">${company} · ${location}</div>
                                </div>
                            `
                        };
                    }

                    if (viewType.startsWith('timeGrid')) {
                        return {
                            html: `
                                <div class="appointment-event appointment-event--timegrid">
                                    <div class="appointment-event-top">
                                        <div class="appointment-event-time">${arg.timeText || 'Scheduled'}</div>
                                        <div class="appointment-event-status">${status}</div>
                                    </div>
                                    <div class="appointment-event-title">${participantLine}</div>
                                    <div class="appointment-event-meta">${company}</div>
                                    <div class="appointment-event-location">${location}</div>
                                </div>
                            `
                        };
                    }

                    return {
                        html: `
                            <div class="appointment-event">
                                <div class="appointment-event-time">${arg.timeText || 'Scheduled'}</div>
                                <div class="appointment-event-title">${title}</div>
                                <div class="appointment-event-meta">${company} · ${location}</div>
                                <div class="appointment-event-status">${status}</div>
                            </div>
                        `
                    };
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url || info.event.extendedProps.detailUrl) {
                        window.location.assign(info.event.url || info.event.extendedProps.detailUrl);
                    }
                },
                eventDidMount: function(info) {
                    const props = info.event.extendedProps || {};
                    const theme = appointmentStatusThemes[props.status] || appointmentStatusThemes.declined;

                    info.el.style.setProperty('--appointment-bg', theme.bg);
                    info.el.style.setProperty('--appointment-bg-soft', theme.bgSoft);
                    info.el.style.setProperty('--appointment-border', theme.border);
                    info.el.style.setProperty('--appointment-text', theme.text);
                    info.el.style.setProperty('--appointment-muted', theme.muted);
                    info.el.style.setProperty('--appointment-chip-bg', theme.chipBg);
                    info.el.style.setProperty('--appointment-chip-border', theme.chipBorder);
                    info.el.setAttribute('title', `${props.booker || 'Visitor'} -> ${props.target || 'Exhibitor'} | ${props.company || 'Independent'} | ${props.location || 'TBD'}`);
                },
                height: 'auto',
                slotMinTime: '07:00:00',
                slotMaxTime: '21:00:00',
                allDaySlot: false,
                nowIndicator: true,
                navLinks: true,
                selectable: true,
                businessHours: { daysOfWeek: [1, 2, 3, 4, 5, 6], startTime: '08:00', endTime: '18:00' },
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: 'short' },
            });

            appointmentCalendar.render();

            // FIX: Hidden Tab Rendering Issue
            // Use ResizeObserver so FullCalendar redraws itself properly the moment the tab becomes visible
            const resizeObserver = new ResizeObserver(() => {
                if (calendarEl.offsetWidth > 0) {
                    appointmentCalendar.updateSize();
                }
            });
            resizeObserver.observe(calendarEl);

            document.querySelectorAll('[data-bs-toggle="tab"], [data-toggle="tab"]').forEach((tabTrigger) => {
                tabTrigger.addEventListener('shown.bs.tab', () => {
                    setTimeout(() => appointmentCalendar?.updateSize(), 10);
                });
            });

            // Custom Navigation buttons
            document.getElementById('calendarToday')?.addEventListener('click', () => appointmentCalendar.today());
            document.getElementById('calendarPrev')?.addEventListener('click', () => appointmentCalendar.prev());
            document.getElementById('calendarNext')?.addEventListener('click', () => appointmentCalendar.next());
        });

        function openEditModal(appointmentId) {
            const tempLink = document.createElement('a');
            tempLink.setAttribute('data-turbo-method', 'get');
            tempLink.setAttribute('data-turbo', 'true');
            tempLink.setAttribute('data-modal', 'editAppointmentModal');
            tempLink.setAttribute('data-modal-title', 'Edit Appointment');
            tempLink.setAttribute('data-async-route', window.location.pathname + '/async/asyncGetAppointment');
            tempLink.setAttribute('data-async-parameters', JSON.stringify({appointment: appointmentId}));
            tempLink.style.display = 'none';
            document.body.appendChild(tempLink);

            setTimeout(() => {
                tempLink.click();
                setTimeout(() => document.body.removeChild(tempLink), 100);
            }, 10);
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
@endpush
@include('admin.appointment.modal-scripts')
