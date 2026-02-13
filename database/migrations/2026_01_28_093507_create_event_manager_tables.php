<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 0. STANDARD LARAVEL NOTIFICATIONS
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // =========================================================================
        // 1. GLOBAL SETTINGS & HOME LAYOUT
        // =========================================================================

        // General Event Config
        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();

            // 1. Branding & Identity
            $table->string('event_name');
            $table->string('app_logo')->nullable();
            $table->string('primary_color')->default('#D4AF37');
            $table->string('secondary_color')->default('#0F172A');
            $table->string('accent_color')->default('#F59E0B');
            $table->string('tagline')->nullable();

            // 2. Event Info
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('location_name')->nullable();
            $table->text('location_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('floor_plan_image')->nullable();
            $table->string('venue_image')->nullable();

            // 3. Operational Logic
            $table->time('opening_hour')->default('10:00:00');
            $table->time('closing_hour')->default('18:00:00');
            $table->integer('meeting_duration_minutes')->default(30);
            $table->integer('meeting_buffer_minutes')->default(5);
            $table->integer('max_meetings_per_day')->default(10);
            $table->boolean('enable_meeting_requests')->default(true);
            $table->boolean('auto_confirm_meetings')->default(false);

            // 4. Features
            $table->boolean('enable_notifications')->default(true);
            $table->boolean('enable_chat')->default(true);
            $table->boolean('enable_qr_checkin')->default(true);
            $table->boolean('enable_networking')->default(true);
            $table->boolean('enable_exhibitor_scanning')->default(true);
            $table->boolean('enable_social_wall')->default(false);
            $table->boolean('show_attendee_list')->default(true);
            $table->boolean('enable_offline_mode')->default(true);

            // 5. Contact
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('emergency_info')->nullable();

            // 6. Advanced
            $table->string('api_key')->nullable()->unique();
            $table->string('app_version')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->string('timezone')->default('Africa/Casablanca');
            $table->string('language')->default('en');
            $table->json('available_languages')->nullable();
            $table->string('default_language')->default('en');

            $table->timestamps();
        });

        // Home Screen Sections (The Container)
        Schema::create('home_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable(); // e.g. "Main Slider", "Platinum Sponsors"
            $table->string('identifier')->nullable();

            // LOGIC: 'slider', 'menu_grid', 'logo_cloud', 'single_banner', 'dynamic_list'
            $table->string('widget_type');
            $table->string('image')->nullable();  // Upload path
            $table->string('icon')->nullable();   // Material Icon name (e.g. "calendar_today")

            // FOR DYNAMIC LISTS: 'companies', 'products', 'speakers'
            $table->string('data_source')->nullable();

            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. ITEMS (The Manual Content)
        Schema::create('home_widget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_widget_id')->constrained('home_widgets')->cascadeOnDelete();

            $table->string('image')->nullable();  // Upload path
            $table->string('icon')->nullable();   // Material Icon name (e.g. "calendar_today")
            $table->string('title')->nullable();
            $table->string('identifier')->nullable();
            $table->string('subtitle')->nullable();

            // ACTION: Internal Route or External Link
            $table->string('action_url')->nullable();

            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // =========================================================================
        // 2. COMPANIES (Exhibitors)
        // =========================================================================
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('booth_number')->nullable();
            $table->string('country')->nullable();
            $table->string('category')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->json('map_coordinates')->nullable(); // {x: 100, y: 500}
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });

        // =========================================================================
        // 3. USERS (Visitors & Exhibitor Team)
        // =========================================================================
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->after('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('job_title')->nullable();

            // Link to Company (Exhibitor Team)
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            // Social & Notification
            $table->string('linkedin_url')->nullable();
            $table->string('linkedin_id')->nullable();
            $table->string('google_id')->nullable();
            $table->string('badge_code')->nullable();
            $table->string('fcm_token')->nullable();
            $table->boolean('is_visible')->default(true);
        });

        // Push Notifications History
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('type')->nullable(); // 'alert', 'info', 'promo'
            $table->json('data')->nullable();   // Payload
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // =========================================================================
        // 4. PRODUCTS
        // =========================================================================
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('type')->nullable(); // Extra filter string
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        // =========================================================================
        // 5. CONFERENCES & SPEAKERS
        // =========================================================================
        Schema::create('conferences', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('conference');
            $table->timestamps();
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('job_title')->nullable();
            $table->string('company_name')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('conference_speaker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
        });

        // User Registrations for Conferences
        Schema::create('conference_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conference_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'conference_id']);
        });

        // =========================================================================
        // 6. NETWORKING & B2B
        // =========================================================================
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamps();
            $table->unique(['requester_id', 'target_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->string('attachment_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->string('table_location')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'declined', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->integer('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });


        // =========================================================================
        // 7. EXTRAS (Awards, Favorites)
        // =========================================================================

        Schema::create('award_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('award_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
        });

        Schema::create('jury_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamps();
            $table->unique(['user_id', 'favoritable_id', 'favoritable_type']);
        });

        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_handled')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        // Drop in reverse order to avoid FK constraints issues
        Schema::dropIfExists('contact_requests');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('jury_members');
        Schema::dropIfExists('award_nominees');
        Schema::dropIfExists('award_categories');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('connections');
        Schema::dropIfExists('conference_registrations');
        Schema::dropIfExists('conference_speaker');
        Schema::dropIfExists('speakers');
        Schema::dropIfExists('conferences');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('home_widget_items');
        Schema::dropIfExists('home_widgets');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['last_name', 'phone', 'avatar', 'bio', 'job_title', 'company_id', 'linkedin_url', 'linkedin_id', 'google_id', 'badge_code', 'fcm_token', 'is_visible']);
        });

        Schema::dropIfExists('companies');
        Schema::dropIfExists('event_settings');
        Schema::dropIfExists('notifications');
    }
};
