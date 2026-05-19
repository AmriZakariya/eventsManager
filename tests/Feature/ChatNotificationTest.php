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

    public function test_messages_support_cursor_fetching_without_marking_messages_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $first = Message::create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $user->id,
            'content' => 'First',
        ]);
        $second = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'content' => 'Second',
        ]);
        $third = Message::create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $user->id,
            'content' => 'Third',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/chat/messages/{$otherUser->id}?after_id={$first->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $third->id);

        $this->assertDatabaseHas('messages', [
            'id' => $first->id,
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $third->id,
            'read_at' => null,
        ]);
    }

    public function test_mark_read_is_explicit(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Message::create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $user->id,
            'content' => 'Unread one',
        ]);
        Message::create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $user->id,
            'content' => 'Unread two',
        ]);
        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'content' => 'Outgoing',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/chat/messages/{$otherUser->id}/read")
            ->assertOk()
            ->assertJson([
                'marked_read_count' => 2,
            ]);

        $this->assertSame(0, Message::where('receiver_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_conversations_can_return_only_updated_conversations(): void
    {
        $user = User::factory()->create();
        $olderUser = User::factory()->create();
        $newerUser = User::factory()->create();

        $oldMessage = Message::create([
            'sender_id' => $olderUser->id,
            'receiver_id' => $user->id,
            'content' => 'Old conversation',
        ]);
        $newMessage = Message::create([
            'sender_id' => $newerUser->id,
            'receiver_id' => $user->id,
            'content' => 'New conversation',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/chat/conversations?after_message_id={$oldMessage->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.user.id', $newerUser->id)
            ->assertJsonPath('0.latest_message_id', $newMessage->id);
    }
}
