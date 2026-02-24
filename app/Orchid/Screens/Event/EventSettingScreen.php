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
use Orchid\Screen\Fields\Select;
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
        return 'Configure your event settings, branding, and mobile app features';
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
                ->icon('bs.check-circle-fill')
                ->method('save')
                ->type(Color::SUCCESS),
        ];
    }

    public function layout(): array
    {
        return [
            // Info Banner
            Layout::view('orchid.settings.banner'),

            Layout::tabs([
                // TAB 1: BASIC INFO
                'Basic Information' => [
                    Layout::columns([
                        Layout::rows([
                            Input::make('settings.event_name')
                                ->title('Event Name')
                                ->placeholder('e.g., Tech Summit 2026')
                                ->required()
                                ->help('Primary event name displayed in the app'),

                            Input::make('settings.tagline')
                                ->title('Tagline')
                                ->placeholder('e.g., "Innovation Meets Opportunity"')
                                ->maxlength(100)
                                ->help('Short, memorable phrase'),

                            TextArea::make('settings.description')
                                ->title('Event Description')
                                ->rows(4)
                                ->maxlength(500)
                                ->placeholder('Welcome to our amazing event...')
                                ->help('Featured on the app home screen (max 500 chars)'),
                        ]),

                        Layout::rows([
                            Cropper::make('settings.app_logo')
                                ->title('App Logo')
                                ->targetRelativeUrl()
                                ->help('Recommended: 512×512px PNG with transparent background'),

                            Cropper::make('settings.venue_image')
                                ->title('Venue Hero Image')
                                ->targetRelativeUrl()
                                ->help('Main banner image (1200×600px)'),
                        ]),
                    ]),
                ],

                // TAB 2: BRANDING
                'Branding & Colors' => [
                    Layout::columns([
                        Layout::rows([
                            Group::make([
                                Input::make('settings.primary_color')
                                    ->title('Primary Color')
                                    ->type('color')
                                    ->value('#D4AF37')
                                    ->help('Main brand color'),

                                Input::make('settings.secondary_color')
                                    ->title('Secondary Color')
                                    ->type('color')
                                    ->value('#0F172A')
                                    ->help('Background color'),

                                Input::make('settings.accent_color')
                                    ->title('Accent Color')
                                    ->type('color')
                                    ->value('#F59E0B')
                                    ->help('Highlights & badges'),
                            ]),
                        ])->title('Color Scheme'),

                        Layout::rows([
                            Cropper::make('settings.floor_plan_image')
                                ->title('Floor Plan / Venue Map')
                                ->targetRelativeUrl()
                                ->help('Interactive navigation map'),
                        ])->title('Additional Assets'),
                    ]),
                ],

                // TAB 3: DATE & LOCATION
                'Date & Location' => [
                    Layout::columns([
                        Layout::rows([
                            Group::make([
                                DateTimer::make('settings.start_date')
                                    ->title('Event Start')
                                    ->enableTime()
                                    ->format('Y-m-d H:i:s')
                                    ->required()
                                    ->help('Opening day & time'),

                                DateTimer::make('settings.end_date')
                                    ->title('Event End')
                                    ->enableTime()
                                    ->format('Y-m-d H:i:s')
                                    ->required()
                                    ->help('Closing day & time'),
                            ]),

                            Group::make([
                                Input::make('settings.opening_hour')
                                    ->title('Daily Opening')
                                    ->type('time')
                                    ->required()
                                    ->help('Daily start time'),

                                Input::make('settings.closing_hour')
                                    ->title('Daily Closing')
                                    ->type('time')
                                    ->required()
                                    ->help('Daily end time'),
                            ]),

                            Select::make('settings.timezone')
                                ->title('Time Zone')
                                ->options([
                                    'Africa/Casablanca' => 'Casablanca (UTC+1)',
                                    'Africa/Cairo' => 'Cairo (UTC+2)',
                                    'Africa/Johannesburg' => 'Johannesburg (UTC+2)',
                                    'Europe/London' => 'London (UTC+1)',
                                    'Europe/Paris' => 'Paris (UTC+2)',
                                    'America/New_York' => 'New York (UTC-4)',
                                    'America/Chicago' => 'Chicago (UTC-5)',
                                    'America/Los_Angeles' => 'Los Angeles (UTC-7)',
                                    'Asia/Dubai' => 'Dubai (UTC+4)',
                                    'Asia/Singapore' => 'Singapore (UTC+8)',
                                ])
                                ->required()
                                ->help('All times displayed in this zone'),
                        ])->title('Schedule'),

                        Layout::rows([
                            Input::make('settings.location_name')
                                ->title('Venue Name')
                                ->placeholder('e.g., Grand Convention Center')
                                ->help('Specific venue or hall name'),

                            TextArea::make('settings.location_address')
                                ->title('Full Address')
                                ->rows(3)
                                ->placeholder('123 Main Street, City, Country')
                                ->help('Complete address for maps'),

                            Group::make([
                                Input::make('settings.latitude')
                                    ->title('Latitude')
                                    ->type('number')
                                    ->step('0.0000001')
                                    ->placeholder('33.5731104'),

                                Input::make('settings.longitude')
                                    ->title('Longitude')
                                    ->type('number')
                                    ->step('0.0000001')
                                    ->placeholder('-7.5898434'),
                            ])->fullwidth(),
                        ])->title('Venue Location'),
                    ]),
                ],

                // TAB 4: FEATURES
                'App Features' => [
                    Layout::columns([
                        Layout::rows([
                            CheckBox::make('settings.enable_meeting_requests')
                                ->placeholder('Enable Meeting Scheduling')
                                ->sendTrueOrFalse()
                                ->help('Allow attendees to book meetings'),

                            CheckBox::make('settings.auto_confirm_meetings')
                                ->placeholder('Auto-approve Meetings')
                                ->sendTrueOrFalse()
                                ->help('Skip manual approval step'),

                            CheckBox::make('settings.enable_exhibitor_scanning')
                                ->placeholder('Lead Retrieval (QR Scanning)')
                                ->sendTrueOrFalse()
                                ->help('Exhibitors can scan attendee badges'),

                            Group::make([
                                Input::make('settings.meeting_duration_minutes')
                                    ->title('Meeting Duration')
                                    ->type('number')
                                    ->min(15)
                                    ->max(120)
                                    ->step(5)
                                    ->value(30)
                                    ->help('Minutes'),

                                Input::make('settings.meeting_buffer_minutes')
                                    ->title('Buffer Time')
                                    ->type('number')
                                    ->min(0)
                                    ->max(30)
                                    ->step(5)
                                    ->value(5)
                                    ->help('Gap between'),

                                Input::make('settings.max_meetings_per_day')
                                    ->title('Daily Limit')
                                    ->type('number')
                                    ->min(1)
                                    ->max(50)
                                    ->value(10)
                                    ->help('Max per day'),
                            ]),
                        ])->title('Meeting Management'),

                        Layout::rows([
                            CheckBox::make('settings.enable_networking')
                                ->placeholder('Attendee Networking')
                                ->sendTrueOrFalse()
                                ->help('Allow connections between attendees'),

                            CheckBox::make('settings.enable_chat')
                                ->placeholder('In-App Messaging')
                                ->sendTrueOrFalse()
                                ->help('Enable direct messaging'),

                            CheckBox::make('settings.enable_notifications')
                                ->placeholder('Push Notifications')
                                ->sendTrueOrFalse()
                                ->help('Send alerts & reminders'),

                            CheckBox::make('settings.enable_qr_checkin')
                                ->placeholder('QR Check-in')
                                ->sendTrueOrFalse()
                                ->help('Track session attendance'),

                            CheckBox::make('settings.show_attendee_list')
                                ->placeholder('Attendee Directory')
                                ->sendTrueOrFalse()
                                ->help('Public attendee list'),

                            CheckBox::make('settings.enable_social_wall')
                                ->placeholder('Social Media Wall')
                                ->sendTrueOrFalse()
                                ->help('Display social posts'),

                            CheckBox::make('settings.enable_offline_mode')
                                ->placeholder('Offline Access')
                                ->sendTrueOrFalse()
                                ->help('Cache key information'),
                        ])->title('Communication & Social'),
                    ]),
                ],

                // TAB 5: CONTACT & SUPPORT
                'Contact & Support' => [
                    Layout::columns([
                        Layout::rows([
                            Input::make('settings.support_email')
                                ->title('Support Email')
                                ->type('email')
                                ->placeholder('support@event.com')
                                ->help('For attendee inquiries'),

                            Input::make('settings.support_phone')
                                ->title('Support Phone')
                                ->type('tel')
                                ->placeholder('+1 234 567 8900')
                                ->help('On-site contact'),

                            Input::make('settings.website_url')
                                ->title('Event Website')
                                ->type('url')
                                ->placeholder('https://event.com')
                                ->help('Official homepage'),

                            TextArea::make('settings.emergency_info')
                                ->title('Emergency Information')
                                ->rows(4)
                                ->placeholder('Emergency exits, first aid locations, security contacts...')
                                ->help('Critical safety information'),
                        ])->title('Support Information'),

                        Layout::rows([
                            Input::make('settings.facebook_url')
                                ->title('Facebook')
                                ->type('url')
                                ->placeholder('https://facebook.com/event'),

                            Input::make('settings.twitter_url')
                                ->title('X (Twitter)')
                                ->type('url')
                                ->placeholder('https://twitter.com/event'),

                            Input::make('settings.instagram_url')
                                ->title('Instagram')
                                ->type('url')
                                ->placeholder('https://instagram.com/event'),

                            Input::make('settings.linkedin_url')
                                ->title('LinkedIn')
                                ->type('url')
                                ->placeholder('https://linkedin.com/company/event'),
                        ])->title('Social Media'),
                    ]),
                ],

                // TAB 6: ADVANCED
                'Advanced Settings' => [
                    Layout::columns([
                        Layout::rows([
                            Select::make('settings.language')
                                ->title('Default Language')
                                ->options([
                                    'en' => '🇬🇧 English',
                                    'fr' => '🇫🇷 Français',
                                    'ar' => '🇸🇦 العربية',
                                    'es' => '🇪🇸 Español',
                                    'de' => '🇩🇪 Deutsch',
                                    'it' => '🇮🇹 Italiano',
                                    'pt' => '🇵🇹 Português',
                                    'zh' => '🇨🇳 中文',
                                ])
                                ->required()
                                ->help('Primary app language'),

                            Input::make('settings.app_version')
                                ->title('Minimum App Version')
                                ->placeholder('1.0.0')
                                ->help('Force update for older versions'),

                            Input::make('settings.api_key')
                                ->title('API Key')
                                ->placeholder('Auto-generated on first save')
                                ->readonly()
                                ->help('Keep this secret!'),

                            Button::make('Manage Languages & Translations')
                                ->icon('bs.translate')
                                ->link(route('platform.language.management'))
                                ->type(Color::INFO)
                                ->class('w-100 mb-3'),
                        ])->title('System Configuration'),

                        Layout::rows([
                            CheckBox::make('settings.maintenance_mode')
                                ->placeholder('⚠️ Enable Maintenance Mode')
                                ->sendTrueOrFalse()
                                ->help('Blocks all user access'),

                            TextArea::make('settings.maintenance_message')
                                ->title('Maintenance Message')
                                ->rows(3)
                                ->placeholder('We are currently updating. Please check back soon.')
                                ->help('Shown when maintenance mode is active'),

                            Input::make('last_updated')
                                ->title('Last Updated')
                                ->value(optional($this->query()['settings']->updated_at)->format('M j, Y \a\t g:i A') ?? 'Never')
                                ->readonly()
                                ->help('Configuration last saved'),
                        ])->title('Maintenance & Status'),
                    ]),
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
            'settings.timezone' => 'required|string|max:50',
            'settings.meeting_duration_minutes' => 'required|integer|min:15|max:120',
            'settings.meeting_buffer_minutes' => 'nullable|integer|min:0|max:30',
            'settings.max_meetings_per_day' => 'nullable|integer|min:1|max:50',
            'settings.enable_meeting_requests' => 'nullable|boolean',
            'settings.auto_confirm_meetings' => 'nullable|boolean',
            'settings.enable_exhibitor_scanning' => 'nullable|boolean',
            'settings.enable_notifications' => 'nullable|boolean',
            'settings.enable_chat' => 'nullable|boolean',
            'settings.enable_qr_checkin' => 'nullable|boolean',
            'settings.enable_networking' => 'nullable|boolean',
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
            'settings.language' => 'required|string|max:5',
            'settings.app_version' => 'nullable|string|max:20',
            'settings.maintenance_mode' => 'nullable|boolean',
            'settings.maintenance_message' => 'nullable|string',
        ]);

        $settings = EventSetting::firstOrNew();
        $settings->fill($request->get('settings'));

        // Generate API key if not exists
        if (empty($settings->api_key)) {
            $settings->api_key = 'evt_' . bin2hex(random_bytes(16));
        }

        $settings->save();

        Toast::success('Configuration saved successfully! Changes are now live in the mobile app.');

        return redirect()->route('platform.event.settings');
    }
}
