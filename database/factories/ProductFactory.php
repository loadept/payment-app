<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Orders\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = \App\Products\Models\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'  => fake()->word(),
            'brand' => fake()->word(),
            'price' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
