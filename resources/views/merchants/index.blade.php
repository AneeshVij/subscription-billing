@extends('layouts.app')

@section('title', 'Merchants')

@section('content')

<div class="page-header">
    <h1>Merchants</h1>
    <p>Manage SaaS tenants</p>
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


{{-- Add Merchant --}}

<div class="card">

    <h2>Add Merchant</h2>

    <form method="POST" action="{{ route('merchants.store') }}">

        @csrf

        <div class="form-grid">

            <div>
                <label for="name">
                    Merchant Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Enter merchant name"
                    required
                >
            </div>

        </div>

        <br>

        <button type="submit">
            Add Merchant
        </button>

    </form>

</div>


{{-- Merchant List --}}

<div class="card">

    <h2>Merchant List</h2>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Merchant</th>
                    <th>Customers</th>
                    <th>Plans</th>
                    <th>Usage Events</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>

                @forelse($merchants as $merchant)

                    <tr>

                        <td>
                            {{ $merchant->id }}
                        </td>

                        <td>
                            <strong>
                                {{ $merchant->name }}
                            </strong>
                        </td>

                        <td>
                            {{ $merchant->customers_count }}
                        </td>

                        <td>
                            {{ $merchant->plans_count }}
                        </td>

                        <td>
                            {{ $merchant->usage_events_count }}
                        </td>

                        <td>
                            {{ $merchant->created_at?->format('d M Y') }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" style="text-align: center;">
                            No merchants found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection