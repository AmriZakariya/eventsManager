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
            $table->string('app_role')->nullable()->default('visitor')->after('locale')->index();
        });

        DB::statement("
            UPDATE users
            SET app_role = CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM role_users
                    INNER JOIN roles ON roles.id = role_users.role_id
                    WHERE role_users.user_id = users.id
                      AND roles.slug = 'admin'
                ) THEN 'admin'
                WHEN EXISTS (
                    SELECT 1
                    FROM role_users
                    INNER JOIN roles ON roles.id = role_users.role_id
                    WHERE role_users.user_id = users.id
                      AND roles.slug = 'exhibitor'
                ) THEN 'exhibitor'
                WHEN EXISTS (
                    SELECT 1
                    FROM role_users
                    INNER JOIN roles ON roles.id = role_users.role_id
                    WHERE role_users.user_id = users.id
                      AND roles.slug = 'visitor'
                ) THEN 'visitor'
                WHEN company_id IS NOT NULL THEN 'exhibitor'
                ELSE 'visitor'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['app_role']);
            $table->dropColumn('app_role');
        });
    }
};
