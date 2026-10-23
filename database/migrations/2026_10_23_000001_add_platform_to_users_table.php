<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Phone the user uses now (android / ios), from the app's X-Platform header.
            $table->string('platform', 10)->nullable()->after('signup_method')->index();
            $table->string('app_version', 20)->nullable()->after('platform');
            $table->timestamp('platform_seen_at')->nullable()->after('app_version');
            // Phone used to create the account.
            $table->string('signup_platform', 10)->nullable()->after('platform_seen_at')->index();
        });

        // Older app versions do not send a reliable platform. Fill in iOS only
        // where it is certain: Apple sign-in exists only in the iPhone app, and
        // THIRD_PARTY_AUTH_ERROR (APNs) only happens for iPhone push tokens.
        DB::table('users')
            ->where(fn ($q) => $q->where('signup_method', 'apple')->orWhereNotNull('apple_id'))
            ->update(['platform' => 'ios', 'signup_platform' => 'ios']);

        if (Schema::hasTable('push_notification_logs')) {
            DB::table('users')
                ->whereNull('platform')
                ->whereIn('id', DB::table('push_notification_logs')
                    ->where('error_code', 'THIRD_PARTY_AUTH_ERROR')
                    ->whereNotNull('user_id')
                    ->select('user_id'))
                ->update(['platform' => 'ios']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropIndex(['signup_platform']);
            $table->dropColumn(['platform', 'app_version', 'platform_seen_at', 'signup_platform']);
        });
    }
};
