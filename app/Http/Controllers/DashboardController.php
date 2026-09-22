<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        $customerCount = Customer::count();

        $totalUsage = 0;

        $projectedOverage = 0;

        $topCustomers = collect();

        $usageDropCustomers = collect();

        return view('dashboard.index', [
            'customerCount' => $customerCount,
            'totalUsage' => $totalUsage,
            'projectedOverage' => $projectedOverage,
            'topCustomers' => $topCustomers,
            'usageDropCustomers' => $usageDropCustomers,
        ]);
    }
}