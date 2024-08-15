<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Content;
use App\Models\ExpenceCategory;
use App\Models\FixedCategory;
use App\Models\FixedExpenseCategory;
use App\Models\FixedIncomeCategory;
use App\Models\IncomeCategory;
use App\Models\MonthlyAmount;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        User::factory(10)->create();
        DB::table('types')->insert([
            [
                'id' => 1,
                'name' => '収入',
                'en_name' => 'income'
            ],
            [
                'id' => 2,
                'name' => '支出',
                'en_name' => 'expense'
            ],
          
        ]);
        foreach(config('app.income_contents') as $key => $incomeContent){
            FixedCategory::factory()->create([
                'id' => $key + 1,
                'type_id' => config('app.income_type_id'),
                'content' => $incomeContent
            ]);
        }
        $expense_id = FixedCategory::latest('id')->first()->id;
        foreach(config('app.expense_contents') as $key => $expenseContent){
            FixedCategory::factory()->create([
                'id' => $expense_id + $key + 1,
                'type_id' => config('app.expense_type_id'),
                'content' => $expenseContent
            ]);
        }
        ExpenceCategory::factory(5)->create();
        IncomeCategory::factory(5)->create();
        Content::factory(10)->create();
        MonthlyAmount::factory(10)->create();
    }
}