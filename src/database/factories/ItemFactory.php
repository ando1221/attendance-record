<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'condition_id' => null,
            'name' => $this->faker->word(),
            'brand_name' => $this->faker->optional()->company(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(100, 100000),
            'status' => 'on_sale',
            'image_path' => 'products/test.jpg',
        ];
    }

    public function sold(): static
    {
        return $this->state(function () {
            return [
                'status' => 'sold',
            ];
        });
    }
}