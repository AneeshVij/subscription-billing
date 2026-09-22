<?php

namespace App\Http\Controllers;

use App\Jobs\AggregateDailyUsageJob;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\UsageEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    /**
     * Display usage page.
     */
    public function index()
    {
        $usageEvents = UsageEvent::with([
            'merchant',
            'customer'
        ])
        ->latest('usage_date')
        ->get();

        $merchants = Merchant::all();

        $customers = Customer::with('merchant')->get();

        return view('usage.index', compact(
            'usageEvents',
            'merchants',
            'customers'
        ));
    }

    /**
     * Record a usage event.
     *
     * Supports:
     * POST /usage
     * POST /api/usage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => [
                'required',
                'integer',
                'exists:merchants,id',
            ],

            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],

            'usage_date' => [
                'required',
                'date',
            ],

            'units' => [
                'required',
                'integer',
                'min:1',
            ],

            'metadata' => [
                'nullable',
            ],
        ]);

        /*
         * Verify that the customer belongs
         * to the selected merchant.
         */
        $customer = Customer::findOrFail(
            $validated['customer_id']
        );

        if (
            (int) $customer->merchant_id
            !==
            (int) $validated['merchant_id']
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' =>
                        'Customer does not belong to the specified merchant.',
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'customer_id' =>
                        'Customer does not belong to the specified merchant.',
                ]);
        }

        /*
         * Convert metadata JSON string
         * into an array when required.
         */
        if (
            isset($validated['metadata'])
            &&
            is_string($validated['metadata'])
            &&
            trim($validated['metadata']) !== ''
        ) {
            $decodedMetadata = json_decode(
                $validated['metadata'],
                true
            );

            if (
                json_last_error() !== JSON_ERROR_NONE
            ) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' =>
                            'Metadata must contain valid JSON.',
                    ], 422);
                }

                return back()
                    ->withInput()
                    ->withErrors([
                        'metadata' =>
                            'Metadata must contain valid JSON.',
                    ]);
            }

            $validated['metadata'] =
                $decodedMetadata;
        }

        /*
         * Idempotency lookup.
         *
         * Idempotency is scoped to the merchant.
         */
        $existingEvent = UsageEvent::where(
            'merchant_id',
            $validated['merchant_id']
        )
        ->where(
            'idempotency_key',
            $validated['idempotency_key']
        )
        ->first();

        if ($existingEvent) {
            return $this->idempotencyResponse(
                $request,
                $existingEvent
            );
        }

        /*
         * Create the usage event.
         *
         * The database composite UNIQUE constraint
         * is the final protection against concurrent
         * duplicate requests.
         */
        try {

            $usageEvent = UsageEvent::create([
                'merchant_id' =>
                    $validated['merchant_id'],

                'customer_id' =>
                    $validated['customer_id'],

                'idempotency_key' =>
                    $validated['idempotency_key'],

                'usage_date' =>
                    $validated['usage_date'],

                'units' =>
                    $validated['units'],

                'metadata' =>
                    $validated['metadata'] ?? null,
            ]);

            /*
             * Queue daily usage aggregation.
             *
             * Usage is stored immediately and the
             * aggregation is processed asynchronously.
             */
            AggregateDailyUsageJob::dispatch(
                (int) $validated['merchant_id'],
                $validated['usage_date']
            );

        } catch (QueryException $exception) {

            /*
             * Another request may have inserted
             * the same merchant + idempotency key
             * between our lookup and create.
             *
             * Fetch the existing event instead.
             */
            $usageEvent = UsageEvent::where(
                'merchant_id',
                $validated['merchant_id']
            )
            ->where(
                'idempotency_key',
                $validated['idempotency_key']
            )
            ->first();

            if (!$usageEvent) {
                throw $exception;
            }

            return $this->idempotencyResponse(
                $request,
                $usageEvent
            );
        }

        /*
         * Successful creation.
         */
        if ($request->expectsJson()) {
            return response()->json([
                'message' =>
                    'Usage recorded successfully.',

                'data' =>
                    $usageEvent,
            ], 201);
        }

        return redirect()
            ->route('usage.index')
            ->with(
                'success',
                'Usage recorded successfully.'
            );
    }

    /**
     * Return an idempotency response.
     */
    private function idempotencyResponse(
        Request $request,
        UsageEvent $usageEvent
    ) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' =>
                    'Usage event already exists.',

                'data' =>
                    $usageEvent,
            ], 200);
        }

        return redirect()
            ->route('usage.index')
            ->with(
                'success',
                'Usage event already exists.'
            );
    }
}