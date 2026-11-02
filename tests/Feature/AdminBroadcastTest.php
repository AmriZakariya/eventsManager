<?php

namespace Tests\Feature;

use App\Models\AdminBroadcast;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'app_role'    => User::APP_ROLE_ADMIN,
            'permissions' => ['platform.index' => true, 'platform.push-monitor' => true],
        ]);
    }

    private function send(User $admin, array $broadcast, string $method = 'sendBroadcast')
    {
        return $this->actingAs($admin)->post(route('platform.push-monitor') . '/' . $method, ['broadcast' => $broadcast]);
    }

    public function test_page_renders_compose_history_and_monitor(): void
    {
        $this->actingAs($this->admin())
            ->get(route('platform.push-monitor'))
            ->assertOk()
            ->assertSee('Who should receive it?')
            ->assertSee('Sent broadcasts')
            ->assertSee('Delivery monitor');
    }

    public function test_history_shows_delivery_numbers(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $broadcast = AdminBroadcast::create([
            'sent_by' => $admin->id, 'audience' => 'all', 'recipients_count' => 2,
            'titles' => ['en' => 'Gala dinner tonight'], 'bodies' => ['en' => 'See you at 20:00'],
        ]);
        foreach (['sent', 'sent', 'failed'] as $status) {
            \App\Models\PushNotificationLog::create([
                'user_id' => $user->id, 'notification' => 'AdminBroadcastNotification',
                'status' => $status, 'broadcast_id' => $broadcast->id,
            ]);
        }

        $this->actingAs($admin)->get(route('platform.push-monitor'))
            ->assertOk()
            ->assertSee('Gala dinner tonight')
            ->assertSee('<span class="badge bg-success">2</span>', false)
            ->assertSee('<span class="badge bg-danger">1</span>', false);
    }

    public function test_broadcast_to_visitors_skips_exhibitors_and_banned_users(): void
    {
        Notification::fake();

        $visitor = User::factory()->create(['app_role' => User::APP_ROLE_VISITOR]);
        $exhibitor = User::factory()->create(['app_role' => User::APP_ROLE_EXHIBITOR]);
        $banned = User::factory()->create(['app_role' => User::APP_ROLE_VISITOR]);
        $banned->ban('Spam');

        $this->send($this->admin(), [
            'audience' => 'visitors',
            'title'    => ['en' => 'Doors open', 'fr' => 'Ouverture'],
            'body'     => ['en' => 'At 9:00', 'fr' => 'À 9h00'],
        ])->assertRedirect();

        Notification::assertSentTo($visitor, AdminBroadcastNotification::class);
        Notification::assertNotSentTo($exhibitor, AdminBroadcastNotification::class);
        Notification::assertNotSentTo($banned, AdminBroadcastNotification::class);

        $broadcast = AdminBroadcast::sole();
        $this->assertSame(1, $broadcast->recipients_count);
        $this->assertSame(['en' => 'Doors open', 'fr' => 'Ouverture'], $broadcast->titles);
    }

    public function test_half_filled_translation_is_rejected_and_nothing_is_sent(): void
    {
        Notification::fake();
        User::factory()->create();

        $this->send($this->admin(), [
            'audience' => 'all',
            'title'    => ['en' => 'Hello', 'fr' => 'Bonjour'],
            'body'     => ['en' => 'Message'],
        ])->assertSessionHasInput('broadcast.title.fr', 'Bonjour');

        Notification::assertNothingSent();
        $this->assertSame(0, AdminBroadcast::count());
    }

    public function test_specific_audience_needs_users_and_sends_only_to_them(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $picked = User::factory()->create();
        $other = User::factory()->create();

        $this->send($admin, ['audience' => 'specific', 'title' => ['en' => 'Hi'], 'body' => ['en' => 'Hi']]);
        $this->assertSame(0, AdminBroadcast::count());

        $this->send($admin, [
            'audience' => 'specific',
            'user_ids' => [$picked->id],
            'title'    => ['en' => 'Hi'],
            'body'     => ['en' => 'Hi'],
        ]);

        Notification::assertSentTo($picked, AdminBroadcastNotification::class);
        Notification::assertNotSentTo($other, AdminBroadcastNotification::class);
        $this->assertSame([$picked->id], AdminBroadcast::sole()->user_ids);
    }

    public function test_send_test_goes_only_to_the_admin_and_is_not_in_history(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $admin->forceFill(['fcm_token' => 'device-token'])->save();
        $user = User::factory()->create();

        $this->send($admin, ['audience' => 'all', 'title' => ['en' => 'Test'], 'body' => ['en' => 'Test']], 'sendTest');

        Notification::assertSentTo($admin, AdminBroadcastNotification::class);
        Notification::assertNotSentTo($user, AdminBroadcastNotification::class);
        $this->assertSame(0, AdminBroadcast::count());
    }
}
