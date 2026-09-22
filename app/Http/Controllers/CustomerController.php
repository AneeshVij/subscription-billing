<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with([
            'merchant',
            'subscriptions.plan',
        ])
        ->latest()
        ->get();

        $merchants = Merchant::orderBy('name')->get();

        return view('customers.index', compact(
            'customers',
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

            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        Customer::create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }
}