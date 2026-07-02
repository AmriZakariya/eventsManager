<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_logs_are_deduplicated_for_same_user_in_short_window(): void
    {
        $user = User::factory()->create();

        $first = AuditLogService::log(
            action: 'login',
            description: 'User logged in',
            userId: $user->id,
            source: 'admin',
        );

        $second = AuditLogService::log(
            action: 'login',
            description: 'User logged in',
            userId: $user->id,
            source: 'web',
        );

        $this->assertTrue($first->is($second));
        $this->assertSame(1, AuditLog::where('action', 'login')->where('user_id', $user->id)->count());
    }

    public function test_login_logs_are_allowed_after_dedupe_window(): void
    {
        $user = User::factory()->create();

        AuditLogService::logLogin($user->id);

        $this->travel(11)->seconds();

        AuditLogService::logLogin($user->id);

        $this->assertSame(2, AuditLog::where('action', 'login')->where('user_id', $user->id)->count());
    }
}
