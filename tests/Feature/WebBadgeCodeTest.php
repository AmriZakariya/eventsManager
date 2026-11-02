<?php

namespace Tests\Feature;

use App\Models\User;
use App\Service\WordPressBadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebBadgeCodeTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_CODE = 'HCX-11111111-2222-3333-4444-555555555555';
    private const OLD_CODE = 'HCX-aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
    private const LOOKUP = 'https://hygiecleanexpo.com/wp-json/hc-sync/v1/badges/lookup';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.wordpress_sync.outbound_token'   => 'test-token',
            'services.wordpress_sync.badge_lookup_url' => self::LOOKUP,
            'services.wordpress_sync.badge_check_url'  => 'https://hygiecleanexpo.com/check-badge/?code=',
        ]);
    }

    public function test_lookup_stores_the_newest_badge_and_sends_token_and_email(): void
    {
        Http::fake([self::LOOKUP => Http::response(['success' => true, 'count' => 2, 'badges' => [
            ['uid' => self::NEW_CODE, 'badge_code' => self::NEW_CODE],
            ['uid' => self::OLD_CODE, 'badge_code' => self::OLD_CODE],
        ]])]);
        $user = User::factory()->create(['email' => 'Visitor@Example.com']);

        $this->assertSame(self::NEW_CODE, app(WordPressBadgeService::class)->ensure($user));
        $this->assertSame(self::NEW_CODE, $user->fresh()->web_badge_code);

        Http::assertSent(fn (Request $request) => $request->url() === self::LOOKUP
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['email'] === 'visitor@example.com');
    }

    public function test_not_found_is_not_retried_right_away(): void
    {
        Http::fake([self::LOOKUP => Http::response(['success' => false], 404)]);
        $user = User::factory()->create();
        $service = app(WordPressBadgeService::class);

        $this->assertNull($service->ensure($user));
        $this->assertNull($service->ensure($user->fresh()));

        Http::assertSentCount(1);
        $this->assertNotNull($user->fresh()->web_badge_checked_at);
    }

    public function test_website_down_never_breaks_and_keeps_existing_code(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $user = User::factory()->create(['web_badge_code' => self::OLD_CODE]);

        $this->assertSame(self::OLD_CODE, app(WordPressBadgeService::class)->refresh($user));
    }

    public function test_invalid_code_from_website_is_ignored(): void
    {
        Http::fake([self::LOOKUP => Http::response(['badges' => [['uid' => 'not-a-badge']]])]);
        $user = User::factory()->create();

        $this->assertNull(app(WordPressBadgeService::class)->refresh($user));
    }

    public function test_me_returns_the_website_check_url_as_badge_qr(): void
    {
        Http::fake([self::LOOKUP => Http::response(['badges' => [['uid' => self::NEW_CODE]]])]);
        $user = User::factory()->create(['badge_code' => 'VIS-ABC123']);

        Sanctum::actingAs($user);
        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.web_badge_code', self::NEW_CODE)
            ->assertJsonPath('data.badge_qr', 'https://hygiecleanexpo.com/check-badge/?code=' . self::NEW_CODE)
            ->assertJsonPath('data.badge_code', 'VIS-ABC123');
    }

    public function test_without_website_badge_the_qr_keeps_the_app_code(): void
    {
        Http::fake([self::LOOKUP => Http::response([], 404)]);
        $user = User::factory()->create(['badge_code' => 'VIS-ABC123']);

        Sanctum::actingAs($user);
        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.web_badge_code', null)
            ->assertJsonPath('data.badge_qr', 'VIS-ABC123');
    }

    public function test_badge_pdf_is_generated_with_the_website_qr(): void
    {
        Http::fake([self::LOOKUP => Http::response(['badges' => [['uid' => self::NEW_CODE]]])]);
        $user = User::factory()->create(['name' => 'Sarah', 'last_name' => 'Visitor']);

        Sanctum::actingAs($user);
        $response = $this->get('/api/generateBadge')->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(self::NEW_CODE, $user->fresh()->web_badge_code);
    }

    public function test_wordpress_sync_webhook_stores_the_uid(): void
    {
        Http::fake();
        config(['services.wordpress_sync.inbound_token' => 'inbound-token']);

        $this->withToken('inbound-token')->postJson('/api/webhooks/wordpress/sync-user', [
            'email'      => 'new@example.com',
            'first_name' => 'New',
            'uid'        => self::NEW_CODE,
            'badge_code' => self::NEW_CODE,
        ])->assertOk();

        $user = User::where('email', 'new@example.com')->sole();
        $this->assertSame(self::NEW_CODE, $user->web_badge_code);
        $this->assertStringStartsWith('VIS-', $user->badge_code);
    }

    public function test_reception_date_is_set_once_and_kept_on_recheck(): void
    {
        Http::fake([self::LOOKUP => Http::response(['badges' => [['uid' => self::NEW_CODE]]])]);
        $user = User::factory()->create();
        $service = app(WordPressBadgeService::class);

        $this->travelTo(now()->subDay());
        $service->refresh($user);
        $receivedAt = $user->fresh()->web_badge_received_at;
        $this->assertNotNull($receivedAt);

        $this->travelBack();
        $service->refresh($user->fresh());
        $this->assertTrue($receivedAt->equalTo($user->fresh()->web_badge_received_at));
    }

    public function test_user_without_code_has_no_reception_date(): void
    {
        Http::fake([self::LOOKUP => Http::response([], 404)]);
        $user = User::factory()->create();

        app(WordPressBadgeService::class)->refresh($user);

        $this->assertNull($user->fresh()->web_badge_received_at);
    }

    public function test_users_list_filters_by_website_badge(): void
    {
        $admin = User::factory()->create([
            'app_role'    => User::APP_ROLE_ADMIN,
            'permissions' => ['platform.index' => true, 'platform.systems.users' => true],
        ]);
        User::factory()->create(['email' => 'with-badge@example.com', 'web_badge_code' => self::NEW_CODE]);
        User::factory()->create(['email' => 'no-badge@example.com']);

        $this->actingAs($admin)->get(route('platform.systems.users', ['web_badge' => 'received']))
            ->assertOk()
            ->assertSee('with-badge@example.com')
            ->assertDontSee('no-badge@example.com')
            ->assertSee('Website badges');

        $this->actingAs($admin)->get(route('platform.systems.users', ['web_badge' => 'missing']))
            ->assertOk()
            ->assertSee('no-badge@example.com')
            ->assertDontSee('with-badge@example.com');
    }
}
