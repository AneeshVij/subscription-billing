@extends('layouts.app')

@section('title', 'Usage')

@section('content')

<div class="page-header">

    <h1>Usage</h1>

    <p>
        Record and manage customer usage events.
    </p>

</div>


{{-- Success Message --}}

<div
    id="usageSuccess"
    class="alert success"
    style="display: none;"
></div>


{{-- Error Message --}}

<div
    id="usageError"
    class="alert error"
    style="display: none;"
>
    <ul
        id="usageErrorList"
        style="margin: 0; padding-left: 20px;"
    ></ul>
</div>


{{-- Create Usage Event --}}

<div class="card">

    <h2>Record Usage</h2>

    <form id="usageForm">

        @csrf

        <div class="form-grid">

            {{-- Merchant --}}

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
                            data-merchant-id="{{ $customer->merchant_id }}"
                            {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                        >
                            {{ $customer->name }}
                            -
                            {{ $customer->merchant->name ?? 'N/A' }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Idempotency Key --}}

            <div>

                <label for="idempotency_key">
                    Idempotency Key
                </label>

                <input
                    type="text"
                    name="idempotency_key"
                    id="idempotency_key"
                    value="{{ old('idempotency_key') }}"
                    placeholder="Example: usage-001"
                    maxlength="100"
                    required
                >

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
                    value="{{ old('usage_date', now()->format('Y-m-d')) }}"
                    required
                >

            </div>


            {{-- Units --}}

            <div>

                <label for="units">
                    Units
                </label>

                <input
                    type="number"
                    name="units"
                    id="units"
                    value="{{ old('units') }}"
                    min="1"
                    required
                >

            </div>


            {{-- Metadata --}}

            <div>

                <label for="metadata">
                    Metadata
                </label>

                <textarea
                    name="metadata"
                    id="metadata"
                    rows="3"
                    placeholder='Example: {"source":"api"}'
                >{{ old('metadata') }}</textarea>

            </div>

        </div>

        <br>

        <button
            type="submit"
            id="recordUsageButton"
        >
            Record Usage
        </button>

    </form>

</div>


{{-- Usage List --}}

<div class="card">

    <div class="section-header">

        <div>

            <h2>Usage List</h2>

            <p>
                Existing customer usage events.
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

                                {{ json_encode($usage->metadata) }}

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
                            No usage events found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- API Submission JavaScript --}}

<script>

document
    .getElementById('usageForm')
    .addEventListener('submit', async function (event) {

        event.preventDefault();


        const form =
            document.getElementById('usageForm');

        const button =
            document.getElementById(
                'recordUsageButton'
            );

        const successBox =
            document.getElementById(
                'usageSuccess'
            );

        const errorBox =
            document.getElementById(
                'usageError'
            );

        const errorList =
            document.getElementById(
                'usageErrorList'
            );


        /*
         * Hide previous messages.
         */

        successBox.style.display = 'none';

        errorBox.style.display = 'none';

        errorList.innerHTML = '';


        /*
         * Read form values.
         */

        const merchantId =
            document.getElementById(
                'merchant_id'
            ).value;

        const customerId =
            document.getElementById(
                'customer_id'
            ).value;

        const idempotencyKey =
            document.getElementById(
                'idempotency_key'
            ).value;

        const usageDate =
            document.getElementById(
                'usage_date'
            ).value;

        const units =
            document.getElementById(
                'units'
            ).value;

        const metadataText =
            document.getElementById(
                'metadata'
            ).value;


        /*
         * Convert metadata text into JSON.
         */

        let metadata = null;

        if (metadataText.trim() !== '') {

            try {

                metadata =
                    JSON.parse(
                        metadataText
                    );

            } catch (error) {

                const li =
                    document.createElement(
                        'li'
                    );

                li.innerText =
                    'Metadata must contain valid JSON.';

                errorList.appendChild(li);

                errorBox.style.display =
                    'block';

                return;
            }
        }


        /*
         * Disable button while request
         * is being processed.
         */

        button.disabled = true;

        button.innerText =
            'Recording...';


        try {

            /*
             * Call our API.
             */

            const response =
                await fetch(
                    "{{ url('/api/usage') }}",
                    {
                        method: 'POST',

                        headers: {

                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'input[name="_token"]'
                                    )
                                    .value
                        },

                        body: JSON.stringify({

                            merchant_id:
                                Number(merchantId),

                            customer_id:
                                Number(customerId),

                            idempotency_key:
                                idempotencyKey,

                            usage_date:
                                usageDate,

                            units:
                                Number(units),

                            metadata:
                                metadata

                        })
                    }
                );


            /*
             * Read API response.
             */

            const data =
                await response.json();


            /*
             * Handle validation errors.
             */

            if (!response.ok) {

                if (data.errors) {

                    Object
                        .values(data.errors)
                        .flat()
                        .forEach(function (error) {

                            const li =
                                document.createElement(
                                    'li'
                                );

                            li.innerText =
                                error;

                            errorList.appendChild(
                                li
                            );

                        });

                } else {

                    const li =
                        document.createElement(
                            'li'
                        );

                    li.innerText =
                        data.message
                        ||
                        'Something went wrong.';

                    errorList.appendChild(
                        li
                    );
                }

                errorBox.style.display =
                    'block';

                return;
            }


            /*
             * Successful API response.
             */

            successBox.innerText =
                data.message;

            successBox.style.display =
                'block';


            /*
             * Reload the page so the
             * new usage event appears
             * in the Usage List.
             */

            setTimeout(function () {

                window.location.reload();

            }, 1000);


        } catch (error) {

            /*
             * Network/server error.
             */

            const li =
                document.createElement(
                    'li'
                );

            li.innerText =
                'Unable to connect to the usage API.';

            errorList.appendChild(
                li
            );

            errorBox.style.display =
                'block';

        } finally {

            /*
             * Re-enable button.
             */

            button.disabled = false;

            button.innerText =
                'Record Usage';

        }

    });

</script>

@endsection