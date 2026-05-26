<?php

namespace Tests\Feature;

use App\Models\SplitGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SplitGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        DB::table('types')->insert([
            ['id' => 1, 'name' => '収入', 'en_name' => 'income'],
            ['id' => 2, 'name' => '支出', 'en_name' => 'expense'],
        ]);
    }

    public function test_can_list_split_groups(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create([]);

        $response = $this->actingAs($this->user)->getJson('/api/split-groups');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'splitGroups');
    }

    public function test_can_create_split_group(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/split-groups', [
            'label' => 'パートナー',
        ]);

        $response->assertStatus(201)->assertJsonPath('status', 200);
        $this->assertDatabaseHas('split_groups', ['user_id' => $this->user->id, 'label' => 'パートナー']);
        $this->assertDatabaseHas('split_group_settings', []);
    }

    public function test_can_update_split_group(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}", [
            'label' => 'ルームメイト',
        ]);

        $response->assertStatus(200)->assertJsonPath('splitGroup.label', 'ルームメイト');
        $this->assertDatabaseHas('split_groups', ['id' => $group->id, 'label' => 'ルームメイト']);
    }

    public function test_cannot_update_other_users_split_group(): void
    {
        $otherUser = User::factory()->create();
        $group = SplitGroup::create(['user_id' => $otherUser->id, 'label' => '他人のグループ']);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}", [
            'label' => '改ざん',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_split_group(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);

        $response = $this->actingAs($this->user)->deleteJson("/api/split-groups/{$group->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('split_groups', ['id' => $group->id]);
    }

    public function test_can_update_settings(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create([]);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}/settings", [
            'income_other_ratio'  => 40,
            'expense_other_ratio' => 50,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('split_group_settings', [
            'split_group_id'      => $group->id,
            'income_other_ratio'  => 40,
            'expense_other_ratio' => 50,
        ]);
    }

    public function test_can_update_category_overrides(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}/category-overrides", [
            'overrides' => [
                ['category_id' => 1, 'type_id' => 2, 'other_ratio' => 70],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('split_group_category_overrides', [
            'split_group_id' => $group->id,
            'category_id'    => 1,
            'other_ratio'    => 70,
        ]);
    }

    public function test_preview_returns_correct_split(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create(['income_other_ratio' => 40, 'expense_other_ratio' => 50]);

        DB::table('income_categories')->insert([
            'id' => 1, 'user_id' => $this->user->id, 'type_id' => 1,
            'content' => '給与', 'icon' => '', 'deleted' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('contents')->insert([
            'user_id' => $this->user->id, 'type_id' => 1, 'category_id' => 1,
            'amount' => 100000, 'content' => '給与', 'recorded_at' => '2026-05-15 00:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/split-groups/{$group->id}/preview?month=202605");

        $response->assertStatus(200)
            ->assertJsonPath('income.total', 100000)
            ->assertJsonPath('income.other', 40000)
            ->assertJsonPath('income.self', 60000);
    }

    public function test_preview_excludes_unconfigured_types(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create(['income_other_ratio' => 40, 'expense_other_ratio' => null]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/split-groups/{$group->id}/preview?month=202605");

        $response->assertStatus(200)
            ->assertJsonMissing(['expense'])
            ->assertJsonMissing(['balance']);
    }
}
