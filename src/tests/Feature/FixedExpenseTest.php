<?php

namespace Tests\Feature;

use App\Models\ExpenceCategory;
use App\Models\FixedExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FixedExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ExpenceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        DB::table('types')->insert([
            ['id' => 1, 'name' => '収入', 'en_name' => 'income'],
            ['id' => 2, 'name' => '支出', 'en_name' => 'expense'],
        ]);
        $this->category = ExpenceCategory::create([
            'user_id'     => $this->user->id,
            'type_id'     => 2,
            'content'     => '家賃',
            'filtered_id' => 1,
            'icon'        => '',
        ]);
    }

    public function test_can_list_fixed_expenses(): void
    {
        FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/fixed-expenses?user_id=' . $this->user->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'fixedExpenses');
    }

    public function test_can_create_fixed_expense(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/fixed-expenses', [
                'user_id'           => $this->user->id,
                'category_id'       => $this->category->id,
                'amount'            => 100000,
                'content'           => '家賃',
                'fixed_expense_day' => 1,
            ]);

        $response->assertStatus(200)->assertJsonPath('status', 200);
        $this->assertDatabaseHas('fixed_expenses', [
            'user_id'           => $this->user->id,
            'amount'            => 100000,
            'fixed_expense_day' => 1,
        ]);
    }

    public function test_can_update_fixed_expense(): void
    {
        $fixedExpense = FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/fixed-expenses/' . $fixedExpense->id, [
                'user_id' => $this->user->id,
                'amount'  => 110000,
            ]);

        $response->assertStatus(200)->assertJsonPath('status', 200);
        $this->assertDatabaseHas('fixed_expenses', ['id' => $fixedExpense->id, 'amount' => 110000]);
    }

    public function test_can_soft_delete_fixed_expense(): void
    {
        $fixedExpense = FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/fixed-expenses/' . $fixedExpense->id . '?user_id=' . $this->user->id);

        $response->assertStatus(200)->assertJsonPath('status', 200);
        $this->assertSoftDeleted('fixed_expenses', ['id' => $fixedExpense->id]);
    }

    public function test_cannot_update_other_users_fixed_expense(): void
    {
        $otherUser = User::factory()->create();
        $fixedExpense = FixedExpense::create([
            'user_id'           => $otherUser->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/fixed-expenses/' . $fixedExpense->id, [
                'user_id' => $this->user->id,
                'amount'  => 999999,
            ]);

        $response->assertStatus(403);
    }
}
