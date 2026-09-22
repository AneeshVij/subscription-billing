@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="page-header">

    <h1>Dashboard</h1>

    <p>
        Subscription billing overview
    </p>

</div>


{{-- Summary Cards --}}

<div class="dashboard-grid">

    {{-- Customers --}}

    <div class="dashboard-card">

        <div class="dashboard-card-label">
            Total Customers
        </div>

        <div class="dashboard-card-value">
            {{ $customerCount }}
        </div>

        <div class="dashboard-card-description">
            Customers registered in the system
        </div>

    </div>


    {{-- Usage --}}

    <div class="dashboard-card">

        <div class="dashboard-card-label">
            Total Usage
        </div>

        <div class="dashboard-card-value">
            {{ number_format($totalUsage) }}
        </div>

        <div class="dashboard-card-description">
            Usage units this month
        </div>

    </div>


    {{-- Projected Overage --}}

    <div class="dashboard-card">

        <div class="dashboard-card-label">
            Projected Overage Revenue
        </div>

        <div class="dashboard-card-value">
            ₹{{ number_format((float) $projectedOverage, 2) }}
        </div>

        <div class="dashboard-card-description">
            Current billing cycle
        </div>

    </div>

</div>


{{-- Top Customers --}}

<div class="card">

    <div class="section-header">

        <div>

            <h2>Top 5 Customers by Usage</h2>

            <p>
                Customers with the highest usage this month.
            </p>

        </div>

    </div>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>Rank</th>
                    <th>Customer</th>
                    <th>Usage Units</th>
                </tr>

            </thead>

            <tbody>

                @forelse($topCustomers as $index => $customerUsage)

                    <tr>

                        <td>
                            <strong>
                                {{ $index + 1 }}
                            </strong>
                        </td>

                        <td>
                            {{ $customerUsage->customer->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ number_format(
                                (int) ($customerUsage->total_usage ?? 0)
                            ) }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3" style="text-align: center;">

                            No usage data available yet.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- Usage Drop --}}

<div class="card">

    <div class="section-header">

        <div>

            <h2>Customers With Usage Drop &gt; 50%</h2>

            <p>
                Customers whose usage dropped significantly compared with
                the previous month.
            </p>

        </div>

    </div>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>Customer</th>
                    <th>Previous Month</th>
                    <th>Current Month</th>
                    <th>Change</th>
                </tr>

            </thead>

            <tbody>

                @forelse($usageDropCustomers as $usageDrop)

                    <tr>

                        <td>
                            {{ $usageDrop['customer']->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ number_format(
                                (int) ($usageDrop['previous_usage'] ?? 0)
                            ) }}
                        </td>

                        <td>
                            {{ number_format(
                                (int) ($usageDrop['current_usage'] ?? 0)
                            ) }}
                        </td>

                        <td>
                            {{ number_format(
                                (float) ($usageDrop['drop_percentage'] ?? 0),
                                1
                            ) }}%
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" style="text-align: center;">

                            No customers with usage drop greater than 50%.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection