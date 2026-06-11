<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('category_translations')->nullable()->after('category');
            $table->json('address_translations')->nullable()->after('address');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('type_translations')->nullable()->after('type');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('conferences', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('speakers', function (Blueprint $table) {
            $table->json('job_title_translations')->nullable()->after('job_title');
            $table->json('bio_translations')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('speakers', function (Blueprint $table) {
            $table->dropColumn(['job_title_translations', 'bio_translations']);
        });

        Schema::table('conferences', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'description_translations']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'type_translations', 'description_translations']);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('name_translations');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'name_translations',
                'category_translations',
                'address_translations',
                'description_translations',
            ]);
        });
    }
};
