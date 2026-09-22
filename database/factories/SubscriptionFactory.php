<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $startDate = now()->subDays(
            fake()->numberBetween(1, 20)
        );

        return [
            'customer_id' => Customer::factory(),
            'plan_id' => Plan::factory(),
            'starts_at' => $startDate,
            'ends_at' => null,
            'status' => 'active',
        ];
    }
}