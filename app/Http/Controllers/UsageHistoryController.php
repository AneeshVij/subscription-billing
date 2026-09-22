<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\UsageEvent;
use Illuminate\Http\Request;

class UsageHistoryController extends Controller
{
    public function index(Request $request)
    {
        $merchants = Merchant::orderBy('name')->get();

        $customers = Customer::with('merchant')
            ->orderBy('name')
            ->get();

        $query = UsageEvent::with([
            'customer',
            'merchant',
        ])->latest();

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('usage_date')) {
            $query->whereDate('usage_date', $request->usage_date);
        }

        $usageEvents = $query->get();

        return view('usage.history', compact(
            'merchants',
            'customers',
            'usageEvents'
        ));
    }
}
