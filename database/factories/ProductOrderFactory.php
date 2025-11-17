<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Orders\Models\ProductOrder>
 */
class ProductOrderFactory extends Factory
{
    protected $model = \App\Products\Models\ProductOrder::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'   => \App\Orders\Models\Order::factory(),
            'product_id' => \App\Products\Models\Product::factory(),
            'quantity'   => fake()->numberBetween(1, 10),
        ];
    }
}
