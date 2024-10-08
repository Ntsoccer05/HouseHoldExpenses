<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IncomeCategory>
 */
class IncomeCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'user_id' => User::inRandomOrder()->value('id'),
            // Typeモデル内にあるidの中からランダムに割り当てられる（新たに生成されない）
            'type_id' => Type::inRandomOrder()->value('id'),
            // 'fixed_category_id' => NULL,
            'content' => fake()->text(50),
            'icon' => fake()->text(5),
        ];
    }
}