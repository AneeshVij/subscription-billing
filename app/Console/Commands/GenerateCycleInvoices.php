<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCycleInvoicesJob;
use Illuminate\Console\Command;

class GenerateCycleInvoices extends Command
{
    protected $signature = 'billing:generate-invoices
                            {billingStart : Billing period start date}
                            {billingEnd : Billing period end date}';

    protected $description =
        'Queue invoice generation for the specified billing period';

    public function handle(): int
    {
        $billingStart = $this->argument(
            'billingStart'
        );

        $billingEnd = $this->argument(
            'billingEnd'
        );

        if (
            !strtotime($billingStart)
            ||
            !strtotime($billingEnd)
        ) {
            $this->error(
                'Billing dates must be valid dates.'
            );

            return self::FAILURE;
        }

        if (
            strtotime($billingEnd)
            <
            strtotime($billingStart)
        ) {
            $this->error(
                'Billing end date cannot be before billing start date.'
            );

            return self::FAILURE;
        }

        GenerateCycleInvoicesJob::dispatch(
            $billingStart,
            $billingEnd
        );

        $this->info(
            'Cycle invoice generation job queued successfully.'
        );

        $this->line(
            "Billing period: {$billingStart} to {$billingEnd}"
        );

        return self::SUCCESS;
    }
}