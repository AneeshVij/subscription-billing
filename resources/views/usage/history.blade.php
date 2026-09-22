
@extends('layouts.app')

@section('title', 'Usage History')

@section('content')

<div class="page-header">

    <h1>Usage History</h1>

    <p>
        View and filter previously recorded customer usage.
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


{{-- Filters --}}

<div class="card">

    <div class="section-header">

        <div>

            <h2>Filter Usage History</h2>

            <p>
                Filter usage records by merchant, customer or date.
            </p>

        </div>

    </div>


    <form
        method="GET"
        action="{{ route('usage.history') }}"
    >

        <div class="form-grid">

            {{-- Merchant --}}

            <div>

                <label for="merchant_id">
                    Merchant
                </label>

                <select
                    name="merchant_id"
                    id="merchant_id"
                >

                    <option value="">
                        All Merchants
                    </option>

                    @foreach($merchants as $merchant)

                        <option
                            value="{{ $merchant->id }}"
                            {{ request('merchant_id') == $merchant->id ? 'selected' : '' }}
                        >

                            {{ $merchant->name }}

                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Customer --}}

            <div>

                <label for="customer_id">
                    Customer
                </label>

                <select
                    name="customer_id"
                    id="customer_id"
                >

                    <option value="">
                        All Customers
                    </option>

                    @foreach($customers as $customer)

                        <option
                            value="{{ $customer->id }}"
                            {{ request('customer_id') == $customer->id ? 'selected' : '' }}
                        >

                            {{ $customer->name }}

                            -

                            {{ $customer->merchant->name ?? 'N/A' }}

                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Usage Date --}}

            <div>

                <label for="usage_date">
                    Usage Date
                </label>

                <input
                    type="date"
                    name="usage_date"
                    id="usage_date"
                    value="{{ request('usage_date') }}"
                >

            </div>

        </div>


        <br>


        <button type="submit">
            Apply Filters
        </button>

        <a
            href="{{ route('usage.history') }}"
            style="margin-left: 10px;"
        >
            Clear Filters
        </a>

    </form>

</div>


{{-- Usage History --}}

<div class="card">

    <div class="section-header">

        <div>

            <h2>Usage History</h2>

            <p>
                {{ $usageEvents->count() }} usage event(s) found.
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

                    <th>Usage Date</th>

                    <th>Units</th>

                    <th>Idempotency Key</th>

                    <th>Metadata</th>

                    <th>Created</th>

                </tr>

            </thead>


            <tbody>

                @forelse($usageEvents as $usage)

                    <tr>

                        <td>
                            {{ $usage->id }}
                        </td>


                        <td>

                            <strong>
                                {{ $usage->customer->name ?? 'N/A' }}
                            </strong>

                        </td>


                        <td>
                            {{ $usage->merchant->name ?? 'N/A' }}
                        </td>


                        <td>
                            {{ $usage->usage_date?->format('d M Y') }}
                        </td>


                        <td>
                            {{ number_format($usage->units) }}
                        </td>


                        <td>
                            {{ $usage->idempotency_key }}
                        </td>


                        <td>

                            @if($usage->metadata)

                                <code>
                                    {{ json_encode($usage->metadata) }}
                                </code>

                            @else

                                —

                            @endif

                        </td>


                        <td>
                            {{ $usage->created_at?->format('d M Y') }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            style="text-align: center;"
                        >

                            No usage history found.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection

