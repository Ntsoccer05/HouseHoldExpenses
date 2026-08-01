<?php

namespace Database\Seeders;

use App\Models\ExpenceCategory;
use App\Models\FixedExpense;
use App\Models\IncomeCategory;
use App\Models\SplitGroup;
use App\Models\SplitGroupSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * claude-in-chrome などでの画面確認用に、TestAccountSeeder で作成した
 * テストアカウントへ固定収支・収支分担グループのテストデータを投入するSeeder。
 * TestAccountSeeder（ユーザー・カテゴリ）を先に実行しておく必要がある。
 */
class TestFixedExpenseSplitGroupSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', TestAccountSeeder::TEST_EMAIL)->first();

        if (! $user) {
            $this->command->warn('TestAccountSeederを先に実行してください。');

            return;
        }

        // 冪等性のため、毎回削除してから作り直す
        FixedExpense::where('user_id', $user->id)->delete();
        SplitGroup::where('user_id', $user->id)->delete();

        $expenseCategory = ExpenceCategory::where('user_id', $user->id)
            ->where('type_id', config('app.expense_type_id'))
            ->orderBy('filtered_id')
            ->first();
        $incomeCategory = IncomeCategory::where('user_id', $user->id)
            ->where('type_id', config('app.income_type_id'))
            ->orderBy('filtered_id')
            ->first();

        if (! $expenseCategory || ! $incomeCategory) {
            $this->command->warn('カテゴリが見つかりません。TestAccountSeederを先に実行してください。');

            return;
        }

        FixedExpense::create([
            'user_id' => $user->id,
            'type_id' => config('app.expense_type_id'),
            'category_id' => $expenseCategory->id,
            'amount' => 80000,
            'content' => '家賃（テストseed）',
            'fixed_expense_day' => 1,
            'is_active' => true,
        ]);

        FixedExpense::create([
            'user_id' => $user->id,
            'type_id' => config('app.expense_type_id'),
            'category_id' => $expenseCategory->id,
            'amount' => 6000,
            'content' => 'インターネット代（テストseed）',
            'fixed_expense_day' => 10,
            'is_active' => true,
        ]);

        FixedExpense::create([
            'user_id' => $user->id,
            'type_id' => config('app.income_type_id'),
            'category_id' => $incomeCategory->id,
            'amount' => 300000,
            'content' => '給与（テストseed）',
            'fixed_expense_day' => 25,
            'is_active' => true,
        ]);

        $splitGroup = SplitGroup::create([
            'user_id' => $user->id,
            'label' => '夫婦（テストseed）',
        ]);

        SplitGroupSetting::create([
            'split_group_id' => $splitGroup->id,
            'income_other_ratio' => 50,
            'income_other_offset' => 0,
            'expense_other_ratio' => 50,
            'expense_other_offset' => 0,
        ]);

        $this->command->info("テストアカウント（{$user->email}）に固定収支3件・分担グループ1件を登録しました。");
    }
}
