<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PurchaseOrderItemDeliveryController;

// Auth Routes
Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('api')
    ->name('login');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('createPurchaseOrder', [PurchaseOrderController::class, 'createPurchaseOrder']);
    Route::put('purchaseOrders/{id}/status', [PurchaseOrderController::class, 'updatePurchaseOrderStatus']);
    Route::resource('purchaseOrderItemDeliveries', PurchaseOrderItemDeliveryController::class);
    Route::post('saveScannedBarcodes', [BarcodeController::class, 'saveScannedBarcodes']);
    Route::post('/products', [ProductController::class, 'store']);  // Create a product
    Route::put('/products/{product}', [ProductController::class, 'update']); // Update a product

});

Route::get('/purchaseOrders/{purchaseOrderId?}', [PurchaseOrderController::class, 'getPurchaseOrderDetails']);

Route::get('getAllProducts', [ProductController::class, 'getAllProducts']);


