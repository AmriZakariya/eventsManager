<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NetworkingDiscoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_excludes_pending_requests_sent_to_authenticated_user(): void
    {
        $requester = User::factory()->create(['name' => 'User A']);
        $target = User::factory()->create(['name' => 'User B']);
        $available = User::factory()->create(['name' => 'User C']);

        Connection::create([
            'requester_id' => $requester->id,
            'target_id' => $target->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($target);

        $response = $this->getJson('/api/networking/discover');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $requester->id]);
        $response->assertJsonFragment(['id' => $available->id]);
    }

    public function test_discover_excludes_admin_users(): void
    {
        $authUser = User::factory()->create();
        $admin = User::factory()->create([
            'app_role' => User::APP_ROLE_ADMIN,
            'name' => 'Admin User',
        ]);
        $visitor = User::factory()->create([
            'app_role' => User::APP_ROLE_VISITOR,
            'name' => 'Visitor User',
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->getJson('/api/networking/discover');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $admin->id]);
        $response->assertJsonFragment(['id' => $visitor->id]);
    }

    public function test_discover_excludes_users_with_na_phone(): void
    {
        $authUser = User::factory()->create();
        $placeholderPhoneUser = User::factory()->create([
            'phone' => 'N/A',
            'name' => 'Placeholder Phone',
        ]);
        $available = User::factory()->create([
            'phone' => '+212600000000',
            'name' => 'Available User',
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->getJson('/api/networking/discover');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $placeholderPhoneUser->id]);
        $response->assertJsonFragment(['id' => $available->id]);
    }

    public function test_discover_excludes_users_without_completed_profile(): void
    {
        $authUser = User::factory()->create();
        $incompleteUser = User::factory()->create([
            'name' => 'Incomplete User',
            'password_is_set' => false,
        ]);
        $available = User::factory()->create([
            'name' => 'Complete User',
            'password_is_set' => true,
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->getJson('/api/networking/discover');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $incompleteUser->id]);
        $response->assertJsonFragment(['id' => $available->id]);
    }

    public function test_my_network_excludes_users_without_completed_profile(): void
    {
        $authUser = User::factory()->create();
        $incompleteIncoming = User::factory()->create(['password_is_set' => false]);
        $incompleteOutgoing = User::factory()->create(['password_is_set' => false]);
        $incompleteAccepted = User::factory()->create(['password_is_set' => false]);
        $completeAccepted = User::factory()->create(['password_is_set' => true]);

        Connection::create([
            'requester_id' => $incompleteIncoming->id,
            'target_id' => $authUser->id,
            'status' => 'pending',
        ]);
        Connection::create([
            'requester_id' => $authUser->id,
            'target_id' => $incompleteOutgoing->id,
            'status' => 'pending',
        ]);
        Connection::create([
            'requester_id' => $authUser->id,
            'target_id' => $incompleteAccepted->id,
            'status' => 'accepted',
        ]);
        Connection::create([
            'requester_id' => $authUser->id,
            'target_id' => $completeAccepted->id,
            'status' => 'accepted',
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->getJson('/api/networking/my-network');

        $response->assertOk();
        $response->assertJsonPath('incoming_requests', []);
        $response->assertJsonPath('outgoing_requests', []);
        $response->assertJsonMissing(['id' => $incompleteAccepted->id]);
        $response->assertJsonFragment(['id' => $completeAccepted->id]);
    }

    public function test_cannot_connect_to_user_without_completed_profile(): void
    {
        $authUser = User::factory()->create();
        $incompleteUser = User::factory()->create(['password_is_set' => false]);

        Sanctum::actingAs($authUser);

        $this->postJson('/api/networking/toggle-connection', [
            'target_id' => $incompleteUser->id,
            'action' => 'connect',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'This user has not completed their profile yet.');

        $this->assertDatabaseMissing('connections', [
            'requester_id' => $authUser->id,
            'target_id' => $incompleteUser->id,
        ]);
    }

    public function test_discover_returns_users_in_stable_order(): void
    {
        $authUser = User::factory()->create();
        $zara = User::factory()->create(['name' => 'Zara', 'last_name' => 'Bravo']);
        $adam = User::factory()->create(['name' => 'Adam', 'last_name' => 'Delta']);
        $anna = User::factory()->create(['name' => 'Anna', 'last_name' => 'Alpha']);

        Sanctum::actingAs($authUser);

        $response = $this->getJson('/api/networking/discover');

        $response->assertOk();
        $this->assertSame(
            [$adam->id, $anna->id, $zara->id],
            collect($response->json('data'))->pluck('id')->all()
        );
    }
}
