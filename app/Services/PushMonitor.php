<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numbers behind the admin "Push Notifications" page and the health alert.
 */
class PushMonitor
{
    /** Waiting jobs, age of the oldest one (seconds) and failed jobs. */
    public static function queue(): array
    {
        $oldest = DB::table('jobs')->whereNull('reserved_at')->min('created_at');

        return [
            'waiting'        => DB::table('jobs')->count(),
            'oldest_seconds' => $oldest ? max(0, now()->getTimestamp() - (int) $oldest) : 0,
            'failed'         => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];
    }

    /** Sent / failed / skipped counts since the given time. */
    public static function totals(\DateTimeInterface $since): array
    {
        $counts = PushNotificationLog::where('created_at', '>=', $since)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'sent'    => (int) ($counts[PushNotificationLog::STATUS_SENT] ?? 0),
            'failed'  => (int) ($counts[PushNotificationLog::STATUS_FAILED] ?? 0),
            'skipped' => (int) ($counts[PushNotificationLog::STATUS_SKIPPED] ?? 0),
        ];
    }

    /** Per notification type over the last 7 days. */
    public static function byType(): array
    {
        return PushNotificationLog::where('created_at', '>=', now()->subDays(7))
            ->selectRaw("notification,
                sum(case when status = 'sent' then 1 else 0 end) as sent,
                sum(case when status = 'failed' then 1 else 0 end) as failed,
                sum(case when status = 'skipped' then 1 else 0 end) as skipped,
                max(created_at) as last_at")
            ->groupBy('notification')
            ->orderByDesc('last_at')
            ->get()
            ->map(fn ($row) => [
                'notification' => $row->notification,
                'sent'         => (int) $row->sent,
                'failed'       => (int) $row->failed,
                'skipped'      => (int) $row->skipped,
                'last_at'      => (string) $row->last_at,
            ])
            ->all();
    }

    /** Failure and skip reasons over the last 7 days. */
    public static function reasons(): array
    {
        return PushNotificationLog::where('created_at', '>=', now()->subDays(7))
            ->where('status', '!=', PushNotificationLog::STATUS_SENT)
            ->selectRaw('error_code, max(error_message) as error_message, count(*) as total, max(created_at) as last_at')
            ->groupBy('error_code')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'error_code'    => $row->error_code ?? 'unknown',
                'error_message' => (string) $row->error_message,
                'hint'          => self::hint($row->error_code),
                'total'         => (int) $row->total,
                'last_at'       => (string) $row->last_at,
            ])
            ->all();
    }

    /** Plain-language explanation of common FCM errors. */
    public static function hint(?string $code): string
    {
        return match ($code) {
            'THIRD_PARTY_AUTH_ERROR', 'UNAUTHENTICATED' => 'iPhone pushes: the APNs key in Firebase (Project settings → Cloud Messaging) is missing or invalid.',
            'UNREGISTERED', 'NOT_FOUND' => 'App uninstalled or token expired. The token is removed automatically.',
            'SENDER_ID_MISMATCH' => 'Token belongs to another Firebase project. Removed automatically.',
            'no_token' => 'User has not opened the app since installing, or refused notifications.',
            'oauth_token' => 'Server could not authenticate with Firebase: check storage/app/firebase-credentials.json.',
            'QUOTA_EXCEEDED' => 'Too many messages sent too quickly. Retry later.',
            'UNAVAILABLE', 'INTERNAL' => 'Temporary Firebase problem. Usually resolves itself.',
            default => '',
        };
    }
}
