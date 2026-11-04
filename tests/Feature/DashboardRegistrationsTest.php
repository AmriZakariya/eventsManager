<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRegistrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrations_count_visitors_and_exhibitors_but_not_admins(): void
    {
        $admin = User::factory()->create([
            'app_role'    => User::APP_ROLE_ADMIN,
            'permissions' => ['platform.index' => true],
        ]);
        User::factory()->count(3)->create(['app_role' => User::APP_ROLE_VISITOR]);
        User::factory()->count(2)->create(['app_role' => User::APP_ROLE_EXHIBITOR]);
        User::factory()->create(['app_role' => User::APP_ROLE_EXHIBITOR, 'created_at' => now()->subDays(3)]);

        $this->actingAs($admin)
            ->get(route('platform.main'))
            ->assertOk()
            // 3 visitors + 3 exhibitors; the admin is not a registration.
            ->assertSee('data-target="6"', false)
            ->assertSee('3 visitors · 3 exhibitors')
            // Joined today: 3 visitors + 2 exhibitors.
            ->assertSee('+5</span>', false)
            ->assertSee('Exhibitor companies')
            ->assertSee('3 exhibitor staff');
    }
}
