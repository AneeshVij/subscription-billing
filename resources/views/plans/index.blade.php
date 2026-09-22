@extends('layouts.app')

@section('title', 'Plans')

@section('content')

<div class="page-header">
    <h1>Plans</h1>
    <p>Manage merchant subscription plans and pricing.</p>
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


{{-- Add Plan --}}

<div class="card">

    <h2>Add Plan</h2>

    <form method="POST" action="{{ route('plans.store') }}">

        @csrf

        <div class="form-grid">

            <div>
                <label for="merchant_id">
                    Merchant
                </label>

                <select
                    name="merchant_id"
                    id="merchant_id"
                    required
                >

                    <option value="">
                        Select Merchant
                    </option>

                    @foreach($merchants as $merchant)

                        <option
                            value="{{ $merchant->id }}"
                            {{ old('merchant_id') == $merchant->id ? 'selected' : '' }}
                        >
                            {{ $merchant->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div>
                <label for="name">
                    Plan Name
                </label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    placeholder="Example: Professional"
                    required
                >
            </div>


            <div>
                <label for="base_price">
                    Base Price
                </label>

                <input
                    type="number"
                    name="base_price"
                    id="base_price"
                    value="{{ old('base_price') }}"
                    placeholder="1000.00"
                    step="0.01"
                    min="0"
                    required
                >
            </div>


            <div>
                <label for="billing_cycle">
                    Billing Cycle
                </label>

                <select
                    name="billing_cycle"
                    id="billing_cycle"
                    required
                >

                    <option value="">
                        Select Cycle
                    </option>

                    <option
                        value="monthly"
                        {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}
                    >
                        Monthly
                    </option>

                    <option
                        value="yearly"
                        {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}
                    >
                        Yearly
                    </option>

                </select>

            </div>


            <div>
                <label for="included_units">
                    Included Units
                </label>

                <input
                    type="number"
                    name="included_units"
                    id="included_units"
                    value="{{ old('included_units') }}"
                    placeholder="10000"
                    min="0"
                    required
                >
            </div>


            <div>
                <label for="overage_rate">
                    Overage Rate / Unit
                </label>

                <input
                    type="number"
                    name="overage_rate"
                    id="overage_rate"
                    value="{{ old('overage_rate') }}"
                    placeholder="0.08"
                    step="0.0001"
                    min="0"
                    required
                >
            </div>

        </div>

        <br>

        <button type="submit">
            Add Plan
        </button>

    </form>

</div>


{{-- Plans List --}}

<div class="card">

    <h2>Plan List</h2>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Merchant</th>
                    <th>Plan</th>
                    <th>Base Price</th>
                    <th>Billing Cycle</th>
                    <th>Included Units</th>
                    <th>Overage Rate</th>
                    <th>Subscriptions</th>
                    <th>Created</th>
                </tr>

            </thead>

            <tbody>

                @forelse($plans as $plan)

                    <tr>

                        <td>
                            {{ $plan->id }}
                        </td>

                        <td>
                            {{ $plan->merchant->name ?? 'N/A' }}
                        </td>

                        <td>
                            <strong>
                                {{ $plan->name }}
                            </strong>
                        </td>

                        <td>
                            ₹{{ number_format((float) $plan->base_price, 2) }}
                        </td>

                        <td>
                            {{ ucfirst($plan->billing_cycle) }}
                        </td>

                        <td>
                            {{ number_format($plan->included_units) }}
                        </td>

                        <td>
                            ₹{{ number_format((float) $plan->overage_rate, 4) }}
                        </td>

                        <td>
                            {{ $plan->subscriptions_count }}
                        </td>

                        <td>
                            {{ $plan->created_at?->format('d M Y') }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="9" style="text-align: center;">
                            No plans found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection