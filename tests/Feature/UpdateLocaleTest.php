<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_locale(): void
    {
        $user = User::factory()->create([
            'locale' => 'en',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/auth/update-locale', [
            'locale' => 'fr',
        ])
            ->assertOk()
            ->assertJsonPath('user.locale', 'fr');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'fr',
        ]);
    }

    public function test_locale_must_be_supported(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/auth/update-locale', [
            'locale' => 'de',
        ])->assertUnprocessable();
    }
}
