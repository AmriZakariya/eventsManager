<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('notification')->index(); // short class name, e.g. NewMessageReceived
            $table->string('title')->nullable();
            $table->string('status', 16)->index(); // sent, failed, skipped
            $table->string('error_code')->nullable()->index(); // UNREGISTERED, THIRD_PARTY_AUTH_ERROR, no_token, ...
            $table->string('error_message')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['user_id', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
