<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Content;
use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use App\Models\MonthlyAmount;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Seeder;

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
        Type::factory(2)->create();
        ExpenceCategory::factory(5)->create();
        IncomeCategory::factory(5)->create();
        Content::factory(10)->create();
        MonthlyAmount::factory(10)->create();
    }
}