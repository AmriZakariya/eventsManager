{{-- resources/views/admin/appointment/calendar.blade.php --}}
<div class="mb-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold" style="color: #0f172a;">
                    <i class="bi bi-calendar3 text-primary me-2"></i> Meeting Schedule
                </h5>
                <div class="btn-group shadow-sm" role="group">
                    <button type="button" class="btn btn-light border" id="calendarPrev">‹ Prev</button>
                    <button type="button" class="btn btn-light border fw-bold" id="calendarToday">Today</button>
                    <button type="button" class="btn btn-light border" id="calendarNext">Next ›</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="appointment-calendar" class="p-3"></div>
        </div>
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <span class="badge text-dark bg-white border shadow-sm px-3 py-2"><span style="color: #ffc107;">●</span> Pending</span>
                <span class="badge text-dark bg-white border shadow-sm px-3 py-2"><span style="color: #198754;">●</span> Confirmed</span>
                <span class="badge text-dark bg-white border shadow-sm px-3 py-2"><span style="color: #0d6efd;">●</span> Completed</span>
                <span class="badge text-dark bg-white border shadow-sm px-3 py-2"><span style="color: #dc3545;">●</span> Cancelled</span>
                <span class="badge text-dark bg-white border shadow-sm px-3 py-2"><span style="color: #6c757d;">●</span> Declined</span>
            </div>
        </div>
    </div>
</div>

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <style>
        #appointment-calendar {
            min-height: 650px;
            font-family: 'Inter', sans-serif;
        }

        .fc-theme-standard td, .fc-theme-standard th { border-color: #e2e8f0; }
        .fc-col-header-cell { background-color: #f8fafc; padding: 12px 0; font-weight: 600; color: #475569; text-transform: uppercase; font-size: 13px;}

        .fc-event {
            cursor: pointer !important;
            border: none !important;
            padding: 3px 6px;
            font-size: 0.85rem;
            border-radius: 6px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .fc-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            filter: brightness(0.95);
        }

        .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700; color: #0f172a; }
        .fc-button-primary { background-color: #ffffff !important; color: #475569 !important; border-color: #e2e8f0 !important; text-transform: capitalize !important;}
        .fc-button-active { background-color: #f1f5f9 !important; color: #0f172a !important; font-weight: 600; }
        .fc-day-today { background-color: rgba(79, 70, 229, 0.03) !important; }
        .fc-timegrid-slot { height: 3.5em; }

        /* Tooltip cleanup */
        .tooltip-inner { text-align: left; padding: 12px; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        let appointmentCalendar;

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
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.extendedProps.appointmentId) {
                        // Redirect to the detail screen URL.
                        // Note: Update '/admin/appointments/' if your route prefix is different!
                        window.location.href = '/admin/appointments/' + info.event.extendedProps.appointmentId;
                    }
                },
                eventDidMount: function(info) {
                    const props = info.event.extendedProps;
                    const startTime = new Date(info.event.start).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

                    const tooltipHtml = `
                        <div style="max-width: 250px;">
                            <strong style="display:block;margin-bottom:6px;font-size:14px;">Meeting Details</strong>
                            <div style="font-size:13px; line-height:1.5;">
                                👤 <b>${props.booker}</b><br>
                                🤝 <b>${props.target}</b><br>
                                ${props.company ? '🏢 ' + props.company + '<br>' : ''}
                                📍 ${props.location}<br>
                                ⏰ ${startTime} (${props.duration} min)<br>
                                <span class="badge bg-${getStatusBadge(props.status)} mt-2">${props.status.toUpperCase()}</span>
                            </div>
                        </div>
                    `;

                    info.el.setAttribute('data-bs-toggle', 'tooltip');
                    info.el.setAttribute('data-bs-html', 'true');
                    info.el.setAttribute('title', tooltipHtml);

                    if (typeof bootstrap !== 'undefined') {
                        new bootstrap.Tooltip(info.el);
                    }
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

        function getStatusBadge(status) {
            const badges = { 'confirmed': 'success', 'pending': 'warning', 'cancelled': 'danger', 'completed': 'primary', 'declined': 'secondary' };
            return badges[status] || 'light';
        }
    </script>
@endpush
@include('admin.appointment.modal-scripts')
