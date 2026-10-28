<?php

use App\Http\Controllers\BillingWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [BillingWebController::class, 'index'])->name('billing.index');
