@if(!empty($queueStuck))
    <div class="alert alert-danger mb-3">
        <strong>Notifications are stuck in the queue.</strong>
        The oldest one has been waiting {{ $metrics['oldest']['value'] ?? '' }}.
        The server cron (<code>* * * * * php artisan schedule:run</code>) is probably not running.
        Click <em>Process queue now</em> to send them, then check the cron job in Hostinger hPanel.
    </div>
@endif
