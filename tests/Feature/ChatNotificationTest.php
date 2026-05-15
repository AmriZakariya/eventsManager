<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_chat_message_notifies_receiver_with_their_locale(): void
    {
        Notification::fake();

        $sender = User::factory()->create([
            'name' => 'Sarah',
            'last_name' => 'Visitor',
            'locale' => 'en',
        ]);
        $receiver = User::factory()->create([
            'locale' => 'fr',
        ]);

        Sanctum::actingAs($sender);

        $this->postJson('/api/chat/send', [
            'receiver_id' => $receiver->id,
            'content' => 'Hello',
        ])->assertOk();

        Notification::assertSentTo($receiver, NewMessageReceived::class, function (NewMessageReceived $notification) use ($receiver) {
            $payload = $notification->toApp($receiver);

            return $payload['title'] === 'Nouveau message'
                && $payload['body'] === 'Sarah Visitor vous a envoyé un message.'
                && $payload['data']['screen'] === '/chat';
        });
    }

    public function test_unread_counts_returns_total_messages_and_distinct_conversations(): void
    {
        $user = User::factory()->create();
        $senderOne = User::factory()->create();
        $senderTwo = User::factory()->create();

        Message::create([
            'sender_id' => $senderOne->id,
            'receiver_id' => $user->id,
            'content' => 'First unread',
        ]);
        Message::create([
            'sender_id' => $senderOne->id,
            'receiver_id' => $user->id,
            'content' => 'Second unread',
        ]);
        Message::create([
            'sender_id' => $senderTwo->id,
            'receiver_id' => $user->id,
            'content' => 'Third unread',
        ]);
        Message::create([
            'sender_id' => $senderTwo->id,
            'receiver_id' => $user->id,
            'content' => 'Already read',
            'read_at' => now(),
        ]);
        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $senderTwo->id,
            'content' => 'Outgoing unread for someone else',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/chat/unread-counts')
            ->assertOk()
            ->assertJson([
                'unread_messages_count' => 3,
                'unread_conversations_count' => 2,
            ]);
    }
}
