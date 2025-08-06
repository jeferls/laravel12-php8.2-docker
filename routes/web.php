<?php

use App\Http\Controllers\ExchangeRateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'OK';
});

// Route::controller(ExchangeRateController::class)->group(function () {
//     Route::get('/exchange-rates', 'exchangeRates');
// });

Route::get('/docs', function () {
    return redirect('/api/documentation');
});
