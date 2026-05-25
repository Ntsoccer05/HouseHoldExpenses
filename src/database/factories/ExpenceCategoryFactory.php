<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenceCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type_id' => Type::factory(),
            'content' => $this->faker->text(20),
            'icon' => '',
            'deleted' => 0,
        ];
    }
}
