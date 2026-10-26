<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set by an admin to block the account. The row is kept on purpose, so the
            // same email and social logins cannot simply sign up again.
            $table->timestamp('banned_at')->nullable()->index();
            $table->string('ban_reason', 500)->nullable();
            $table->unsignedBigInteger('banned_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['banned_at']);
            $table->dropColumn(['banned_at', 'ban_reason', 'banned_by']);
        });
    }
};
