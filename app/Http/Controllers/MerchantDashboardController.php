<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\DashboardService;

class MerchantDashboardController extends Controller
{
    public function show(
        Merchant $merchant,
        DashboardService $dashboardService
    ) {
        $data = $dashboardService->getMerchantDashboardData(
            $merchant->id
        );

        return response()->json([
            'merchant' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
            ],

            'data' => $data,
        ]);
    }
}