<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * /health (web) と /api/health (api) は元々クロージャルートとして定義されていたが、
 * route:cache はクロージャをシリアライズできずエラーになるため、コントローラー化する
 * (serverless-migration-lessons-learned.md 3-16)。挙動は変えない。
 */
class HealthCheckRouteTest extends TestCase
{
    public function test_web_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_api_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertExactJson(['status' => 'ok']);
    }
}
