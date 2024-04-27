<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => $this->faker->randomNumber(5),
            'amount_usd' => $amount = $this->faker->randomFloat(2, 1, 100),
            'amount_bdt' => intval($amount * 140 + ($amount < 10 ? 50 : 0)),
            'payment_method' => $this->faker->randomElement(['Cash', 'Bank', 'bKash', 'Bank+bKash']),
            'description' => $this->faker->text(),
        ];
    }
}
