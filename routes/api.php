<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\CalibrationRecordController;
use App\Http\Controllers\MaintenanceRecordController;
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
 

    Route::apiResource('products', ProductController::class);

    
    Route::apiResource('calibrationRecords', CalibrationRecordController::class)->except(['show']);
    Route::get('calibrationRecords/{serial_number}', [CalibrationRecordController::class, 'show']);

    Route::apiResource('maintenanceRecords', MaintenanceRecordController::class)->except(['show']);
    Route::get('maintenanceRecords/{serial_number}', [MaintenanceRecordController::class, 'show']);


    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('warehouses', WarehouseController::class);

});

Route::get('/purchaseOrders/{purchaseOrderId?}', [PurchaseOrderController::class, 'getPurchaseOrderDetails']);

Route::get('getAllProducts/{productId?}', [ProductController::class, 'getAllProducts']);


