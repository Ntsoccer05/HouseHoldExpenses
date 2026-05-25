<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Type>
 */
class TypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $enName = $this->faker->randomElement(['income', 'expense']);

        return [
            'name' => $enName === 'income' ? '収入' : '支出',
            'en_name' => $enName,
        ];
    }
}
