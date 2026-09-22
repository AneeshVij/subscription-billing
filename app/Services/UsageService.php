<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\UsageEvent;
use Illuminate\Support\Facades\DB;

class UsageService
{
    public function record(array $data): UsageEvent
    {
        $customer = Customer::findOrFail(
            $data['customer_id']
        );

        if ($customer->merchant_id !== (int) $data['merchant_id']) {
            throw new \InvalidArgumentException(
                'Customer does not belong to this merchant.'
            );
        }

        $existing = UsageEvent::where(
            'idempotency_key',
            $data['idempotency_key']
        )->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($data) {

            $usageEvent = UsageEvent::create([
                'merchant_id' => $data['merchant_id'],
                'customer_id' => $data['customer_id'],
                'idempotency_key' => $data['idempotency_key'],
                'usage_date' => $data['usage_date'],
                'units' => $data['units'],
                'metadata' => $data['metadata'] ?? null,
            ]);

            $dailyUsage = DailyUsage::firstOrCreate(
                [
                    'customer_id' => $data['customer_id'],
                    'usage_date' => $data['usage_date'],
                ],
                [
                    'merchant_id' => $data['merchant_id'],
                    'total_units' => 0,
                ]
            );

            $dailyUsage->increment(
                'total_units',
                $data['units']
            );

            return $usageEvent;
        });
    }
}