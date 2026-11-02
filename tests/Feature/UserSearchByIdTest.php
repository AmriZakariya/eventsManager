<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchByIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_users_by_id(): void
    {
        $admin = User::factory()->create([
            'app_role'    => User::APP_ROLE_ADMIN,
            'permissions' => ['platform.index' => true, 'platform.systems.users' => true],
        ]);
        $target = User::factory()->create(['id' => 498, 'email' => 'target@example.com', 'phone' => null]);
        User::factory()->create(['email' => 'phone-match@example.com', 'phone' => '+212600498111']);
        User::factory()->create(['email' => 'unrelated@example.com', 'phone' => null]);

        // Plain number: the ID plus the usual text matches.
        $this->actingAs($admin)->get(route('platform.systems.users', ['search' => '498']))
            ->assertOk()
            ->assertSee($target->email)
            ->assertSee('phone-match@example.com')
            ->assertDontSee('unrelated@example.com');

        // "#498": that user only.
        $this->actingAs($admin)->get(route('platform.systems.users', ['search' => '#498']))
            ->assertOk()
            ->assertSee($target->email)
            ->assertDontSee('phone-match@example.com');

        // Text search still works.
        $this->actingAs($admin)->get(route('platform.systems.users', ['search' => 'unrelated']))
            ->assertOk()
            ->assertSee('unrelated@example.com')
            ->assertDontSee($target->email);
    }
}
