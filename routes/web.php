
<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionChangeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\MerchantDashboardController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', [
    DashboardController::class,
    'index'
])->name('dashboard');


/*
|--------------------------------------------------------------------------
| Merchants
|--------------------------------------------------------------------------
*/

Route::resource(
    'merchants',
    MerchantController::class
);


/*
|--------------------------------------------------------------------------
| Merchant Dashboard API
|--------------------------------------------------------------------------
*/

Route::get(
    '/merchants/{merchant}/dashboard',
    [
        MerchantDashboardController::class,
        'show'
    ]
)->name('merchants.dashboard');


/*
|--------------------------------------------------------------------------
| Plans
|--------------------------------------------------------------------------
*/

Route::resource(
    'plans',
    PlanController::class
);


/*
|--------------------------------------------------------------------------
| Customers
|--------------------------------------------------------------------------
*/

Route::resource(
    'customers',
    CustomerController::class
);


/*
|--------------------------------------------------------------------------
| Subscriptions
|--------------------------------------------------------------------------
*/

Route::get(
    '/subscriptions',
    [
        SubscriptionController::class,
        'index'
    ]
)->name('subscriptions.index');

Route::post(
    '/subscriptions',
    [
        SubscriptionController::class,
        'store'
    ]
)->name('subscriptions.store');


/*
|--------------------------------------------------------------------------
| Subscription Changes
|--------------------------------------------------------------------------
*/

Route::get(
    '/subscription-changes',
    [
        SubscriptionChangeController::class,
        'index'
    ]
)->name('subscription-changes.index');

Route::post(
    '/subscription-changes',
    [
        SubscriptionChangeController::class,
        'store'
    ]
)->name('subscription-changes.store');


/*
|--------------------------------------------------------------------------
| Usage
|--------------------------------------------------------------------------
*/

Route::get(
    '/usage',
    [
        UsageController::class,
        'index'
    ]
)->name('usage.index');

Route::post(
    '/usage',
    [
        UsageController::class,
        'store'
    ]
)->name('usage.store');


/*
|--------------------------------------------------------------------------
| Usage History
|--------------------------------------------------------------------------
*/

Route::get(
    '/usage/history',
    [
        UsageHistoryController::class,
        'index'
    ]
)->name('usage.history');

/*
|--------------------------------------------------------------------------
| Invoices
|--------------------------------------------------------------------------
*/

Route::get(
    '/invoices',
    [
        InvoiceController::class,
        'index'
    ]
)->name('invoices.index');

Route::post(
    '/invoices',
    [
        InvoiceController::class,
        'store'
    ]
)->name('invoices.store');

