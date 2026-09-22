<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Subscription Billing')</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f6fa;
            color: #1f2937;
        }

        /* Main application layout */

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */

        .sidebar {
            width: 230px;
            min-height: 100vh;
            background: #1f2937;
            color: white;
            padding: 20px 0;
            flex-shrink: 0;
        }

        .sidebar h2 {
            margin: 0;
            padding: 0 20px 25px;
            font-size: 20px;
            line-height: 1.4;
        }

        .sidebar a {
            display: block;
            padding: 13px 20px;
            color: #e5e7eb;
            text-decoration: none;
            font-size: 15px;
        }

        .sidebar a:hover {
            background: #374151;
            color: white;
        }

        /* Main content */

        .content {
            flex: 1;
            min-width: 0;
            padding: 32px;
            overflow-x: auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
            line-height: 1.3;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }

        /* Cards */

        .card {
            width: 100%;
            background: white;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .card h2 {
            margin: 0 0 20px;
            font-size: 21px;
            line-height: 1.4;
        }

        .card p {
            color: #6b7280;
        }

        /* Forms */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-grid > div {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-width: 0;
            height: 40px;
            padding: 8px 11px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            color: #111827;
            font-size: 14px;
        }

        textarea {
            height: auto;
            min-height: 90px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
        }

        /* Buttons */

        button {
            border: none;
            border-radius: 6px;
            padding: 11px 18px;
            background: #2563eb;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        /* Alerts */

        .alert {
            padding: 13px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Tables */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: #f8fafc;
        }

        th {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        /* Badges */

        .badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 20px;
            background: #e5e7eb;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge.success {
            background: #dcfce7;
            color: #166534;
        }

        /* Dashboard */

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .dashboard-card-label {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .dashboard-card-value {
            font-size: 30px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .dashboard-card-description {
            font-size: 13px;
            color: #9ca3af;
        }

        .section-header {
            margin-bottom: 18px;
        }

        .section-header h2 {
            margin: 0 0 6px;
            font-size: 21px;
        }

        .section-header p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        /* Responsive layout */

        @media (max-width: 1000px) {
            .sidebar {
                width: 200px;
            }

            .content {
                padding: 24px;
            }

            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .app {
                display: block;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
                padding: 15px 0;
            }

            .sidebar h2 {
                padding: 0 15px 15px;
            }

            .sidebar a {
                display: inline-block;
                padding: 10px 15px;
            }

            .content {
                padding: 16px;
            }

            .page-header h1 {
                font-size: 26px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <aside class="sidebar">

        <h2>Subscription Billing</h2>

        <a href="{{ route('dashboard') }}">
            Dashboard
        </a>

        <a href="{{ route('merchants.index') }}">
            Merchants
        </a>

        <a href="{{ route('plans.index') }}">
            Plans
        </a>

        <a href="{{ route('customers.index') }}">
            Customers
        </a>

        <a href="{{ route('subscriptions.index') }}">
            Subscriptions
        </a>

        <a href="{{ route('usage.index') }}">
            Usage
        </a>

        <a href="{{ route('usage.history') }}">
            Usage History
        </a>

        <a href="{{ route('subscription-changes.index') }}">
            Plan Changes
        </a>

        <a href="{{ route('invoices.index') }}">
            Invoices
        </a>

    </aside>

    <main class="content">

        @yield('content')

    </main>

</div>

</body>
</html>