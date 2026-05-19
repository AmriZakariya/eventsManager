<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            $table->index(['target_id', 'requester_id', 'status'], 'connections_target_requester_status_index');
            $table->index(['requester_id', 'status'], 'connections_requester_status_index');
            $table->index(['target_id', 'status'], 'connections_target_status_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['booker_id', 'scheduled_at', 'status'], 'appointments_booker_scheduled_status_index');
            $table->index(['target_user_id', 'scheduled_at', 'status'], 'appointments_target_scheduled_status_index');
            $table->index(['status', 'scheduled_at'], 'appointments_status_scheduled_index');
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'app_notifications_user_created_index');
            $table->index(['user_id', 'is_read'], 'app_notifications_user_read_index');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index(['is_active', 'name'], 'companies_active_name_index');
            $table->index(['is_active', 'is_featured', 'name'], 'companies_active_featured_name_index');
            $table->index(['is_active', 'booth_number'], 'companies_active_booth_index');
            $table->index(['category', 'country'], 'companies_category_country_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_featured', 'created_at'], 'products_featured_created_index');
            $table->index(['category_id', 'is_featured', 'created_at'], 'products_category_featured_created_index');
            $table->index(['company_id', 'is_featured'], 'products_company_featured_index');
        });

        Schema::table('home_widgets', function (Blueprint $table) {
            $table->index(['is_active', 'order'], 'home_widgets_active_order_index');
        });

        Schema::table('home_widget_items', function (Blueprint $table) {
            $table->index(['home_widget_id', 'order'], 'home_widget_items_widget_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('home_widget_items', function (Blueprint $table) {
            $table->dropIndex('home_widget_items_widget_order_index');
        });

        Schema::table('home_widgets', function (Blueprint $table) {
            $table->dropIndex('home_widgets_active_order_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_featured_created_index');
            $table->dropIndex('products_category_featured_created_index');
            $table->dropIndex('products_company_featured_index');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_active_name_index');
            $table->dropIndex('companies_active_featured_name_index');
            $table->dropIndex('companies_active_booth_index');
            $table->dropIndex('companies_category_country_index');
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex('app_notifications_user_created_index');
            $table->dropIndex('app_notifications_user_read_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_booker_scheduled_status_index');
            $table->dropIndex('appointments_target_scheduled_status_index');
            $table->dropIndex('appointments_status_scheduled_index');
        });

        Schema::table('connections', function (Blueprint $table) {
            $table->dropIndex('connections_target_requester_status_index');
            $table->dropIndex('connections_requester_status_index');
            $table->dropIndex('connections_target_status_index');
        });
    }
};
