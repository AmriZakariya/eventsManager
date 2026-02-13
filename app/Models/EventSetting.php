<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class EventSetting extends Model
{
    use AsSource;

    protected $fillable = [
        // Branding & Identity
        'event_name',
        'app_logo',
        'primary_color',
        'secondary_color',
        'accent_color',
        'tagline',

        // Event Info
        'description',
        'start_date',
        'end_date',
        'location_name',
        'location_address',
        'latitude',
        'longitude',
        'floor_plan_image',
        'venue_image',

        // Operational Logic
        'opening_hour',
        'closing_hour',
        'meeting_duration_minutes',
        'meeting_buffer_minutes',
        'max_meetings_per_day',
        'enable_meeting_requests',
        'auto_confirm_meetings',

        // Features
        'enable_notifications',
        'enable_chat',
        'enable_qr_checkin',
        'enable_networking',
        'enable_exhibitor_scanning',
        'enable_social_wall',
        'show_attendee_list',
        'enable_offline_mode',

        // Contact & Support
        'support_email',
        'support_phone',
        'website_url',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'emergency_info',

        // Advanced
        'api_key',
        'app_version',
        'maintenance_mode',
        'maintenance_message',
        'timezone',
        'language',
        'available_languages',
        'default_language',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'available_languages' => 'array',

        // Boolean casts
        'enable_meeting_requests' => 'boolean',
        'auto_confirm_meetings' => 'boolean',
        'enable_notifications' => 'boolean',
        'enable_chat' => 'boolean',
        'enable_qr_checkin' => 'boolean',
        'enable_networking' => 'boolean',
        'enable_exhibitor_scanning' => 'boolean',
        'enable_social_wall' => 'boolean',
        'show_attendee_list' => 'boolean',
        'enable_offline_mode' => 'boolean',
        'maintenance_mode' => 'boolean',
    ];

    protected $attributes = [
        'primary_color' => '#D4AF37',
        'secondary_color' => '#0F172A',
        'accent_color' => '#F59E0B',
        'opening_hour' => '10:00:00',
        'closing_hour' => '18:00:00',
        'meeting_duration_minutes' => 30,
        'meeting_buffer_minutes' => 5,
        'max_meetings_per_day' => 10,
        'enable_meeting_requests' => true,
        'auto_confirm_meetings' => false,
        'enable_notifications' => true,
        'enable_chat' => true,
        'enable_qr_checkin' => true,
        'enable_networking' => true,
        'enable_exhibitor_scanning' => true,
        'enable_social_wall' => false,
        'show_attendee_list' => true,
        'enable_offline_mode' => true,
        'maintenance_mode' => false,
        'timezone' => 'Africa/Casablanca',
        'language' => 'en',
    ];

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (is_string($value)) {
            // Force convert to UTF-8, stripping invalid byte sequences
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return $value;
    }

    public function getAppLogoUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset($this->image);
    }

    public function getAvailableLanguagesAttribute($value)
    {
        $languages = json_decode($value, true) ?? [];

        // Default languages if not set
        if (empty($languages)) {
            return [
                ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧', 'enabled' => true],
                ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷', 'enabled' => true],
                ['code' => 'ar', 'name' => 'العربية', 'flag' => '🇸🇦', 'enabled' => true],
            ];
        }

        return $languages;
    }

    /**
     * Get enabled languages only
     */
    public function getEnabledLanguages()
    {
        return collect($this->available_languages)
            ->filter(fn($lang) => $lang['enabled'] ?? true)
            ->values()
            ->toArray();
    }

    /**
     * Get translation file content for a language
     */
    public function getTranslationFile(string $languageCode): array
    {
        $path = resource_path("lang/{$languageCode}.json");

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        return json_decode($content, true) ?? [];
    }

    /**
     * Save translation file
     */
    public function saveTranslationFile(string $languageCode, array $translations): bool
    {
        $path = resource_path("lang/{$languageCode}.json");

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($path, $content) !== false;
    }
}
