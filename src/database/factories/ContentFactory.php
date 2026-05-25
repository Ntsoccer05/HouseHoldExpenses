<?php

namespace Database\Factories;

use App\Models\ExpenceCategory;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type_id' => Type::factory(),
            'category_id' => ExpenceCategory::factory(),
            'amount' => $this->faker->numberBetween(100, 100000),
            'content' => $this->faker->text(50),
            'recorded_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
