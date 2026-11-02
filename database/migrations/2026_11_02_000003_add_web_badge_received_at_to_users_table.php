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
            // When the user first got their website badge code (HCX-...).
            $table->timestamp('web_badge_received_at')->nullable()->index();
        });

        // Codes stored before this column existed: the last check is the closest date we have.
        DB::table('users')
            ->whereNotNull('web_badge_code')
            ->update(['web_badge_received_at' => DB::raw('coalesce(web_badge_checked_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['web_badge_received_at']);
            $table->dropColumn('web_badge_received_at');
        });
    }
};
