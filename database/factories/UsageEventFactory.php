<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UsageEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'usage_date' => fake()->dateTimeBetween(
                '-30 days',
                'now'
            )->format('Y-m-d'),
            'units' => fake()->numberBetween(1, 1000),
            'metadata' => [
                'source' => 'demo',
            ],
        ];
    }
}