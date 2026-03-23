<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_wordpress_synced')->default(false)->after('app_role');
            $table->string('wordpress_sync_source')->nullable()->after('is_wordpress_synced');
            $table->timestamp('wordpress_synced_at')->nullable()->after('wordpress_sync_source');
            $table->text('wordpress_sync_error')->nullable()->after('wordpress_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_wordpress_synced',
                'wordpress_sync_source',
                'wordpress_synced_at',
                'wordpress_sync_error',
            ]);
        });
    }
};
