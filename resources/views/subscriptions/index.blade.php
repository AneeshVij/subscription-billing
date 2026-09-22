@extends('layouts.app')

@section('title', 'Subscriptions')

@section('content')

<div class="page-header">

    <h1>Subscriptions</h1>

    <p>
        Manage customer subscriptions and plans.
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

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

    </div>

@endif


{{-- Create Subscription --}}

<div class="card">

    <h2>Create Subscription</h2>

    <form
        method="POST"
        action="{{ route('subscriptions.store') }}"
    >

        @csrf


        <div class="form-grid">

            {{-- Customer --}}

            <div>

                <label for="customer_id">
                    Customer
                </label>

                <select
                    name="customer_id"
                    id="customer_id"
                    required
                >

                    <option value="">
                        Select Customer
                    </option>

                    @foreach($customers as $customer)

                        <option
                            value="{{ $customer->id }}"
                            {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                        >

                            {{ $customer->name }}

                            -
                            {{ $customer->merchant->name }}

                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Plan --}}

            <div>

                <label for="plan_id">
                    Plan
                </label>

                <select
                    name="plan_id"
                    id="plan_id"
                    required
                >

                    <option value="">
                        Select Plan
                    </option>

                    @foreach($plans as $plan)

                        <option
                            value="{{ $plan->id }}"
                            {{ old('plan_id') == $plan->id ? 'selected' : '' }}
                        >

                            {{ $plan->name }}

                            -
                            {{ $plan->merchant->name }}

                            -
                            ₹{{ number_format((float) $plan->base_price, 2) }}

                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Start Date --}}

            <div>

                <label for="starts_at">
                    Start Date
                </label>

                <input
                    type="date"
                    name="starts_at"
                    id="starts_at"
                    value="{{ old('starts_at', now()->format('Y-m-d')) }}"
                    required
                >

            </div>

        </div>


        <br>

        <button type="submit">
            Create Subscription
        </button>

    </form>

</div>


{{-- Subscription List --}}

<div class="card">

    <div class="section-header">

        <div>

            <h2>Subscription List</h2>

            <p>
                Existing customer subscriptions.
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

                    <th>Plan</th>

                    <th>Start Date</th>

                    <th>End Date</th>

                    <th>Status</th>

                    <th>Created</th>

                </tr>

            </thead>


            <tbody>

                @forelse($subscriptions as $subscription)

                    <tr>

                        <td>
                            {{ $subscription->id }}
                        </td>


                        <td>

                            <strong>
                                {{ $subscription->customer->name ?? 'N/A' }}
                            </strong>

                        </td>


                        <td>

                            {{ $subscription->customer->merchant->name ?? 'N/A' }}

                        </td>


                        <td>

                            {{ $subscription->plan->name ?? 'N/A' }}

                        </td>


                        <td>

                            {{ $subscription->starts_at?->format('d M Y') }}

                        </td>


                        <td>

                            @if($subscription->ends_at)

                                {{ $subscription->ends_at->format('d M Y') }}

                            @else

                                —

                            @endif

                        </td>


                        <td>

                            @if($subscription->status === 'active')

                                <span class="badge success">
                                    Active
                                </span>

                            @elseif($subscription->status === 'cancelled')

                                <span class="badge">
                                    Cancelled
                                </span>

                            @else

                                <span class="badge">
                                    {{ ucfirst($subscription->status) }}
                                </span>

                            @endif

                        </td>


                        <td>

                            {{ $subscription->created_at?->format('d M Y') }}

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            style="text-align: center;"
                        >

                            No subscriptions found.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection