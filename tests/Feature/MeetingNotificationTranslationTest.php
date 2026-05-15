<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\MeetingStatusUpdated;
use App\Notifications\NewMeetingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingNotificationTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_meeting_request_uses_recipient_locale(): void
    {
        $booker = User::factory()->create([
            'name' => 'Sarah',
            'last_name' => 'Visitor',
            'company_name' => 'ACME',
            'locale' => 'en',
        ]);
        $recipient = User::factory()->create([
            'locale' => 'fr',
        ]);
        $appointment = Appointment::create([
            'booker_id' => $booker->id,
            'target_user_id' => $recipient->id,
            'scheduled_at' => '2026-05-20 14:30:00',
            'duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $payload = (new NewMeetingRequest($appointment, $booker))->toApp($recipient);

        $this->assertSame('Nouvelle demande de réunion 🤝', $payload['title']);
        $this->assertStringContainsString('Sarah Visitor de ACME', $payload['body']);
        $this->assertStringContainsString('20 mai à 14:30', $payload['body']);
    }

    public function test_meeting_status_update_uses_recipient_locale(): void
    {
        $booker = User::factory()->create([
            'locale' => 'ar',
        ]);
        $target = User::factory()->create();
        $appointment = Appointment::create([
            'booker_id' => $booker->id,
            'target_user_id' => $target->id,
            'scheduled_at' => '2026-05-20 14:30:00',
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        $payload = (new MeetingStatusUpdated($appointment, 'confirmed'))->toFcm($booker);

        $this->assertSame('تم تأكيد الاجتماع! ✅', $payload['title']);
        $this->assertStringContainsString('تم تأكيد اجتماعك', $payload['body']);
        $this->assertStringNotContainsString(' at ', $payload['body']);
        $this->assertSame('FLUTTER_NOTIFICATION_CLICK', $payload['data']['click_action']);
    }
}
