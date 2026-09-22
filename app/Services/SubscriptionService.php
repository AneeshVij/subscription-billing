<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function changePlan(
        Subscription $subscription,
        Plan $newPlan,
        string $effectiveAt
    ): SubscriptionChange {

        if ($subscription->customer->merchant_id !== $newPlan->merchant_id) {
            throw new \InvalidArgumentException(
                'Plan must belong to the customer merchant.'
            );
        }

        if ($subscription->plan_id === $newPlan->id) {
            throw new \InvalidArgumentException(
                'Customer is already on this plan.'
            );
        }

        return DB::transaction(function () use (
            $subscription,
            $newPlan,
            $effectiveAt
        ) {

            $change = SubscriptionChange::create([
                'subscription_id' => $subscription->id,
                'old_plan_id' => $subscription->plan_id,
                'new_plan_id' => $newPlan->id,
                'effective_at' => $effectiveAt,
            ]);

            $subscription->update([
                'plan_id' => $newPlan->id,
            ]);

            return $change;
        });
    }
}