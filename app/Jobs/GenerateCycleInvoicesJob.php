<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCycleInvoicesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $billingStart,
        public string $billingEnd
    ) {
    }

    public function handle(
        BillingService $billingService
    ): void {
        Subscription::query()
            ->where('status', 'active')
            ->whereDate(
                'starts_at',
                '<=',
                $this->billingEnd
            )
            ->where(function ($query) {
                $query
                    ->whereNull('ends_at')
                    ->orWhereDate(
                        'ends_at',
                        '>=',
                        $this->billingStart
                    );
            })
            ->orderBy('id')
            ->chunkById(
                100,
                function ($subscriptions) use (
                    $billingService
                ) {
                    foreach ($subscriptions as $subscription) {

                        $billingService->generateInvoice(
                            $subscription,
                            $this->billingStart,
                            $this->billingEnd
                        );
                    }
                }
            );
    }
}