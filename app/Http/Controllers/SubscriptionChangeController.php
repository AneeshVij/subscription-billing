<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionChangeController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with([
            'customer',
            'plan',
        ])
        ->where('status', 'active')
        ->get();

        $plans = Plan::with('merchant')->get();

        $changes = SubscriptionChange::with([
            'subscription.customer',
            'subscription.plan',
            'oldPlan',
            'newPlan',
        ])
        ->latest('effective_at')
        ->latest('id')
        ->get();

        return view('subscription-changes.index', compact(
            'subscriptions',
            'plans',
            'changes'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => [
                'required',
                'exists:subscriptions,id',
            ],

            'new_plan_id' => [
                'required',
                'exists:plans,id',
            ],

            'effective_at' => [
                'required',
                'date',
            ],
        ]);

        DB::transaction(function () use ($validated) {

            $subscription = Subscription::with([
                'customer',
                'plan',
            ])
            ->lockForUpdate()
            ->findOrFail($validated['subscription_id']);

            if ($subscription->status !== 'active') {
                throw ValidationException::withMessages([
                    'subscription_id' =>
                        'Only active subscriptions can be changed.',
                ]);
            }

            $newPlan = Plan::findOrFail(
                $validated['new_plan_id']
            );

            // Make sure the new plan belongs to the same merchant
            // as the customer's subscription.
            if (
                (int) $newPlan->merchant_id !==
                (int) $subscription->customer->merchant_id
            ) {
                throw ValidationException::withMessages([
                    'new_plan_id' =>
                        'The selected plan does not belong to the customer merchant.',
                ]);
            }

            // Prevent selecting the current plan.
            if (
                (int) $newPlan->id ===
                (int) $subscription->plan_id
            ) {
                throw ValidationException::withMessages([
                    'new_plan_id' =>
                        'The new plan must be different from the current plan.',
                ]);
            }

            // Effective date cannot be before subscription start.
            if (
                $subscription->starts_at &&
                $validated['effective_at'] <
                $subscription->starts_at
            ) {
                throw ValidationException::withMessages([
                    'effective_at' =>
                        'Effective date cannot be before the subscription start date.',
                ]);
            }

            // Get the previous plan change.
            $lastChange = SubscriptionChange::where(
                'subscription_id',
                $subscription->id
            )
            ->latest('effective_at')
            ->first();

            // Keep plan changes in chronological order.
            if (
                $lastChange &&
                $validated['effective_at'] <=
                $lastChange->effective_at
            ) {
                throw ValidationException::withMessages([
                    'effective_at' =>
                        'Effective date must be after the previous plan change.',
                ]);
            }

            $oldPlanId = $subscription->plan_id;

            // Store plan change history.
            SubscriptionChange::create([
                'subscription_id' => $subscription->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlan->id,
                'effective_at' => $validated['effective_at'],
            ]);

            // Update current subscription plan.
            $subscription->update([
                'plan_id' => $newPlan->id,
            ]);
        });

        return redirect()
            ->route('subscription-changes.index')
            ->with(
                'success',
                'Subscription plan changed successfully.'
            );
    }
}