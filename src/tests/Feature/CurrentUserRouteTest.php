<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/user は元々クロージャルートとして定義されていたが、
 * route:cache導入のためコントローラー化する。挙動は変えない。
 */
class CurrentUserRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_own_user_info(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_guest_cannot_fetch_user_info(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
