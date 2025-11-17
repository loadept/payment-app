<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Orders\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = \App\Orders\Models\Order::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id'  => \App\Users\Models\User::factory(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'installments' => $this->faker->numberBetween(1, 8),
            'order_status_id' => \App\Orders\Models\OrderStatus::factory(),
        ];
    }
}
