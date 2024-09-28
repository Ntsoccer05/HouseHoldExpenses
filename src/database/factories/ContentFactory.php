<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Content>
 */
class ContentFactory extends Factory
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
            // 'user_id' => User::factory(),
            // // Typeモデル内にあるidの中からランダムに割り当てられる（新たに生成されない）
            // 'type_id' => Type::inRandomOrder()->value('id'),
            // 'amount' => fake()->randomNumber(),
            // 'content' => fake()->text(100),
            // 'recorded_at' => fake()->dateTime()
        ];
    }
}