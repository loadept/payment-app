<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Payments\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = \App\Payments\Models\Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_plan_id' => \App\Payments\Models\PaymentPlan::factory(),
            'amount'          => fake()->randomFloat(2, 10, 5000),
            'is_success'      => false,
        ];
    }
}
