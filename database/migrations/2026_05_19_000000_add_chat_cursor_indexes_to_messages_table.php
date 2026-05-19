<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['sender_id', 'receiver_id', 'id'], 'messages_sender_receiver_id_index');
            $table->index(['receiver_id', 'sender_id', 'read_at'], 'messages_receiver_sender_read_index');
            $table->index(['receiver_id', 'read_at'], 'messages_receiver_read_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_sender_receiver_id_index');
            $table->dropIndex('messages_receiver_sender_read_index');
            $table->dropIndex('messages_receiver_read_index');
        });
    }
};
