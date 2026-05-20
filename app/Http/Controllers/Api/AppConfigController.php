<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AppConfigController extends Controller
{
    private const CONFIG_CACHE_KEYS = [
        'api:config:init',
        'api:config:minimal',
        'api:config:features',
    ];

    /**
     * Get initial app configuration for mobile app
     *
     * @return JsonResponse
     */
    public function init(): JsonResponse
    {
        try {
            $payload = Cache::remember('api:config:init', now()->addMinutes(5), function () {
                $settings = EventSetting::first();

                if (!$settings) {
                    return [
                        'http_status' => 404,
                        'body' => [
                            'status' => 'error',
                            'message' => 'Event configuration not found. Please contact administrator.',
                        ],
                    ];
                }

                if ($settings->maintenance_mode) {
                    return [
                        'http_status' => 503,
                        'body' => [
                            'status' => 'maintenance',
                            'message' => $settings->maintenance_message ?? 'App is under maintenance. Please try again later.',
                            'data' => [
                                ...$this->updateConfig($settings),
                                'maintenance_mode' => true,
                                'maintenance_message' => $settings->maintenance_message,
                            ],
                        ],
                    ];
                }

                return [
                    'http_status' => 200,
                    'body' => [
                        'status' => 'success',
                        'message' => 'Configuration loaded successfully',
                        'data' => [
                            // Branding
                            'event_name' => $settings->event_name,
                            'app_logo' => $settings->app_logo ? asset($settings->app_logo) : null,
                            'primary_color' => $settings->primary_color,
                            'secondary_color' => $settings->secondary_color,
                            'accent_color' => $settings->accent_color,
                            'tagline' => $settings->tagline,
                            'description' => $settings->description,

                            // Event Details
                            'start_date' => $settings->start_date?->toIso8601String(),
                            'end_date' => $settings->end_date?->toIso8601String(),
                            'location_name' => $settings->location_name,
                            'location_address' => $settings->location_address,
                            'latitude' => $settings->latitude,
                            'longitude' => $settings->longitude,
                            'floor_plan_image' => $settings->floor_plan_image ? asset($settings->floor_plan_image) : null,
                            'venue_image' => $settings->venue_image ? asset($settings->venue_image) : null,

                            // Operational
                            'opening_hour' => $settings->opening_hour,
                            'closing_hour' => $settings->closing_hour,
                            'meeting_duration_minutes' => $settings->meeting_duration_minutes,
                            'meeting_buffer_minutes' => $settings->meeting_buffer_minutes,
                            'max_meetings_per_day' => $settings->max_meetings_per_day,
                            'enable_meeting_requests' => $settings->enable_meeting_requests,
                            'auto_confirm_meetings' => $settings->auto_confirm_meetings,

                            // Features
                            'enable_notifications' => $settings->enable_notifications,
                            'enable_chat' => $settings->enable_chat,
                            'enable_qr_checkin' => $settings->enable_qr_checkin,
                            'enable_networking' => $settings->enable_networking,
                            'enable_exhibitor_scanning' => $settings->enable_exhibitor_scanning,
                            'enable_social_wall' => $settings->enable_social_wall,
                            'show_attendee_list' => $settings->show_attendee_list,
                            'enable_offline_mode' => $settings->enable_offline_mode,

                            // Contact & Support
                            'support_email' => $settings->support_email,
                            'support_phone' => $settings->support_phone,
                            'website_url' => $settings->website_url,
                            'facebook_url' => $settings->facebook_url,
                            'twitter_url' => $settings->twitter_url,
                            'instagram_url' => $settings->instagram_url,
                            'linkedin_url' => $settings->linkedin_url,
                            'emergency_info' => $settings->emergency_info,

                            // Technical
                            ...$this->updateConfig($settings),
                            'maintenance_mode' => $settings->maintenance_mode,
                            'maintenance_message' => $settings->maintenance_message,
                            'timezone' => $settings->timezone,
                            'language' => $settings->language,

                            // Language Configuration
                            'languages' => $settings->getEnabledLanguages(),
                            'defaultLanguage' => $settings->default_language ?? 'en',
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ],
                ];
            });

            return response()->json($payload['body'], $payload['http_status']);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get minimal configuration for quick loading
     *
     * @return JsonResponse
     */
    public function minimal(): JsonResponse
    {
        try {
            $payload = Cache::remember('api:config:minimal', now()->addMinutes(5), function () {
                $settings = EventSetting::first();

                if (!$settings) {
                    return [
                        'http_status' => 404,
                        'body' => [
                            'status' => 'error',
                            'message' => 'Event configuration not found',
                        ],
                    ];
                }

                return [
                    'http_status' => 200,
                    'body' => [
                        'status' => 'success',
                        'data' => [
                            'event_name' => $settings->event_name,
                            'primary_color' => $settings->primary_color,
                            'secondary_color' => $settings->secondary_color,
                            'accent_color' => $settings->accent_color,
                            'maintenance_mode' => $settings->maintenance_mode,
                            ...$this->updateConfig($settings),
                        ],
                    ],
                ];
            });

            return response()->json($payload['body'], $payload['http_status']);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load configuration',
            ], 500);
        }
    }

    /**
     * Check if specific features are enabled
     *
     * @return JsonResponse
     */
    public function features(): JsonResponse
    {
        try {
            $payload = Cache::remember('api:config:features', now()->addMinutes(5), function () {
                $settings = EventSetting::first();

                if (!$settings) {
                    return [
                        'http_status' => 404,
                        'body' => [
                            'status' => 'error',
                            'message' => 'Event configuration not found',
                        ],
                    ];
                }

                return [
                    'http_status' => 200,
                    'body' => [
                        'status' => 'success',
                        'data' => [
                            'notifications' => $settings->enable_notifications,
                            'chat' => $settings->enable_chat,
                            'qr_checkin' => $settings->enable_qr_checkin,
                            'networking' => $settings->enable_networking,
                            'exhibitor_scanning' => $settings->enable_exhibitor_scanning,
                            'social_wall' => $settings->enable_social_wall,
                            'attendee_list' => $settings->show_attendee_list,
                            'offline_mode' => $settings->enable_offline_mode,
                            'meeting_requests' => $settings->enable_meeting_requests,
                        ],
                    ],
                ];
            });

            return response()->json($payload['body'], $payload['http_status']);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load features',
            ], 500);
        }
    }

    public static function clearConfigCache(): void
    {
        foreach (self::CONFIG_CACHE_KEYS as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    private function updateConfig(EventSetting $settings): array
    {
        $appVersion = $settings->app_version ?? '1.0';

        return [
            'app_version' => $appVersion,
            'api_version' => $appVersion,
            'update_url' => $settings->update_url,
            'android_update_url' => $settings->android_update_url,
            'ios_update_url' => $settings->ios_update_url,
        ];
    }
}
