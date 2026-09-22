<?php

use App\Http\Controllers\UsageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/usage', [
    UsageController::class,
    'store'
])->middleware('throttle:usage');


RateLimiter::for('usage', function (Request $request) {

    return Limit::perMinute(60)
        ->by(
            $request->input('merchant_id')
            . '|'
            . $request->ip()
        );
});