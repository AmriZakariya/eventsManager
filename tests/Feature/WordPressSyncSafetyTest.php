<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressSyncSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config(['services.wordpress_sync.inbound_token' => 'inbound-token']);
    }

    private function sync(array $payload)
    {
        return $this->withToken('inbound-token')
            ->postJson('/api/webhooks/wordpress/sync-user', $payload)
            ->assertOk();
    }

    public function test_existing_password_is_kept(): void
    {
        $user = User::factory()->create(['email' => 'pro@example.com', 'password' => Hash::make('app-secret'), 'password_is_set' => true]);

        $this->sync(['email' => 'pro@example.com', 'first_name' => 'Pro', 'password' => 'website-secret']);

        $this->assertTrue(Hash::check('app-secret', $user->fresh()->password));
    }

    public function test_account_without_password_gets_the_website_one(): void
    {
        User::factory()->create(['email' => 'imported@example.com', 'password_is_set' => false]);

        $this->sync(['email' => 'imported@example.com', 'first_name' => 'Imp', 'password' => 'website-secret']);

        $user = User::where('email', 'imported@example.com')->sole();
        $this->assertTrue($user->password_is_set);
        $this->assertTrue(Hash::check('website-secret', $user->password));
    }

    public function test_exhibitor_is_not_downgraded_and_visitor_can_be_upgraded(): void
    {
        $exhibitor = User::factory()->create(['email' => 'ex@example.com', 'app_role' => User::APP_ROLE_EXHIBITOR]);
        $visitor = User::factory()->create(['email' => 'vis@example.com', 'app_role' => User::APP_ROLE_VISITOR]);
        $admin = User::factory()->create(['email' => 'adm@example.com', 'app_role' => User::APP_ROLE_ADMIN]);

        $this->sync(['email' => 'ex@example.com', 'first_name' => 'Ex', 'role' => 'visitor']);
        $this->sync(['email' => 'vis@example.com', 'first_name' => 'Vis', 'role' => 'exhibitor']);
        $this->sync(['email' => 'adm@example.com', 'first_name' => 'Adm', 'role' => 'visitor']);

        $this->assertSame(User::APP_ROLE_EXHIBITOR, $exhibitor->fresh()->app_role);
        $this->assertSame(User::APP_ROLE_EXHIBITOR, $visitor->fresh()->app_role);
        $this->assertSame(User::APP_ROLE_ADMIN, $admin->fresh()->app_role);
    }

    public function test_empty_values_do_not_erase_the_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'keep@example.com', 'name' => 'Sara', 'last_name' => 'Amal',
            'phone' => '+212600000000', 'job_title' => 'Manager', 'city' => 'Rabat',
        ]);

        $this->sync(['email' => 'keep@example.com', 'first_name' => '', 'phone' => '', 'city' => 'Casablanca']);

        $user->refresh();
        $this->assertSame('Sara', $user->name);
        $this->assertSame('Amal', $user->last_name);
        $this->assertSame('+212600000000', $user->phone);
        $this->assertSame('Manager', $user->job_title);
        $this->assertSame('Casablanca', $user->city);
    }

    public function test_new_website_user_gets_everything(): void
    {
        $this->sync([
            'email' => 'new@example.com', 'first_name' => 'New', 'last_name' => 'Person',
            'password' => 'website-secret', 'role' => 'exhibitor', 'city' => 'Tanger',
        ]);

        $user = User::where('email', 'new@example.com')->sole();
        $this->assertSame('New', $user->name);
        $this->assertSame(User::APP_ROLE_EXHIBITOR, $user->app_role);
        $this->assertStringStartsWith('EXH-', $user->badge_code);
        $this->assertTrue(Hash::check('website-secret', $user->password));
        $this->assertSame('Tanger', $user->city);
    }

    public function test_new_user_without_name_still_gets_a_placeholder(): void
    {
        $this->sync(['email' => 'noname@example.com']);

        $this->assertSame('Unknown', User::where('email', 'noname@example.com')->sole()->name);
    }
}
