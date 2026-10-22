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
            // email, google, apple, linkedin, facebook, social, wordpress, admin
            $table->string('signup_method', 20)->nullable()->after('created_source')->index();
            $table->string('apple_id')->nullable()->after('google_id');
        });

        // Existing users: only what can be known for sure. The rest stay NULL
        // ("Unknown"): email and finished social sign-ups look the same.
        DB::table('users')
            ->where('created_source', 'wordpress')
            ->update(['signup_method' => 'wordpress']);

        // Social login creates accounts with these placeholders; only
        // unfinished social sign-ups still have them. Provider was not recorded.
        DB::table('users')
            ->whereNull('signup_method')
            ->where('created_source', 'app')
            ->where('password_is_set', true)
            ->where(fn ($q) => $q->where('phone', 'N/A')->orWhere('city', 'N/A')->orWhere('country', 'N/A'))
            ->update(['signup_method' => 'social']);

        DB::table('users')->whereNull('signup_method')->whereNotNull('google_id')->update(['signup_method' => 'google']);
        DB::table('users')->whereNull('signup_method')->whereNotNull('linkedin_id')->update(['signup_method' => 'linkedin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['signup_method']);
            $table->dropColumn(['signup_method', 'apple_id']);
        });
    }
};
