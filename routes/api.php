<?php

use App\Http\Controllers\BestSellersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('best-sellers', [BestSellersController::class, 'getList']);
});
