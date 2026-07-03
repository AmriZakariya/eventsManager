<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('award_nominees', function (Blueprint $table) {
            $table->dropForeign(['award_category_id']);
        });

        Schema::table('award_nominees', function (Blueprint $table) {
            $table->foreignId('award_category_id')
                ->nullable()
                ->change();
        });

        Schema::table('award_nominees', function (Blueprint $table) {
            $table->foreign('award_category_id')
                ->references('id')
                ->on('award_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('award_nominees')
            ->whereNull('award_category_id')
            ->delete();

        Schema::table('award_nominees', function (Blueprint $table) {
            $table->dropForeign(['award_category_id']);
        });

        Schema::table('award_nominees', function (Blueprint $table) {
            $table->foreignId('award_category_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('award_nominees', function (Blueprint $table) {
            $table->foreign('award_category_id')
                ->references('id')
                ->on('award_categories')
                ->cascadeOnDelete();
        });
    }
};
