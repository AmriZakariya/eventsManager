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
