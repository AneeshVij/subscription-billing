<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::with('merchant')
            ->withCount('subscriptions')
            ->latest()
            ->get();

        $merchants = Merchant::orderBy('name')->get();

        return view('plans.index', compact(
            'plans',
            'merchants'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => [
                'required',
                'integer',
                'exists:merchants,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'base_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'billing_cycle' => [
                'required',
                'in:monthly,yearly',
            ],

            'included_units' => [
                'required',
                'integer',
                'min:0',
            ],

            'overage_rate' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $plan = Plan::create($validated);

        /*
         * If this plan already existed and was being updated,
         * we would invalidate its pricing cache here.
         *
         * For creation there is no previous cache entry.
         */

        return redirect()
            ->route('plans.index')
            ->with(
                'success',
                'Plan created successfully.'
            );
    }
}