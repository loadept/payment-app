<?php

use App\Orders\Controllers\OrderController;
use App\Products\Controllers\ProductController;
use App\Payments\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'list']);

Route::get('/orders/{orderId?}', [OrderController::class, 'getOrdersByUser']);
Route::post('/orders', [OrderController::class, 'create']);

Route::get('/payments/plan/{orderId}', [PaymentController::class, 'getPaymentPlans']);
Route::post('/payments/pay', [PaymentController::class, 'payOrderment']);
