<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Payments\Models\PaymentPlan>
 */
class PaymentPlanFactory extends Factory
{
    protected $model = \App\Payments\Models\PaymentPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Orders\Models\Order::factory(),
            'installment_number' => fake()->numberBetween(1, 12),
            'amount' => fake()->randomFloat(2, 50, 2000),
            'is_paid' => false,
            'due_date' => fake()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
