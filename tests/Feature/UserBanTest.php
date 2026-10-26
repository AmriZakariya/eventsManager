<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserBanTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);
        $user->ban('Spam');

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_banned');
    }

    public function test_ban_revokes_tokens_and_blocks_api_requests(): void
    {
        $user = User::factory()->create();
        $user->createToken('mobile_app');

        $user->ban('Spam');

        $this->assertSame(0, $user->tokens()->count());

        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'account_banned');
    }

    public function test_unbanned_user_can_use_the_api_again(): void
    {
        $user = User::factory()->create();
        $user->ban('Spam');
        $user->unban();

        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/auth/me')->assertOk();
    }

    public function test_conversations_and_messages_with_banned_user_are_hidden(): void
    {
        $client = User::factory()->create();
        $spammer = User::factory()->create();
        $friend = User::factory()->create();

        Message::create(['sender_id' => $spammer->id, 'receiver_id' => $client->id, 'content' => 'Buy from us']);
        Message::create(['sender_id' => $friend->id, 'receiver_id' => $client->id, 'content' => 'Hello']);

        $spammer->ban('Spam');
        Sanctum::actingAs($client);

        $conversations = $this->getJson('/api/chat/conversations')->assertOk()->json();
        $this->assertSame([$friend->id], array_column(array_column($conversations, 'user'), 'id'));

        $this->getJson('/api/chat/messages/' . $spammer->id)->assertOk()->assertJsonCount(0, 'data');

        $this->getJson('/api/chat/unread-counts')
            ->assertOk()
            ->assertJsonPath('unread_messages_count', 1)
            ->assertJsonPath('unread_conversations_count', 1);
    }

    public function test_cannot_message_a_banned_user(): void
    {
        Notification::fake();

        $client = User::factory()->create();
        $spammer = User::factory()->create();
        $spammer->ban('Spam');

        Sanctum::actingAs($client);
        $this->postJson('/api/chat/send', ['receiver_id' => $spammer->id, 'content' => 'Hi'])
            ->assertStatus(422);

        $this->assertSame(0, Message::count());
    }

    public function test_banned_user_is_hidden_from_networking(): void
    {
        $client = User::factory()->create();
        $spammer = User::factory()->create(['is_visible' => true]);
        $spammer->ban('Spam');

        Sanctum::actingAs($client);
        $ids = collect($this->getJson('/api/networking/discover')->assertOk()->json('data'))->pluck('id');

        $this->assertNotContains($spammer->id, $ids);
    }

    public function test_chat_notifications_from_banned_sender_are_hidden(): void
    {
        $client = User::factory()->create();
        $spammer = User::factory()->create();

        AppNotification::create([
            'user_id' => $client->id, 'title' => 'New message', 'body' => 'Spam',
            'data' => ['screen' => '/chat', 'arg' => (string) $spammer->id],
        ]);
        AppNotification::create([
            'user_id' => $client->id, 'title' => 'Event news', 'body' => 'Doors open at 9',
            'data' => [],
        ]);

        $spammer->ban('Spam');
        Sanctum::actingAs($client);

        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Event news');
        $this->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('count', 1);
    }
}
