@php
    $waiting = (int) ($queue['waiting'] ?? 0);
    $failedJobs = (int) ($queue['failed'] ?? 0);

    if (! empty($queueStuck)) {
        $tone = 'danger';
        $icon = 'bs.exclamation-octagon';
        $title = 'Notifications are stuck in the queue';
        $text = 'The oldest one has been waiting ' . ($metrics['oldest']['value'] ?? '') . '. The server cron (<code>* * * * * php artisan schedule:run</code>) is probably not running. Click <em>Process queue now</em> to send them, then check the cron job in Hostinger hPanel.';
    } elseif ($failedJobs > 0) {
        $tone = 'warning';
        $icon = 'bs.exclamation-triangle';
        $title = $failedJobs . ' job(s) failed';
        $text = 'Some notifications could not be processed. Click <em>Retry failed jobs</em> to try them again.';
    } else {
        $tone = 'success';
        $icon = 'bs.check-circle';
        $title = 'Delivery is running normally';
        $text = $waiting > 0
            ? $waiting . ' notification(s) waiting; they go out within a minute.'
            : 'The queue is empty: everything has been sent.';
    }

    $colors = [
        'success' => ['#eaf7f0', '#bfe5cf', '#1d6b43'],
        'warning' => ['#fff6e5', '#f3d9a4', '#8a5a00'],
        'danger'  => ['#fdecec', '#f5c2c2', '#8a1f1f'],
    ][$tone];
@endphp
<div role="status" style="display:flex;gap:12px;align-items:flex-start;background:{{ $colors[0] }};border:1px solid {{ $colors[1] }};color:{{ $colors[2] }};border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:14px;">
    <x-orchid-icon path="{{ $icon }}" width="20" height="20"/>
    <div>
        <strong style="display:block;font-size:15px;margin-bottom:2px;">{{ $title }}</strong>
        <span>{!! $text !!}</span>
    </div>
</div>
