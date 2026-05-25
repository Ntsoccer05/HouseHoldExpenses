<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ExpenceCategory;
use App\Models\FixedExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReplicateFixedExpensesTest extends TestCase
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

    public function test_replicates_active_fixed_expenses(): void
    {
        FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
        ]);

        $this->artisan('fixed-expenses:replicate --month=202601')
            ->assertExitCode(0);

        $this->assertDatabaseHas('contents', [
            'user_id'          => $this->user->id,
            'amount'           => 100000,
            'is_fixed_expense' => 1,
        ]);
    }

    public function test_does_not_duplicate_existing_replication(): void
    {
        $fixedExpense = FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
        ]);

        $this->artisan('fixed-expenses:replicate --month=202601')->assertExitCode(0);
        $this->artisan('fixed-expenses:replicate --month=202601')->assertExitCode(0);

        $this->assertSame(1, Content::where('fixed_expense_id', $fixedExpense->id)->count());
    }

    public function test_smart_date_adjustment_for_month_end(): void
    {
        FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 50000,
            'content'           => '保険',
            'fixed_expense_day' => 31,
        ]);

        // 2月は31日が存在しないので28日（平年）になるはず
        $this->artisan('fixed-expenses:replicate --month=202502')->assertExitCode(0);

        $content = Content::where('is_fixed_expense', 1)->first();
        $this->assertSame('2025-02-28', $content->recorded_at->format('Y-m-d'));
    }

    public function test_skips_inactive_fixed_expenses(): void
    {
        FixedExpense::create([
            'user_id'           => $this->user->id,
            'type_id'           => 2,
            'category_id'       => $this->category->id,
            'amount'            => 100000,
            'content'           => '家賃',
            'fixed_expense_day' => 1,
            'is_active'         => false,
        ]);

        $this->artisan('fixed-expenses:replicate --month=202601')->assertExitCode(0);

        $this->assertDatabaseCount('contents', 0);
    }
}
