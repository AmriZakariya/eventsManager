<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventSetting;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    /**
     * Get initial app configuration for mobile app
     *
     * @return JsonResponse
     */
    public function init(): JsonResponse
    {
        try {
            $settings = EventSetting::first();

            if (!$settings) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event configuration not found. Please contact administrator.',
                ], 404);
            }

            // Check maintenance mode
            if ($settings->maintenance_mode) {
                return response()->json([
                    'status' => 'maintenance',
                    'message' => $settings->maintenance_message ?? 'App is under maintenance. Please try again later.',
                    'data' => null,
                ], 503);
            }

            // Return all configuration data
            return response()->json([
                'status' => 'success',
                'message' => 'Configuration loaded successfully',
                'data' => [
                    // Branding
                    'event_name' => $settings->event_name,
                    'app_logo' => $settings->app_logo ? asset( $settings->app_logo) : null,
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
                    'floor_plan_image' => $settings->floor_plan_image ? asset( $settings->floor_plan_image) : null,
                    'venue_image' => $settings->venue_image ? asset( $settings->venue_image) : null,

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
                    'api_version' => $settings->app_version ?? '1.0',
                    'maintenance_mode' => $settings->maintenance_mode,
                    'maintenance_message' => $settings->maintenance_message,
                    'timezone' => $settings->timezone,
                    'language' => $settings->language,

                    // Language Configuration
                    'languages' => $settings->getEnabledLanguages(),
                    'defaultLanguage' => $settings->default_language ?? 'en',
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

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
            $settings = EventSetting::first();

            if (!$settings) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event configuration not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'event_name' => $settings->event_name,
                    'primary_color' => $settings->primary_color,
                    'secondary_color' => $settings->secondary_color,
                    'accent_color' => $settings->accent_color,
                    'maintenance_mode' => $settings->maintenance_mode,
                ],
            ]);

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
            $settings = EventSetting::first();

            if (!$settings) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event configuration not found',
                ], 404);
            }

            return response()->json([
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
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load features',
            ], 500);
        }
    }
}
