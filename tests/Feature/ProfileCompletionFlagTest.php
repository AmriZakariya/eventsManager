<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileCompletionFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_profile_exposes_profile_completion_flags(): void
    {
        $user = User::factory()->create([
            'password_is_set' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.password_is_set', false)
            ->assertJsonPath('data.profile_completed', false)
            ->assertJsonPath('data.needs_profile_completion', true);
    }

    public function test_user_lists_expose_profile_completion_flags(): void
    {
        $authUser = User::factory()->create([
            'password_is_set' => true,
            'phone' => '+212600000000',
            'is_visible' => true,
        ]);

        User::factory()->create([
            'name' => 'Incomplete',
            'last_name' => 'User',
            'password_is_set' => false,
            'phone' => '+212600000001',
            'is_visible' => true,
        ]);

        Sanctum::actingAs($authUser);

        $this->getJson('/api/networking/discover')
            ->assertOk()
            ->assertJsonPath('data.0.password_is_set', false)
            ->assertJsonPath('data.0.profile_completed', false)
            ->assertJsonPath('data.0.needs_profile_completion', true);
    }

    public function test_admin_profile_completion_badge_uses_password_state(): void
    {
        $incompleteUser = User::factory()->make([
            'password_is_set' => false,
        ]);

        $completeUser = User::factory()->make([
            'password_is_set' => true,
        ]);

        $this->assertStringContainsString('Needs Profile', $incompleteUser->profileCompletionBadgeHtml());
        $this->assertStringContainsString('No password is set', $incompleteUser->profileCompletionBadgeHtml());
        $this->assertStringContainsString('Profile Complete', $completeUser->profileCompletionBadgeHtml());
    }
}
