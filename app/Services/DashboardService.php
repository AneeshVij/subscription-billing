<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get merchant-specific dashboard data.
     */
    public function getMerchantDashboardData(
        int $merchantId
    ): array {
        $now = Carbon::now();

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $previousMonthStart = $now
            ->copy()
            ->subMonth()
            ->startOfMonth();

        $previousMonthEnd = $now
            ->copy()
            ->subMonth()
            ->endOfMonth();

        return [
            'topCustomers' =>
                $this->getMerchantTopCustomers(
                    $merchantId,
                    $monthStart,
                    $monthEnd
                ),

            'projectedOverage' =>
                $this->getMerchantProjectedOverage(
                    $merchantId,
                    $monthStart,
                    $monthEnd
                ),

            'usageDropCustomers' =>
                $this->getMerchantUsageDropCustomers(
                    $merchantId,
                    $monthStart,
                    $monthEnd,
                    $previousMonthStart,
                    $previousMonthEnd
                ),
        ];
    }


    /**
     * Get top 5 customers by usage
     * for a specific merchant.
     */
    private function getMerchantTopCustomers(
        int $merchantId,
        Carbon $monthStart,
        Carbon $monthEnd
    ): Collection {
        return UsageEvent::query()
            ->selectRaw(
                'customer_id, SUM(units) as total_usage'
            )
            ->with('customer')
            ->where(
                'merchant_id',
                $merchantId
            )
            ->whereBetween(
                'usage_date',
                [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ]
            )
            ->groupBy('customer_id')
            ->orderByDesc('total_usage')
            ->limit(5)
            ->get();
    }


    /**
     * Get customers whose usage dropped
     * by more than 50% compared with
     * the previous month.
     */
    private function getMerchantUsageDropCustomers(
        int $merchantId,
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $previousMonthStart,
        Carbon $previousMonthEnd
    ): Collection {

        /*
         * Current month usage.
         */
        $currentUsage = UsageEvent::query()
            ->selectRaw(
                'customer_id, SUM(units) as total_usage'
            )
            ->where(
                'merchant_id',
                $merchantId
            )
            ->whereBetween(
                'usage_date',
                [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ]
            )
            ->groupBy('customer_id')
            ->pluck(
                'total_usage',
                'customer_id'
            );


        /*
         * Previous month usage.
         */
        $previousUsage = UsageEvent::query()
            ->selectRaw(
                'customer_id, SUM(units) as total_usage'
            )
            ->where(
                'merchant_id',
                $merchantId
            )
            ->whereBetween(
                'usage_date',
                [
                    $previousMonthStart->toDateString(),
                    $previousMonthEnd->toDateString(),
                ]
            )
            ->groupBy('customer_id')
            ->pluck(
                'total_usage',
                'customer_id'
            );


        /*
         * Combine customer IDs from
         * both months.
         */
        $customerIds = $currentUsage
            ->keys()
            ->merge($previousUsage->keys())
            ->unique();


        /*
         * Load customers belonging to
         * this merchant only.
         */
        $customers = Customer::where(
            'merchant_id',
            $merchantId
        )
        ->whereIn(
            'id',
            $customerIds
        )
        ->get()
        ->keyBy('id');


        $result = collect();


        foreach ($customerIds as $customerId) {

            $current = (int) (
                $currentUsage[$customerId] ?? 0
            );

            $previous = (int) (
                $previousUsage[$customerId] ?? 0
            );


            /*
             * Cannot calculate a percentage
             * when previous usage is zero.
             */
            if ($previous <= 0) {
                continue;
            }


            /*
             * Calculate percentage drop.
             */
            $dropPercentage =
                (
                    ($previous - $current)
                    / $previous
                )
                * 100;


            /*
             * Assignment requirement:
             * usage dropped by more than 50%.
             */
            if ($dropPercentage > 50) {

                $customer = $customers->get(
                    $customerId
                );


                if (!$customer) {
                    continue;
                }


                $result->push([
                    'customer' =>
                        $customer,

                    'current_usage' =>
                        $current,

                    'previous_usage' =>
                        $previous,

                    'drop_percentage' =>
                        round(
                            $dropPercentage,
                            2
                        ),
                ]);
            }
        }


        return $result
            ->sortByDesc(
                'drop_percentage'
            )
            ->values();
    }


    /**
     * Calculate projected overage revenue
     * for a specific merchant.
     *
     * Supports mid-cycle plan changes.
     */
    private function getMerchantProjectedOverage(
        int $merchantId,
        Carbon $monthStart,
        Carbon $monthEnd
    ): float {

        /*
         * Only subscriptions belonging
         * to this merchant.
         */
        $subscriptions = Subscription::with([
            'customer',
            'plan',
        ])
        ->where(
            'status',
            'active'
        )
        ->whereHas(
            'customer',
            function ($query) use ($merchantId) {
                $query->where(
                    'merchant_id',
                    $merchantId
                );
            }
        )
        ->get();


        $projectedRevenue = 0;


        foreach ($subscriptions as $subscription) {

            if (!$subscription->plan) {
                continue;
            }


            /*
             * Subscription start date.
             */
            $subscriptionStart = Carbon::parse(
                $subscription->starts_at
            )->startOfDay();


            /*
             * Subscription end date.
             */
            $subscriptionEnd = $subscription->ends_at
                ? Carbon::parse(
                    $subscription->ends_at
                )->endOfDay()
                : null;


            /*
             * Determine effective dashboard period.
             */
            $effectiveStart =
                $monthStart->greaterThan(
                    $subscriptionStart
                )
                    ? $monthStart->copy()
                    : $subscriptionStart->copy();


            $effectiveEnd =
                $monthEnd->copy();


            if (
                $subscriptionEnd &&
                $effectiveEnd->greaterThan(
                    $subscriptionEnd
                )
            ) {
                $effectiveEnd =
                    $subscriptionEnd->copy();
            }


            /*
             * Subscription is not active
             * during this period.
             */
            if (
                $effectiveEnd->lt(
                    $effectiveStart
                )
            ) {
                continue;
            }


            /*
             * Get plan changes during
             * this dashboard period.
             */
            $changes = SubscriptionChange::with([
                'oldPlan',
                'newPlan',
            ])
            ->where(
                'subscription_id',
                $subscription->id
            )
            ->where(
                'effective_at',
                '>',
                $effectiveStart
            )
            ->where(
                'effective_at',
                '<=',
                $effectiveEnd
            )
            ->orderBy(
                'effective_at'
            )
            ->get();


            /*
             * Start with current subscription plan.
             */
            $currentPlan =
                $subscription->plan;


            /*
             * Find the plan active at
             * the beginning of the period.
             */
            $previousChange =
                SubscriptionChange::with([
                    'newPlan',
                ])
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->where(
                    'effective_at',
                    '<=',
                    $effectiveStart
                )
                ->orderByDesc(
                    'effective_at'
                )
                ->first();


            if ($previousChange) {

                $currentPlan =
                    $previousChange->newPlan;

            } elseif ($changes->isNotEmpty()) {

                $currentPlan =
                    $changes->first()->oldPlan;
            }


            /*
             * Build plan segments.
             */
            $segments = [];

            $segmentStart =
                $effectiveStart->copy();


            foreach ($changes as $change) {

                $changeAt = Carbon::parse(
                    $change->effective_at
                );


                if (
                    $changeAt->gt(
                        $segmentStart
                    )
                ) {

                    $segments[] = [
                        'plan' =>
                            $currentPlan,

                        'start' =>
                            $segmentStart->copy(),

                        'end' =>
                            $changeAt
                                ->copy()
                                ->subSecond(),
                    ];
                }


                /*
                 * New plan starts from
                 * the effective timestamp.
                 */
                $currentPlan =
                    $change->newPlan;

                $segmentStart =
                    $changeAt->copy();
            }


            /*
             * Add final segment.
             */
            if (
                $segmentStart->lte(
                    $effectiveEnd
                )
            ) {

                $segments[] = [
                    'plan' =>
                        $currentPlan,

                    'start' =>
                        $segmentStart->copy(),

                    'end' =>
                        $effectiveEnd->copy(),
                ];
            }


            /*
             * Number of days in the
             * effective billing period.
             */
            $billingDays =
                $effectiveStart
                    ->copy()
                    ->startOfDay()
                    ->diffInDays(
                        $effectiveEnd
                            ->copy()
                            ->startOfDay()
                    )
                + 1;


            /*
             * Calculate overage for
             * each plan segment.
             */
            foreach ($segments as $segment) {

                $plan =
                    $segment['plan'];


                if (!$plan) {
                    continue;
                }


                $segmentStart =
                    $segment['start'];

                $segmentEnd =
                    $segment['end'];


                /*
                 * Number of days in
                 * this segment.
                 */
                $segmentDays =
                    $segmentStart
                        ->copy()
                        ->startOfDay()
                        ->diffInDays(
                            $segmentEnd
                                ->copy()
                                ->startOfDay()
                        )
                    + 1;


                /*
                 * Usage belonging to
                 * this merchant/customer.
                 */
                $units = UsageEvent::where(
                    'merchant_id',
                    $merchantId
                )
                ->where(
                    'customer_id',
                    $subscription->customer_id
                )
                ->whereBetween(
                    'usage_date',
                    [
                        $segmentStart->toDateString(),
                        $segmentEnd->toDateString(),
                    ]
                )
                ->sum('units');


                /*
                 * Prorated included units.
                 */
                $includedUnits =
                    (int) floor(
                        $plan->included_units
                        *
                        (
                            $segmentDays
                            /
                            $billingDays
                        )
                    );


                /*
                 * Calculate overage units.
                 */
                $overageUnits = max(
                    0,
                    (int) $units
                    - $includedUnits
                );


                /*
                 * Calculate projected revenue.
                 */
                $projectedRevenue +=
                    $overageUnits
                    *
                    (float) $plan->overage_rate;
            }
        }


        return round(
            $projectedRevenue,
            2
        );
    }


    /**
     * Existing global dashboard data.
     *
     * Kept for the existing web dashboard.
     */
    public function getDashboardData(): array
    {
        $now = Carbon::now();

        $monthStart =
            $now->copy()->startOfMonth();

        $monthEnd =
            $now->copy()->endOfMonth();

        $previousMonthStart =
            $now
                ->copy()
                ->subMonth()
                ->startOfMonth();

        $previousMonthEnd =
            $now
                ->copy()
                ->subMonth()
                ->endOfMonth();

        return [
            'customerCount' =>
                Customer::count(),

            'totalUsage' =>
                $this->getTotalUsage(
                    $monthStart,
                    $monthEnd
                ),

            'projectedOverage' =>
                $this->getProjectedOverage(
                    $monthStart,
                    $monthEnd
                ),

            'topCustomers' =>
                $this->getTopCustomers(
                    $monthStart,
                    $monthEnd
                ),

            'usageDropCustomers' =>
                $this->getUsageDropCustomers(
                    $monthStart,
                    $monthEnd,
                    $previousMonthStart,
                    $previousMonthEnd
                ),
        ];
    }


    /**
     * Get total usage for the current month.
     */
    private function getTotalUsage(
        Carbon $monthStart,
        Carbon $monthEnd
    ): int {
        return (int) UsageEvent::whereBetween(
            'usage_date',
            [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ]
        )->sum('units');
    }


    /**
     * Get top 5 customers globally.
     */
    private function getTopCustomers(
        Carbon $monthStart,
        Carbon $monthEnd
    ): Collection {
        return UsageEvent::query()
            ->selectRaw(
                'customer_id, SUM(units) as total_usage'
            )
            ->with('customer')
            ->whereBetween(
                'usage_date',
                [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ]
            )
            ->groupBy('customer_id')
            ->orderByDesc('total_usage')
            ->limit(5)
            ->get();
    }


    /**
     * Get global usage drop customers.
     */
    private function getUsageDropCustomers(
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $previousMonthStart,
        Carbon $previousMonthEnd
    ): Collection {
        $currentUsage = UsageEvent::query()
            ->selectRaw(
                'customer_id, SUM(units) as total_usage'
            )
            ->whereBetween(
                'usage_date',
                [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ]
            )
            ->groupBy('customer_id')
            ->pluck(
                'total_usage',
                'customer_id'
            );

        $previousUsage = UsageEvent::query()
            ->selectRaw(
                'customer_id, SUM(units) as total_usage'
            )
            ->whereBetween(
                'usage_date',
                [
                    $previousMonthStart->toDateString(),
                    $previousMonthEnd->toDateString(),
                ]
            )
            ->groupBy('customer_id')
            ->pluck(
                'total_usage',
                'customer_id'
            );

        $customerIds = $currentUsage
            ->keys()
            ->merge($previousUsage->keys())
            ->unique();

        $customers = Customer::whereIn(
            'id',
            $customerIds
        )
        ->get()
        ->keyBy('id');

        $result = collect();

        foreach ($customerIds as $customerId) {

            $current = (int) (
                $currentUsage[$customerId] ?? 0
            );

            $previous = (int) (
                $previousUsage[$customerId] ?? 0
            );

            if ($previous <= 0) {
                continue;
            }

            $dropPercentage =
                (
                    ($previous - $current)
                    / $previous
                )
                * 100;

            if ($dropPercentage > 50) {

                $customer = $customers->get(
                    $customerId
                );

                if (!$customer) {
                    continue;
                }

                $result->push([
                    'customer' =>
                        $customer,

                    'current_usage' =>
                        $current,

                    'previous_usage' =>
                        $previous,

                    'drop_percentage' =>
                        round(
                            $dropPercentage,
                            2
                        ),
                ]);
            }
        }

        return $result
            ->sortByDesc(
                'drop_percentage'
            )
            ->values();
    }


    /**
     * Existing global projected overage.
     */
    private function getProjectedOverage(
        Carbon $monthStart,
        Carbon $monthEnd
    ): float {
        $subscriptions = Subscription::with([
            'customer',
            'plan',
        ])
        ->where('status', 'active')
        ->get();

        $projectedRevenue = 0;

        foreach ($subscriptions as $subscription) {

            if (!$subscription->plan) {
                continue;
            }

            $subscriptionStart = Carbon::parse(
                $subscription->starts_at
            )->startOfDay();

            $effectiveStart =
                $monthStart->greaterThan(
                    $subscriptionStart
                )
                    ? $monthStart->copy()
                    : $subscriptionStart->copy();

            $effectiveEnd =
                $monthEnd->copy();

            if (
                $effectiveEnd->lt(
                    $effectiveStart
                )
            ) {
                continue;
            }

            $changes = SubscriptionChange::with([
                'oldPlan',
                'newPlan',
            ])
            ->where(
                'subscription_id',
                $subscription->id
            )
            ->where(
                'effective_at',
                '>',
                $effectiveStart
            )
            ->where(
                'effective_at',
                '<=',
                $effectiveEnd
            )
            ->orderBy('effective_at')
            ->get();

            $currentPlan =
                $subscription->plan;

            $previousChange =
                SubscriptionChange::with([
                    'newPlan',
                ])
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->where(
                    'effective_at',
                    '<=',
                    $effectiveStart
                )
                ->orderByDesc(
                    'effective_at'
                )
                ->first();

            if ($previousChange) {

                $currentPlan =
                    $previousChange->newPlan;

            } elseif ($changes->isNotEmpty()) {

                $currentPlan =
                    $changes->first()->oldPlan;
            }

            $segments = [];

            $segmentStart =
                $effectiveStart->copy();

            foreach ($changes as $change) {

                $changeAt =
                    Carbon::parse(
                        $change->effective_at
                    );

                if (
                    $changeAt->gt(
                        $segmentStart
                    )
                ) {

                    $segments[] = [
                        'plan' =>
                            $currentPlan,

                        'start' =>
                            $segmentStart->copy(),

                        'end' =>
                            $changeAt
                                ->copy()
                                ->subSecond(),
                    ];
                }

                $currentPlan =
                    $change->newPlan;

                $segmentStart =
                    $changeAt->copy();
            }

            if (
                $segmentStart->lte(
                    $effectiveEnd
                )
            ) {

                $segments[] = [
                    'plan' =>
                        $currentPlan,

                    'start' =>
                        $segmentStart->copy(),

                    'end' =>
                        $effectiveEnd->copy(),
                ];
            }

            $billingDays =
                $effectiveStart
                    ->copy()
                    ->startOfDay()
                    ->diffInDays(
                        $effectiveEnd
                            ->copy()
                            ->startOfDay()
                    )
                + 1;

            foreach ($segments as $segment) {

                $plan =
                    $segment['plan'];

                if (!$plan) {
                    continue;
                }

                $segmentStart =
                    $segment['start'];

                $segmentEnd =
                    $segment['end'];

                $segmentDays =
                    $segmentStart
                        ->copy()
                        ->startOfDay()
                        ->diffInDays(
                            $segmentEnd
                                ->copy()
                                ->startOfDay()
                        )
                    + 1;

                $units = UsageEvent::where(
                    'customer_id',
                    $subscription->customer_id
                )
                ->where(
                    'merchant_id',
                    $subscription->customer->merchant_id
                )
                ->whereBetween(
                    'usage_date',
                    [
                        $segmentStart->toDateString(),
                        $segmentEnd->toDateString(),
                    ]
                )
                ->sum('units');

                $includedUnits =
                    (int) floor(
                        $plan->included_units
                        *
                        (
                            $segmentDays
                            /
                            $billingDays
                        )
                    );

                $overageUnits = max(
                    0,
                    (int) $units
                    - $includedUnits
                );

                $projectedRevenue +=
                    $overageUnits
                    *
                    (float) $plan->overage_rate;
            }
        }

        return round(
            $projectedRevenue,
            2
        );
    }
}

