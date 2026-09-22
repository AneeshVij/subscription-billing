<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Technologies',
        ]);

        $basicPlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Basic',
            'base_price' => 500.00,
            'billing_cycle' => 'monthly',
            'included_units' => 5000,
            'average_rate' => 0.10,
        ]);

        $professionalPlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Professional',
            'base_price' => 1000.00,
            'billing_cycle' => 'monthly',
            'included_units' => 10000,
            'average_rate' => 0.08,
        ]);

        $enterprisePlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Enterprise',
            'base_price' => 2500.00,
            'billing_cycle' => 'monthly',
            'included_units' => 50000,
            'average_rate' => 0.05,
        ]);

        $plans = [
            $basicPlan,
            $professionalPlan,
            $enterprisePlan,
        ];

        $customers = Customer::factory()
            ->count(10)
            ->create([
                'merchant_id' => $merchant->id,
            ]);

        foreach ($customers as $customer) {
            $plan = fake()->randomElement($plans);

            Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'starts_at' => now()->subDays(
                    fake()->numberBetween(1, 20)
                ),
                'ends_at' => null,
                'status' => 'active',
            ]);
        }
    }
}