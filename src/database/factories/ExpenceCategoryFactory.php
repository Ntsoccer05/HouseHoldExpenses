<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenceCategory>
 */
class ExpenceCategoryFactory extends Factory
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
            // `User::factory()` の記述はExpenceCategoryのダミーデータを1件生成すると、それと同時にuser_idが新たに生成され、そのuser_idが`ExpenceCategory->user_id` にセットされる
            'user_id' => User::factory(),
            // Typeモデル内にあるidの中からランダムに割り当てられる（新たに生成されない）
            'type_id' => Type::inRandomOrder()->value('id'),
            'content' => fake()->text(50),
        ];
    }
}