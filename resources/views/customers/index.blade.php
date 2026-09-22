@extends('layouts.app')

@section('content')

<div class="page-header">
    <div>
        <h1>Customers</h1>
        <p>Manage customers belonging to your merchants.</p>
    </div>
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

    <h2>Add Customer</h2>

    <form method="POST" action="{{ route('customers.store') }}">
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
                    Customer Name
                </label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    placeholder="Enter customer name"
                    required
                >
            </div>


            <div>
                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    placeholder="customer@example.com"
                    required
                >
            </div>

        </div>

        <br>

        <button type="submit">
            Add Customer
        </button>

    </form>

</div>


<div class="card">

    <div style="
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    ">
        <div>
            <h2>Customer List</h2>
            <p>
                Total Customers:
                <strong>{{ $customers->count() }}</strong>
            </p>
        </div>
    </div>


    <div style="overflow-x: auto;">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Merchant</th>
                    <th>Current Plan</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>

                @forelse($customers as $customer)

                    @php
                        $activeSubscription = $customer->subscriptions
                            ->where('status', 'active')
                            ->sortByDesc('starts_at')
                            ->first();
                    @endphp

                    <tr>

                        <td>
                            {{ $customer->id }}
                        </td>

                        <td>
                            <strong>
                                {{ $customer->name }}
                            </strong>
                        </td>

                        <td>
                            {{ $customer->email }}
                        </td>

                        <td>
                            {{ $customer->merchant->name ?? 'N/A' }}
                        </td>

                        <td>

                            @if($activeSubscription && $activeSubscription->plan)
                                {{ $activeSubscription->plan->name }}
                            @else
                                <span style="color:#777;">
                                    No active plan
                                </span>
                            @endif

                        </td>

                        <td>

                            @if($activeSubscription)

                                <span class="badge success">
                                    Active
                                </span>

                            @else

                                <span class="badge">
                                    No Subscription
                                </span>

                            @endif

                        </td>

                        <td>
                            {{ $customer->created_at?->format('d M Y') }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7" style="text-align:center;">
                            No customers found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection