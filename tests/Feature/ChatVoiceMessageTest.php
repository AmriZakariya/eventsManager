<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatVoiceMessageTest extends TestCase
{
    use RefreshDatabase;

    private function voiceFile(): UploadedFile
    {
        // Minimal MPEG-4 audio header, as written by the phone recorder.
        $m4a = "\x00\x00\x00\x20ftypM4A \x00\x00\x00\x00M4A mp42isom\x00\x00\x00\x00\x00\x00\x00\x08free";

        return UploadedFile::fake()->createWithContent('voice.m4a', $m4a);
    }

    public function test_voice_message_is_stored_with_its_duration_and_announced_as_voice(): void
    {
        Storage::fake('public');
        Notification::fake();
        $sender = User::factory()->create(['name' => 'Sarah', 'last_name' => 'Visitor']);
        $receiver = User::factory()->create(['locale' => 'en']);

        Sanctum::actingAs($sender);
        $this->post('/api/chat/send', [
            'receiver_id' => $receiver->id,
            'file'        => $this->voiceFile(),
            'duration'    => 12,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('attachment_duration', 12);

        $message = Message::sole();
        $this->assertTrue($message->isVoice());
        $this->assertStringContainsString('/chat_files/audio/', $message->attachment_url);

        Notification::assertSentTo($receiver, NewMessageReceived::class, fn ($n) =>
            $n->toApp($receiver)['body'] === 'Sarah Visitor sent you a voice message.');

        Sanctum::actingAs($receiver);
        $this->getJson('/api/chat/conversations')
            ->assertOk()
            ->assertJsonPath('0.last_message_type', 'voice');
    }

    public function test_duration_is_ignored_for_non_audio_files(): void
    {
        Storage::fake('public');
        Notification::fake();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);
        $this->post('/api/chat/send', [
            'receiver_id' => $receiver->id,
            'file'        => UploadedFile::fake()->image('photo.jpg'),
            'duration'    => 5,
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNull(Message::sole()->attachment_duration);
        $this->assertFalse(Message::sole()->isVoice());
    }

    public function test_text_message_preview_is_unchanged(): void
    {
        Notification::fake();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);
        $this->postJson('/api/chat/send', ['receiver_id' => $receiver->id, 'content' => 'Hello'])->assertOk();

        Sanctum::actingAs($receiver);
        $this->getJson('/api/chat/conversations')
            ->assertJsonPath('0.last_message', 'Hello')
            ->assertJsonPath('0.last_message_type', 'text');
    }
}
