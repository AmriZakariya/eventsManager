<?php

namespace App\Orchid\Screens\Event;

use App\Models\EventSetting;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class EventSettingScreen extends Screen
{
    public function name(): ?string
    {
        return 'Event Configuration';
    }

    public function description(): ?string
    {
        return 'Customize your mobile app branding, event details, and operational settings.';
    }

    public function query(): array
    {
        $settings = EventSetting::firstOrNew();

        return [
            'settings' => $settings,
        ];
    }

    public function commandBar(): array
    {
        return [
            Button::make('Save Configuration')
                ->icon('bs.check-circle')
                ->method('save')
                ->type(Color::SUCCESS)
                ->class('btn-lg'),

            Button::make('Reset to Defaults')
                ->icon('bs.arrow-counterclockwise')
                ->method('resetDefaults')
                ->confirm('Are you sure you want to reset all settings to default values? This cannot be undone.')
                ->type(Color::WARNING)
                ->novalidate(),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::tabs([
                'Branding & Identity' => [
                    Layout::rows([
                        Input::make('settings.event_name')
                            ->title('Event Name')
                            ->placeholder('e.g., Hygie Clean Expo 2026')
                            ->required()
                            ->help('This will appear in the app header and splash screen'),

                        Cropper::make('settings.app_logo')
                            ->title('Notification Logo')
                            ->targetRelativeUrl()
                            ->width(512)
                            ->height(512)
                            ->help('Recommended: 512x512px PNG with transparent background'),

                        Group::make([
                            Input::make('settings.primary_color')
                                ->title('Primary Color')
                                ->type('color')
                                ->help('Main brand color (buttons, headers)'),

                            Input::make('settings.secondary_color')
                                ->title('Secondary Color')
                                ->type('color')
                                ->help('Accent color (backgrounds, text)'),

                            Input::make('settings.accent_color')
                                ->title('Accent Color')
                                ->type('color')
                                ->help('Highlights and call-to-action elements'),
                        ]),

                        TextArea::make('settings.description')
                            ->title('Event Description')
                            ->rows(4)
                            ->maxlength(500)
                            ->help('Brief description shown on the home screen (max 500 characters)'),

                        Input::make('settings.tagline')
                            ->title('Event Tagline')
                            ->placeholder('e.g., The Future of Clean')
                            ->maxlength(100)
                            ->help('Short catchy phrase for marketing'),

                    ])->title('Notification Appearance'),
                ],

                'Event Details' => [
                    Layout::rows([
                        Group::make([
                            DateTimer::make('settings.start_date')
                                ->title('Start Date & Time')
                                ->enableTime()
                                ->format24hr()
                                ->required()
                                ->help('When does the event begin?'),

                            DateTimer::make('settings.end_date')
                                ->title('End Date & Time')
                                ->enableTime()
                                ->format24hr()
                                ->required()
                                ->help('When does the event end?'),
                        ]),

                        Input::make('settings.location_name')
                            ->title('Venue Name')
                            ->placeholder('e.g., Parc des Expositions')
                            ->help('Name of the venue or conference center'),

                        TextArea::make('settings.location_address')
                            ->title('Full Address')
                            ->rows(2)
                            ->placeholder('123 Main St, Casablanca, Morocco')
                            ->help('Complete address for map integration'),

                        Group::make([
                            Input::make('settings.latitude')
                                ->title('Latitude')
                                ->type('number')
                                ->step('0.0000001')
                                ->placeholder('33.5731104')
                                ->help('For accurate map positioning'),

                            Input::make('settings.longitude')
                                ->title('Longitude')
                                ->type('number')
                                ->step('0.0000001')
                                ->placeholder('-7.5898434')
                                ->help('For accurate map positioning'),
                        ])->fullwidth(),

                        Cropper::make('settings.venue_image')
                            ->title('Venue Photo')
                            ->targetRelativeUrl()
                            ->help('Main photo of the venue for the app'),

                        Cropper::make('settings.floor_plan_image')
                            ->title('Floor Plan / Venue Map')
                            ->targetRelativeUrl()
                            ->help('Interactive floor plan for navigation'),

                    ])->title('Location & Venue'),
                ],

                'Operational Settings' => [
                    Layout::rows([
                        Group::make([
                            Input::make('settings.opening_hour')
                                ->title('Daily Opening Time')
                                ->type('time')
                                ->required()
                                ->help('When does the event open each day?'),

                            Input::make('settings.closing_hour')
                                ->title('Daily Closing Time')
                                ->type('time')
                                ->required()
                                ->help('When does the event close each day?'),
                        ]),

                        Group::make([
                            Input::make('settings.meeting_duration_minutes')
                                ->title('B2B Meeting Duration (Minutes)')
                                ->type('number')
                                ->min(15)
                                ->max(120)
                                ->required()
                                ->help('Default duration for scheduled meetings'),

                            Input::make('settings.meeting_buffer_minutes')
                                ->title('Meeting Buffer Time (Minutes)')
                                ->type('number')
                                ->min(0)
                                ->max(30)
                                ->help('Gap between consecutive meetings'),

                            Input::make('settings.max_meetings_per_day')
                                ->title('Max Meetings Per Person/Day')
                                ->type('number')
                                ->min(1)
                                ->max(50)
                                ->help('Limit on daily meeting bookings'),
                        ]),

                        CheckBox::make('settings.enable_meeting_requests')
                            ->title('Enable Meeting Requests')
                            ->placeholder('Allow attendees to request meetings')
                            ->sendTrueOrFalse()
                            ->help('When enabled, attendees can send meeting requests to each other'),

                        CheckBox::make('settings.auto_confirm_meetings')
                            ->title('Auto-Confirm Meetings')
                            ->placeholder('Automatically confirm meeting requests without approval')
                            ->sendTrueOrFalse()
                            ->help('If disabled, exhibitors must manually approve each request'),

                    ])->title('Meeting & Scheduling'),
                ],

                'Notification Features' => [
                    Layout::rows([
                        CheckBox::make('settings.enable_notifications')
                            ->title('Push Notifications')
                            ->placeholder('Enable push notifications for important updates')
                            ->sendTrueOrFalse()
                            ->help('Allows sending alerts for sessions, meetings, and announcements'),

                        CheckBox::make('settings.enable_chat')
                            ->title('In-Notification Chat')
                            ->placeholder('Enable real-time messaging between users')
                            ->sendTrueOrFalse()
                            ->help('Users can message each other directly in the app'),

                        CheckBox::make('settings.enable_qr_checkin')
                            ->title('QR Code Check-in')
                            ->placeholder('Enable QR code scanning for attendance tracking')
                            ->sendTrueOrFalse()
                            ->help('Staff can scan attendee badges to track session attendance'),

                        CheckBox::make('settings.enable_networking')
                            ->title('Networking Features')
                            ->placeholder('Enable attendee networking and connections')
                            ->sendTrueOrFalse()
                            ->help('Allows attendees to connect and share contact information'),

                        CheckBox::make('settings.enable_exhibitor_scanning')
                            ->title('Exhibitor Lead Scanning')
                            ->placeholder('Allow exhibitors to scan attendee badges for leads')
                            ->sendTrueOrFalse()
                            ->help('Exhibitors can collect leads by scanning QR codes'),

                        CheckBox::make('settings.enable_social_wall')
                            ->title('Social Media Wall')
                            ->placeholder('Display social media posts in the app')
                            ->sendTrueOrFalse()
                            ->help('Aggregates and displays social posts with event hashtag'),

                        CheckBox::make('settings.show_attendee_list')
                            ->title('Show Attendee Directory')
                            ->placeholder('Display searchable list of all attendees')
                            ->sendTrueOrFalse()
                            ->help('Privacy consideration: attendees can see who else is attending'),

                        CheckBox::make('settings.enable_offline_mode')
                            ->title('Offline Mode')
                            ->placeholder('Allow app to work without internet connection')
                            ->sendTrueOrFalse()
                            ->help('Caches data locally for offline access'),

                    ])->title('Feature Toggles'),
                ],

                'Contact & Support' => [
                    Layout::rows([
                        Group::make([
                            Input::make('settings.support_email')
                                ->title('Support Email')
                                ->type('email')
                                ->placeholder('support@yourevent.com')
                                ->help('Contact email shown in the app'),

                            Input::make('settings.support_phone')
                                ->title('Support Phone')
                                ->type('tel')
                                ->placeholder('+212 5XX-XXXXXX')
                                ->help('Contact phone shown in the app'),
                        ]),

                        Input::make('settings.website_url')
                            ->title('Event Website')
                            ->type('url')
                            ->placeholder('https://yourevent.com')
                            ->help('Official event website URL'),

                        Group::make([
                            Input::make('settings.facebook_url')
                                ->title('Facebook Page')
                                ->type('url')
                                ->placeholder('https://facebook.com/yourevent'),

                            Input::make('settings.twitter_url')
                                ->title('Twitter/X Handle')
                                ->type('url')
                                ->placeholder('https://twitter.com/yourevent'),
                        ]),

                        Group::make([
                            Input::make('settings.instagram_url')
                                ->title('Instagram Profile')
                                ->type('url')
                                ->placeholder('https://instagram.com/yourevent'),

                            Input::make('settings.linkedin_url')
                                ->title('LinkedIn Page')
                                ->type('url')
                                ->placeholder('https://linkedin.com/company/yourevent'),
                        ]),

                        TextArea::make('settings.emergency_info')
                            ->title('Emergency Information')
                            ->rows(4)
                            ->placeholder('Emergency exits, first aid locations, emergency contact numbers, etc.')
                            ->help('Important safety information displayed in the app'),

                    ])->title('Contact Information'),
                ],

                'Advanced' => [
                    Layout::rows([
                        Input::make('settings.api_key')
                            ->title('API Key')
                            ->placeholder('Generated automatically on first save')
                            ->readonly()
                            ->help('Use this key for mobile app API authentication'),

                        Group::make([
                            Input::make('settings.timezone')
                                ->title('Event Timezone')
                                ->placeholder('Africa/Casablanca')
                                ->help('IANA timezone identifier (e.g., Africa/Casablanca, Europe/Paris)'),

                            Input::make('settings.language')
                                ->title('Default Language')
                                ->placeholder('en')
                                ->maxlength(5)
                                ->help('ISO language code: en, fr, ar, etc.'),
                        ]),

                        Input::make('settings.app_version')
                            ->title('Minimum Required Notification Version')
                            ->placeholder('1.0.0')
                            ->help('Force users to update if they have an older version'),

                        CheckBox::make('settings.maintenance_mode')
                            ->title('Maintenance Mode')
                            ->placeholder('Put app in maintenance mode (blocks all access)')
                            ->sendTrueOrFalse()
                            ->help('⚠️ Warning: Enabling this will prevent users from accessing the app'),

                        TextArea::make('settings.maintenance_message')
                            ->title('Maintenance Message')
                            ->rows(3)
                            ->placeholder('We are currently updating the app. Please check back soon.')
                            ->help('Message shown to users when maintenance mode is enabled'),

                    ])->title('Technical Settings'),
                ],
            ]),
        ];
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'settings.event_name' => 'required|string|max:255',
            'settings.app_logo' => 'nullable|string',
            'settings.primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/i',
            'settings.secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/i',
            'settings.accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/i',
            'settings.tagline' => 'nullable|string|max:100',
            'settings.description' => 'nullable|string|max:500',
            'settings.start_date' => 'required|date',
            'settings.end_date' => 'required|date|after:settings.start_date',
            'settings.location_name' => 'nullable|string|max:255',
            'settings.location_address' => 'nullable|string',
            'settings.latitude' => 'nullable|numeric|between:-90,90',
            'settings.longitude' => 'nullable|numeric|between:-180,180',
            'settings.venue_image' => 'nullable|string',
            'settings.floor_plan_image' => 'nullable|string',
            'settings.opening_hour' => 'required',
            'settings.closing_hour' => 'required',
            'settings.meeting_duration_minutes' => 'required|integer|min:15|max:120',
            'settings.meeting_buffer_minutes' => 'nullable|integer|min:0|max:30',
            'settings.max_meetings_per_day' => 'nullable|integer|min:1|max:50',
            'settings.enable_meeting_requests' => 'nullable|boolean',
            'settings.auto_confirm_meetings' => 'nullable|boolean',
            'settings.enable_notifications' => 'nullable|boolean',
            'settings.enable_chat' => 'nullable|boolean',
            'settings.enable_qr_checkin' => 'nullable|boolean',
            'settings.enable_networking' => 'nullable|boolean',
            'settings.enable_exhibitor_scanning' => 'nullable|boolean',
            'settings.enable_social_wall' => 'nullable|boolean',
            'settings.show_attendee_list' => 'nullable|boolean',
            'settings.enable_offline_mode' => 'nullable|boolean',
            'settings.support_email' => 'nullable|email|max:255',
            'settings.support_phone' => 'nullable|string|max:50',
            'settings.website_url' => 'nullable|url|max:255',
            'settings.facebook_url' => 'nullable|url|max:255',
            'settings.twitter_url' => 'nullable|url|max:255',
            'settings.instagram_url' => 'nullable|url|max:255',
            'settings.linkedin_url' => 'nullable|url|max:255',
            'settings.emergency_info' => 'nullable|string',
            'settings.app_version' => 'nullable|string|max:20',
            'settings.maintenance_mode' => 'nullable|boolean',
            'settings.maintenance_message' => 'nullable|string',
            'settings.timezone' => 'nullable|string|max:50',
            'settings.language' => 'nullable|string|max:5',
        ]);

        $settings = EventSetting::firstOrNew();

        // Fill all settings from request
        $settings->fill($request->get('settings'));

        // Generate API key if not exists
        if (empty($settings->api_key)) {
            $settings->api_key = bin2hex(random_bytes(32));
        }

        $settings->save();

        Toast::success('Event settings saved successfully!');

        return redirect()->route('platform.event.settings');
    }

    public function resetDefaults()
    {
        $settings = EventSetting::firstOrNew();

        // Keep the API key and event name
        $apiKey = $settings->api_key;
        $eventName = $settings->event_name ?? 'My Event';

        $settings->fill([
            'event_name' => $eventName,
            'api_key' => $apiKey,
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
        ]);

        $settings->save();

        Toast::info('Settings reset to default values. Event name and API key were preserved.');

        return redirect()->route('platform.event.settings');
    }
}
