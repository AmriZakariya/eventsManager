<?php

namespace Tests\Feature;

use App\Models\EventSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppConfigUpdateMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_init_includes_mobile_update_metadata(): void
    {
        Cache::flush();

        EventSetting::create([
            'event_name' => 'Sahara Summit',
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'app_version' => '2.4.1',
            'update_url' => 'https://example.com/app',
            'android_update_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
            'ios_update_url' => 'https://apps.apple.com/app/example/id123456789',
        ]);

        $this->getJson('/api/config/init')
            ->assertOk()
            ->assertJsonPath('data.app_version', '2.4.1')
            ->assertJsonPath('data.api_version', '2.4.1')
            ->assertJsonPath('data.update_url', 'https://example.com/app')
            ->assertJsonPath('data.android_update_url', 'https://play.google.com/store/apps/details?id=com.example.app')
            ->assertJsonPath('data.ios_update_url', 'https://apps.apple.com/app/example/id123456789');
    }

    public function test_maintenance_response_keeps_mobile_update_metadata(): void
    {
        Cache::flush();

        EventSetting::create([
            'event_name' => 'Sahara Summit',
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'app_version' => '2.4.1',
            'update_url' => 'https://example.com/app',
            'maintenance_mode' => true,
            'maintenance_message' => 'Back soon.',
        ]);

        $this->getJson('/api/config/init')
            ->assertStatus(503)
            ->assertJsonPath('status', 'maintenance')
            ->assertJsonPath('data.app_version', '2.4.1')
            ->assertJsonPath('data.api_version', '2.4.1')
            ->assertJsonPath('data.update_url', 'https://example.com/app')
            ->assertJsonPath('data.maintenance_mode', true);
    }
}
