<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PurchaseOrderItemDeliveryController;

// Auth Routes
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

// Protected Routes
// Required Authentication
Route::middleware('auth:sanctum')->group(function () {
    

    Route::get('user', [UserController::class,'getUserData']);


    Route::post('createPurchaseOrder', [PurchaseOrderController::class, 'createPurchaseOrder']);
    Route::put('purchaseOrders/{id}/status', [PurchaseOrderController::class, 'updatePurchaseOrderStatus']);
    Route::resource('purchaseOrderItemDeliveries', PurchaseOrderItemDeliveryController::class);
    Route::post('products', [ProductController::class, 'store']);  // Create a product
    Route::put('products/{product}', [ProductController::class, 'update']); // Update a product

});

Route::get('/purchaseOrders/{purchaseOrderId?}', [PurchaseOrderController::class, 'getPurchaseOrderDetails']);

Route::get('getAllProducts/{productId?}', [ProductController::class, 'getAllProducts']);


