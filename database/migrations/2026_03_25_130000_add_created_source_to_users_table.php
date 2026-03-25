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
            $table->string('created_source')->nullable()->after('app_role');
        });

        DB::table('users')
            ->whereNull('created_source')
            ->update([
                'created_source' => DB::raw("
                    CASE
                        WHEN wordpress_sync_source = 'wordpress' THEN 'wordpress'
                        ELSE 'app'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('created_source');
        });
    }
};
