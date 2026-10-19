<div class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="bg-white rounded shadow-sm p-4 h-100">
            <div class="fw-bold">Daily Registrations</div>
            <div class="text-muted small mb-3">New users per day</div>
            <canvas id="reg-chart-daily" height="110"></canvas>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="bg-white rounded shadow-sm p-4 h-100">
            <div class="fw-bold">Weekly Registrations</div>
            <div class="text-muted small mb-3">New users per week (Monday–Sunday)</div>
            <canvas id="reg-chart-weekly" height="150"></canvas>
        </div>
    </div>
</div>

<script>
    (function () {
        const data = @json($chart);

        function draw() {
            const daily = document.getElementById('reg-chart-daily');
            const weekly = document.getElementById('reg-chart-weekly');
            if (!daily || !weekly) return;

            [daily, weekly].forEach(function (canvas) {
                const existing = Chart.getChart(canvas);
                if (existing) existing.destroy();
            });

            const indigo = '#4f46e5';
            const fill = daily.getContext('2d').createLinearGradient(0, 0, 0, 300);
            fill.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
            fill.addColorStop(1, 'rgba(79, 70, 229, 0)');

            const axes = {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 12, autoSkip: true } },
                y: { beginAtZero: true, ticks: { precision: 0 }, border: { dash: [4, 4] } },
            };

            new Chart(daily, {
                type: 'line',
                data: {
                    labels: data.dailyLabels,
                    datasets: [{
                        label: 'New users',
                        data: data.dailyValues,
                        borderColor: indigo,
                        backgroundColor: fill,
                        fill: true,
                        tension: 0.3,
                        pointRadius: data.dailyValues.length > 60 ? 0 : 2,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: axes,
                    interaction: { intersect: false, mode: 'index' },
                },
            });

            new Chart(weekly, {
                type: 'bar',
                data: {
                    labels: data.weeklyLabels,
                    datasets: [{ label: 'New users', data: data.weeklyValues, backgroundColor: indigo, borderRadius: 6 }],
                },
                options: { plugins: { legend: { display: false } }, scales: axes },
            });
        }

        // The page can be opened through Turbo, so Chart.js may not be loaded yet.
        if (window.Chart) {
            draw();
        } else {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.onload = draw;
            document.head.appendChild(script);
        }
    })();
</script>
