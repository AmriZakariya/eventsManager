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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action')->index(); // login, logout, create, update, delete, view
            $table->string('resource_type')->nullable()->index(); // User, Event, Product, etc.
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source')->default('web')->index(); // web, api, admin
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id', 'created_at']);
            $table->index(['created_at']);
            $table->index(['action', 'created_at']);

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
