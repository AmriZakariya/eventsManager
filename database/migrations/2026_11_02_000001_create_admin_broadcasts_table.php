<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per push notification composed and sent from the admin.
        Schema::create('admin_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->string('audience', 20);
            $table->json('user_ids')->nullable();
            $table->json('titles');
            $table->json('bodies');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        // Links each push attempt to its broadcast, for the delivery numbers.
        Schema::table('push_notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('broadcast_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_logs', function (Blueprint $table) {
            $table->dropIndex(['broadcast_id']);
            $table->dropColumn('broadcast_id');
        });

        Schema::dropIfExists('admin_broadcasts');
    }
};
