<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->string('update_url')->nullable()->after('app_version');
            $table->string('android_update_url')->nullable()->after('update_url');
            $table->string('ios_update_url')->nullable()->after('android_update_url');
        });
    }

    public function down(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn([
                'update_url',
                'android_update_url',
                'ios_update_url',
            ]);
        });
    }
};
