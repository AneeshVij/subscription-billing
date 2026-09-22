<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with([
            'customer',
            'plan',
        ])->latest()->get();

        $customers = Customer::orderBy('name')->get();

        $plans = Plan::orderBy('name')->get();

        return view('subscriptions.index', compact(
            'subscriptions',
            'customers',
            'plans'
        ));
    }

    public function store()
    {
        request()->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'starts_at' => ['required', 'date'],
        ]);

        $customer = Customer::findOrFail(
            request('customer_id')
        );

        $plan = Plan::findOrFail(
            request('plan_id')
        );

        if ($customer->merchant_id !== $plan->merchant_id) {
            return back()->withErrors([
                'plan_id' => 'Customer and plan must belong to the same merchant.',
            ]);
        }

        DB::transaction(function () {

            Subscription::where(
                'customer_id',
                request('customer_id')
            )
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'ends_at' => now(),
            ]);

            Subscription::create([
                'customer_id' => request('customer_id'),
                'plan_id' => request('plan_id'),
                'starts_at' => request('starts_at'),
                'status' => 'active',
            ]);
        });

        return back()->with(
            'success',
            'Subscription created successfully.'
        );
    }
}