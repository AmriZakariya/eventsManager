<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuntimeRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_config_endpoint_responds_successfully(): void
    {
        $this->getJson('/api/config/home')
            ->assertOk()
            ->assertJsonStructure([
                'layout',
            ]);
    }
}
