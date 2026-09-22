<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingService
{
    public function generateInvoice(
        Subscription $subscription,
        string $billingStart,
        string $billingEnd
    ): Invoice {
        return DB::transaction(function () use (
            $subscription,
            $billingStart,
            $billingEnd
        ) {

            /*
             * ---------------------------------------------------------
             * 1. Billing period
             * ---------------------------------------------------------
             */

            $start = Carbon::parse($billingStart)->startOfDay();
            $end = Carbon::parse($billingEnd)->endOfDay();

            if ($end->lt($start)) {
                throw new RuntimeException(
                    'Billing end date cannot be before billing start date.'
                );
            }

            $subscription->load([
                'customer',
                'plan',
            ]);

            /*
             * ---------------------------------------------------------
             * 2. Subscription period
             * ---------------------------------------------------------
             */

            $subscriptionStart = Carbon::parse(
                $subscription->starts_at
            )->startOfDay();

            $subscriptionEnd = $subscription->ends_at
                ? Carbon::parse($subscription->ends_at)->endOfDay()
                : null;

            /*
             * If the entire billing period is before the subscription,
             * there is nothing to bill.
             */
            if ($end->lt($subscriptionStart)) {
                throw new RuntimeException(
                    'Billing period is completely before the subscription start date.'
                );
            }

            /*
             * If subscription has ended before the billing period,
             * there is nothing to bill.
             */
            if (
                $subscriptionEnd &&
                $start->gt($subscriptionEnd)
            ) {
                throw new RuntimeException(
                    'Billing period is completely after the subscription end date.'
                );
            }

            /*
             * ---------------------------------------------------------
             * 3. Effective billing period
             * ---------------------------------------------------------
             *
             * Example:
             *
             * Billing period:
             * 01 Sep -> 30 Sep
             *
             * Subscription:
             * 15 Sep -> active
             *
             * Effective billing:
             * 15 Sep -> 30 Sep
             */

            $effectiveStart = $start->greaterThan($subscriptionStart)
                ? $start->copy()
                : $subscriptionStart->copy();

            $effectiveEnd = $end->copy();

            if (
                $subscriptionEnd &&
                $effectiveEnd->greaterThan($subscriptionEnd)
            ) {
                $effectiveEnd = $subscriptionEnd->copy();
            }

            if ($effectiveEnd->lt($effectiveStart)) {
                throw new RuntimeException(
                    'No active subscription period exists inside the billing period.'
                );
            }

            /*
             * ---------------------------------------------------------
             * 4. Prevent duplicate invoices
             * ---------------------------------------------------------
             *
             * The invoice still represents the requested billing
             * period, not only the effective subscription period.
             */

            $existingInvoice = Invoice::where(
                'subscription_id',
                $subscription->id
            )
            ->whereDate(
                'billing_start',
                $start->toDateString()
            )
            ->whereDate(
                'billing_end',
                $end->toDateString()
            )
            ->first();

            if ($existingInvoice) {
                return $existingInvoice->load('items');
            }

            /*
             * ---------------------------------------------------------
             * 5. Find plan changes
             * ---------------------------------------------------------
             *
             * Only changes that happen during the effective
             * subscription period are relevant.
             */

            $changes = SubscriptionChange::with([
                'oldPlan',
                'newPlan',
            ])
            ->where('subscription_id', $subscription->id)
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

            /*
             * ---------------------------------------------------------
             * 6. Determine plan at effective billing start
             * ---------------------------------------------------------
             */

            $currentPlan = $subscription->plan;

            /*
             * Since subscription.plan_id contains the current/latest
             * plan, use the most recent change before the effective
             * billing start to determine the correct starting plan.
             */

            $previousChange = SubscriptionChange::with([
                'newPlan',
            ])
            ->where('subscription_id', $subscription->id)
            ->where(
                'effective_at',
                '<=',
                $effectiveStart
            )
            ->orderByDesc('effective_at')
            ->first();

            if ($previousChange) {
                $currentPlan = $previousChange->newPlan;
            } elseif ($changes->isNotEmpty()) {
                /*
                 * If there is a change later in the billing period,
                 * the old plan from the first change was active at
                 * the beginning.
                 */
                $currentPlan = $changes->first()->oldPlan;
            }

            /*
             * ---------------------------------------------------------
             * 7. Build billing segments
             * ---------------------------------------------------------
             */

            $segments = [];

            $segmentStart = $effectiveStart->copy();

            foreach ($changes as $change) {

                $changeAt = Carbon::parse(
                    $change->effective_at
                );

                /*
                 * Old plan segment.
                 */
                if ($changeAt->gt($segmentStart)) {

                    $segments[] = [
                        'plan' => $currentPlan,
                        'start' => $segmentStart->copy(),
                        'end' => $changeAt->copy()->subSecond(),
                    ];
                }

                /*
                 * New plan becomes active at change time.
                 */
                $currentPlan = $change->newPlan;

                $segmentStart = $changeAt->copy();
            }

            /*
             * Final segment.
             */
            if ($segmentStart->lte($effectiveEnd)) {

                $segments[] = [
                    'plan' => $currentPlan,
                    'start' => $segmentStart->copy(),
                    'end' => $effectiveEnd->copy(),
                ];
            }

            /*
             * ---------------------------------------------------------
             * 8. Billing cycle days
             * ---------------------------------------------------------
             *
             * Important:
             *
             * Proration is based on the REQUESTED billing period.
             *
             * Example:
             *
             * 01 Sep -> 30 Sep = 30 days
             *
             * Subscription starts 15 Sep.
             *
             * Effective subscription period:
             * 15 Sep -> 30 Sep = 16 days
             *
             * Therefore:
             *
             * Base price × 16 / 30
             */

            $billingDays =
                $start->copy()->startOfDay()
                ->diffInDays(
                    $end->copy()->startOfDay()
                ) + 1;

            /*
             * ---------------------------------------------------------
             * 9. Create invoice
             * ---------------------------------------------------------
             */

            $invoice = Invoice::create([
                'merchant_id' =>
                    $subscription->customer->merchant_id,

                'customer_id' =>
                    $subscription->customer_id,

                'subscription_id' =>
                    $subscription->id,

                'billing_start' =>
                    $start->toDateString(),

                'billing_end' =>
                    $end->toDateString(),

                'base_amount' => 0,

                'overage_amount' => 0,

                'total_amount' => 0,

                'status' => 'generated',
            ]);

            $totalBaseAmount = 0;
            $totalOverageAmount = 0;

            /*
             * ---------------------------------------------------------
             * 10. Calculate every billing segment
             * ---------------------------------------------------------
             */

            foreach ($segments as $segment) {

                $plan = $segment['plan'];

                $periodStart = $segment['start'];
                $periodEnd = $segment['end'];

                /*
                 * Number of calendar days in this segment.
                 */

                $segmentDays =
                    $periodStart->copy()->startOfDay()
                    ->diffInDays(
                        $periodEnd->copy()->startOfDay()
                    ) + 1;

                /*
                 * -----------------------------------------------------
                 * 10A. Base price proration
                 * -----------------------------------------------------
                 */

                $baseAmount =
                    ((float) $plan->base_price)
                    *
                    ($segmentDays / $billingDays);

                /*
                 * -----------------------------------------------------
                 * 10B. Usage for this plan segment
                 * -----------------------------------------------------
                 */

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
                        $periodStart->toDateString(),
                        $periodEnd->toDateString(),
                    ]
                )
                ->sum('units');

                /*
                 * -----------------------------------------------------
                 * 10C. Prorated included units
                 * -----------------------------------------------------
                 *
                 * Example:
                 *
                 * Full allowance = 1,000
                 * Plan active = 15 days
                 * Billing period = 30 days
                 *
                 * 1,000 × 15 / 30 = 500
                 */

                $fullIncludedUnits =
                    (int) $plan->included_units;

                $proratedIncludedUnits =
                    $fullIncludedUnits
                    *
                    ($segmentDays / $billingDays);

                $includedUnits =
                    (int) floor(
                        $proratedIncludedUnits
                    );

                /*
                 * -----------------------------------------------------
                 * 10D. Overage units
                 * -----------------------------------------------------
                 */

                $overageUnits = max(
                    0,
                    (int) $units - $includedUnits
                );

                /*
                 * -----------------------------------------------------
                 * 10E. Overage amount
                 * -----------------------------------------------------
                 */

                $overageAmount =
                    $overageUnits
                    *
                    (float) $plan->overage_rate;

                /*
                 * -----------------------------------------------------
                 * 10F. Segment total
                 * -----------------------------------------------------
                 */

                $segmentTotal =
                    $baseAmount
                    +
                    $overageAmount;

                /*
                 * -----------------------------------------------------
                 * 10G. Save invoice item
                 * -----------------------------------------------------
                 */

                $invoice->items()->create([
                    'plan_id' =>
                        $plan->id,

                    'description' =>
                        'Subscription - ' . $plan->name,

                    'units' =>
                        $units,

                    /*
                     * Existing column name is average_units.
                     *
                     * We use it to store the prorated included
                     * units for this segment.
                     */
                    'average_units' =>
                        $includedUnits,

                    'rate' =>
                        $plan->overage_rate,

                    'amount' =>
                        round(
                            $segmentTotal,
                            4
                        ),

                    'period_start' =>
                        $periodStart->toDateString(),

                    'period_end' =>
                        $periodEnd->toDateString(),
                ]);

                $totalBaseAmount += $baseAmount;

                $totalOverageAmount += $overageAmount;
            }

            /*
             * ---------------------------------------------------------
             * 11. Final invoice total
             * ---------------------------------------------------------
             */

            $totalAmount =
                $totalBaseAmount
                +
                $totalOverageAmount;

            $invoice->update([
                'base_amount' =>
                    round(
                        $totalBaseAmount,
                        2
                    ),

                'overage_amount' =>
                    round(
                        $totalOverageAmount,
                        2
                    ),

                'total_amount' =>
                    round(
                        $totalAmount,
                        2
                    ),
            ]);

            return $invoice->load('items');
        });
    }
}