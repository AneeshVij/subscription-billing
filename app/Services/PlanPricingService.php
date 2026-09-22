<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

class PlanPricingService
{
    public function getPlan(int $planId): Plan
    {
        return Cache::remember(
            "plan:pricing:{$planId}",
            now()->addMinutes(30),
            function () use ($planId) {
                return Plan::findOrFail($planId);
            }
        );
    }

    public function forgetPlan(int $planId): void
    {
        Cache::forget("plan:pricing:{$planId}");
    }
}