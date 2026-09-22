@extends('layouts.app')

@section('title', 'Subscription Changes')

@section('content')

<div class="page-header">
    <h1>Subscription Changes</h1>
    <p>
        Change customer subscription plans and view plan change history.
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


<div class="card">

    <h2>Change Subscription Plan</h2>

    <p>
        Select an active subscription and change it to another plan.
    </p>

    <form method="POST" action="{{ route('subscription-changes.store') }}">

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
                <label for="new_plan_id">
                    New Plan
                </label>

                <select
                    name="new_plan_id"
                    id="new_plan_id"
                    required
                >
                    <option value="">
                        Select New Plan
                    </option>

                    @foreach($plans as $plan)

                        <option
                            value="{{ $plan->id }}"
                            {{ old('new_plan_id') == $plan->id ? 'selected' : '' }}
                        >
                            {{ $plan->name }}
                            -
                            {{ $plan->merchant->name ?? 'N/A' }}
                            -
                            ₹{{ number_format((float) $plan->base_price, 2) }}
                        </option>

                    @endforeach

                </select>
            </div>


            <div>
                <label for="effective_at">
                    Effective Date & Time
                </label>

                <input
                    type="datetime-local"
                    name="effective_at"
                    id="effective_at"
                    value="{{ old('effective_at', now()->format('Y-m-d\TH:i')) }}"
                    required
                >
            </div>

        </div>

        <br>

        <button type="submit">
            Change Plan
        </button>

    </form>

</div>


<div class="card">

    <div class="section-header">

        <div>
            <h2>Subscription Change History</h2>

            <p>
                View all previous subscription plan changes.
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
                    <th>Old Plan</th>
                    <th>New Plan</th>
                    <th>Effective At</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>

                @forelse($changes as $change)

                    <tr>

                        <td>
                            {{ $change->id }}
                        </td>

                        <td>
                            <strong>
                                {{ $change->subscription->customer->name ?? 'N/A' }}
                            </strong>
                        </td>

                        <td>
                            {{ $change->subscription->customer->merchant->name ?? 'N/A' }}
                        </td>

                        <td>
                            #{{ $change->subscription_id }}
                        </td>

                        <td>
                            {{ $change->oldPlan->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ $change->newPlan->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ $change->effective_at?->format('d M Y H:i') }}
                        </td>

                        <td>
                            {{ $change->created_at?->format('d M Y') }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            style="text-align: center;"
                        >
                            No subscription changes found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection