<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RemoveConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function remove(User $actor, User $other)
    {
        Sanctum::actingAs($actor);

        return $this->postJson('/api/networking/toggle-connection', [
            'target_id' => $other->id,
            'action'    => 'remove',
        ]);
    }

    public function test_either_side_can_remove_an_accepted_connection(): void
    {
        Notification::fake();
        [$a, $b, $c] = User::factory()->count(3)->create();
        Connection::create(['requester_id' => $a->id, 'target_id' => $b->id, 'status' => 'accepted']);
        Connection::create(['requester_id' => $c->id, 'target_id' => $a->id, 'status' => 'accepted']);

        // The person who accepted removes it.
        $this->remove($b, $a)->assertOk();
        // The person who sent the request removes it.
        $this->remove($a, $c)->assertOk();

        $this->assertSame(0, Connection::count());
        Notification::assertNothingSent();
    }

    public function test_remove_does_not_touch_pending_requests_or_other_people(): void
    {
        [$a, $b, $c] = User::factory()->count(3)->create();
        Connection::create(['requester_id' => $b->id, 'target_id' => $a->id, 'status' => 'pending']);
        Connection::create(['requester_id' => $b->id, 'target_id' => $c->id, 'status' => 'accepted']);

        $this->remove($a, $b)->assertOk();

        $this->assertSame(2, Connection::count());
    }

    public function test_removed_connection_disappears_from_my_network(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        Connection::create(['requester_id' => $a->id, 'target_id' => $b->id, 'status' => 'accepted']);

        $this->remove($a, $b)->assertOk();

        Sanctum::actingAs($b);
        $this->getJson('/api/networking/my-network')
            ->assertOk()
            ->assertJsonCount(0, 'connections');
    }
}
