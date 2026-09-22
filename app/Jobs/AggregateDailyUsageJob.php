<?php

namespace App\Jobs;

use App\Models\DailyUsage;
use App\Models\UsageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateDailyUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $merchantId,
        public string $usageDate
    ) {
    }

    public function handle(): void
    {
        /*
         * Rebuild the aggregate for this merchant/date.
         *
         * This makes the job safe to run again.
         */
        DailyUsage::where(
            'merchant_id',
            $this->merchantId
        )
        ->whereDate(
            'usage_date',
            $this->usageDate
        )
        ->delete();

        UsageEvent::where(
            'merchant_id',
            $this->merchantId
        )
        ->whereDate(
            'usage_date',
            $this->usageDate
        )
        ->select([
            'id',
            'merchant_id',
            'customer_id',
            'usage_date',
            'units',
        ])
        ->chunkById(
            1000,
            function ($events) {

                $grouped = $events->groupBy(
                    function ($event) {
                        return $event->customer_id
                            . '|'
                            . $event->usage_date->format('Y-m-d');
                    }
                );

                foreach ($grouped as $customerEvents) {

                    $first = $customerEvents->first();

                    $units = $customerEvents->sum('units');

                    $dailyUsage = DailyUsage::firstOrCreate(
                        [
                            'merchant_id' =>
                                $first->merchant_id,

                            'customer_id' =>
                                $first->customer_id,

                            'usage_date' =>
                                $first->usage_date,
                        ],
                        [
                            'total_units' => 0,
                        ]
                    );

                    $dailyUsage->increment(
                        'total_units',
                        $units
                    );
                }
            }
        );
    }
}

