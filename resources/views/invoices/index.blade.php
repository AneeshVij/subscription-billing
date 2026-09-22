@extends('layouts.app')

@section('title', 'Invoices')

@section('content')

<div class="page-header">
    <h1>Invoices</h1>
    <p>
        Generate and view subscription invoices.
    </p>
</div>

@if(session('success'))
    <div class="alert success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert error">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


{{-- Generate Invoice --}}
<div class="card">

    <h2>Generate Invoice</h2>

    <p>
        Generate an invoice for an active subscription.
    </p>

    <form
        method="POST"
        action="{{ route('invoices.store') }}"
    >

        @csrf

        <div class="form-grid">

            <div>
                <label for="subscription_id">
                    Subscription
                </label>

                <select
                    name="subscription_id"
                    id="subscription_id"
                    required
                >

                    <option value="">
                        Select Subscription
                    </option>

                    @foreach($subscriptions as $subscription)

                        <option
                            value="{{ $subscription->id }}"
                            {{ old('subscription_id') == $subscription->id ? 'selected' : '' }}
                        >

                            {{ $subscription->customer->name ?? 'N/A' }}

                            -

                            {{ $subscription->plan->name ?? 'N/A' }}

                            -

                            {{ $subscription->customer->merchant->name ?? 'N/A' }}

                        </option>

                    @endforeach

                </select>
            </div>


            <div>

                <label for="billing_start">
                    Billing Start
                </label>

                <input
                    type="date"
                    name="billing_start"
                    id="billing_start"
                    value="{{ old('billing_start', now()->startOfMonth()->format('Y-m-d')) }}"
                    required
                >

            </div>


            <div>

                <label for="billing_end">
                    Billing End
                </label>

                <input
                    type="date"
                    name="billing_end"
                    id="billing_end"
                    value="{{ old('billing_end', now()->endOfMonth()->format('Y-m-d')) }}"
                    required
                >

            </div>

        </div>

        <br>

        <button type="submit">
            Generate Invoice
        </button>

    </form>

</div>


{{-- Invoice List --}}
<div class="card">

    <div class="section-header">

        <div>
            <h2>Invoice List</h2>

            <p>
                Generated subscription invoices.
            </p>
        </div>

    </div>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Merchant</th>
                    <th>Subscription</th>
                    <th>Billing Period</th>
                    <th>Base Amount</th>
                    <th>Overage Amount</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>

            </thead>

            <tbody>

                @forelse($invoices as $invoice)

                    <tr>

                        <td>
                            #{{ $invoice->id }}
                        </td>

                        <td>
                            <strong>
                                {{ $invoice->customer->name ?? 'N/A' }}
                            </strong>
                        </td>

                        <td>
                            {{ $invoice->merchant->name ?? 'N/A' }}
                        </td>

                        <td>

                            #{{ $invoice->subscription_id }}

                            <br>

                            <small>
                                {{ $invoice->subscription->plan->name ?? 'N/A' }}
                            </small>

                        </td>

                        <td>

                            {{ $invoice->billing_start?->format('d M Y') }}

                            -

                            {{ $invoice->billing_end?->format('d M Y') }}

                        </td>

                        <td>

                            ₹{{ number_format(
                                (float) $invoice->base_amount,
                                2
                            ) }}

                        </td>

                        <td>

                            ₹{{ number_format(
                                (float) $invoice->overage_amount,
                                2
                            ) }}

                        </td>

                        <td>

                            <strong>
                                ₹{{ number_format(
                                    (float) $invoice->total_amount,
                                    2
                                ) }}
                            </strong>

                        </td>

                        <td>

                            @if($invoice->status === 'generated')

                                <span class="badge success">
                                    Generated
                                </span>

                            @elseif($invoice->status === 'draft')

                                <span class="badge">
                                    Draft
                                </span>

                            @else

                                <span class="badge">
                                    {{ ucfirst($invoice->status) }}
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="9"
                            style="text-align: center;"
                        >
                            No invoices found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- Invoice Items --}}
<div class="card">
    <div class="section-header">
        <div>
            <h2>Invoice Items</h2>
            <p>Usage and billing details for generated invoices.</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Plan</th>
                    <th>Description</th>
                    <th>Usage Units</th>
                    <th>Included Units</th>
                    <th>Overage Units</th>
                    <th>Overage Rate</th>
                    <th>Amount</th>
                    <th>Period</th>
                </tr>
            </thead>

            <tbody>
                @forelse($invoices as $invoice)

                    @foreach($invoice->items as $item)

                        @php
                            $overageUnits = max(
                                0,
                                (int) $item->units -
                                (int) $item->average_units
                            );
                        @endphp

                        <tr>
                            <td>
                                #{{ $invoice->id }}
                            </td>

                            <td>
                                {{ $item->plan->name ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $item->description }}
                            </td>

                            <td>
                                {{ number_format($item->units) }}
                            </td>

                            <td>
                                {{ number_format($item->average_units) }}
                            </td>

                            <td>
                                {{ number_format($overageUnits) }}
                            </td>

                            <td>
                                ₹{{ number_format((float) $item->rate, 4) }}
                            </td>

                            <td>
                                <strong>
                                    ₹{{ number_format((float) $item->amount, 2) }}
                                </strong>
                            </td>

                            <td>
                                {{ $item->period_start?->format('d M Y') }}
                                -
                                {{ $item->period_end?->format('d M Y') }}
                            </td>
                        </tr>

                    @endforeach

                @empty

                    <tr>
                        <td colspan="9" style="text-align: center;">
                            No invoice items found.
                        </td>
                    </tr>

                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection