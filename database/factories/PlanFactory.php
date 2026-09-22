<?php

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->randomElement([
                'Basic',
                'Professional',
                'Enterprise',
            ]),
            'base_price' => fake()->randomElement([
                500,
                1000,
                2000,
                5000,
            ]),
            'billing_cycle' => 'monthly',
            'included_units' => fake()->randomElement([
                1000,
                5000,
                10000,
                50000,
            ]),
            'overage_rate' => fake()->randomElement([
                0.05,
                0.10,
                0.20,
                0.50,
            ]),
        ];
    }
}