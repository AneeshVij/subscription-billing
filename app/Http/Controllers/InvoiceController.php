<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with([
            'merchant',
            'customer',
            'subscription.plan',
            'items.plan',
        ])
        ->latest()
        ->get();

        $subscriptions = Subscription::with([
            'customer',
            'plan',
        ])
        ->where('status', 'active')
        ->get();

        $merchants = Merchant::all();

        $customers = Customer::with('merchant')->get();

        return view('invoices.index', compact(
            'invoices',
            'subscriptions',
            'merchants',
            'customers'
        ));
    }

    public function store(
        Request $request,
        \App\Services\BillingService $billingService
    ) {
        $validated = $request->validate([
            'subscription_id' => [
                'required',
                'exists:subscriptions,id',
            ],

            'billing_start' => [
                'required',
                'date',
            ],

            'billing_end' => [
                'required',
                'date',
                'after_or_equal:billing_start',
            ],
        ]);

        $subscription = Subscription::findOrFail(
            $validated['subscription_id']
        );

        $billingService->generateInvoice(
            $subscription,
            $validated['billing_start'],
            $validated['billing_end']
        );

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                'Invoice generated successfully.'
            );
    }
}