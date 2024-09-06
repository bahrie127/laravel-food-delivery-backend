<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//user register
Route::post('/user/register', [App\Http\Controllers\Api\AuthController::class, 'userRegister']);

//restaurant register
Route::post('/restaurant/register', [App\Http\Controllers\Api\AuthController::class, 'restaurantRegister']);

//driver register
Route::post('/driver/register', [App\Http\Controllers\Api\AuthController::class, 'driverRegister']);

//login
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

//logout
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'clear'])->middleware('auth:sanctum');

//update latlong
Route::put('/user/update-latlong', [App\Http\Controllers\Api\AuthController::class, 'updateLatLong'])->middleware('auth:sanctum');

//update fcm id
Route::put('/user/update-fcm', [App\Http\Controllers\Api\AuthController::class, 'updateFcmId'])->middleware('auth:sanctum');



//get all restaurant
Route::get('/restaurant', [App\Http\Controllers\Api\AuthController::class, 'getRestaurant']);

Route::apiResource('/products', App\Http\Controllers\Api\ProductController::class)->middleware('auth:sanctum');

//get product by user id
Route::get('/restaurant/{userId}/products', [App\Http\Controllers\Api\ProductController::class, 'getProductByUserId'])->middleware('auth:sanctum');

//get total by restaurant
Route::prefix('/total')->middleware('auth:sanctum')->group(function () {
    Route::get('/product', [App\Http\Controllers\Api\TotalCounterController::class, 'getTotalProduct']);
    Route::get('/order', [App\Http\Controllers\Api\TotalCounterController::class, 'getTotalOrder']);
    Route::get('/today-transaction', [App\Http\Controllers\Api\TotalCounterController::class, 'getTodayTransaction']);
    Route::get('/income', [App\Http\Controllers\Api\TotalCounterController::class, 'getTotalIncome']);
});

//order
Route::post('/order', [App\Http\Controllers\Api\OrderController::class, 'createOrder'])->middleware('auth:sanctum');

//get payment method
Route::get('/payment-methods', [App\Http\Controllers\Api\OrderController::class, 'getPaymentMethods']);

//purchase order
Route::post('/purchase/{orderId}', [App\Http\Controllers\Api\OrderController::class, 'purchaseOrder'])->middleware('auth:sanctum');

Route::post('/xendit-callback', [App\Http\Controllers\Api\OrderController::class, 'webhook']);


//get order by user id
Route::get('/order/user', [App\Http\Controllers\Api\OrderController::class, 'orderHistory'])->middleware('auth:sanctum');

//get order by restaurant id
//Route::get('/order/restaurant', [App\Http\Controllers\Api\OrderController::class, 'getOrdersByStatus'])->middleware('auth:sanctum');

//get order by driver id
Route::get('/order/driver', [App\Http\Controllers\Api\OrderController::class, 'getOrdersByStatusDriver'])->middleware('auth:sanctum');

//update order status
Route::put('/order/restaurant/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatus'])->middleware('auth:sanctum');

//update order status driver
Route::put('/order/driver/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatusDriver'])->middleware('auth:sanctum');

//update purchase status
Route::put('/order/user/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updatePurchaseStatus'])->middleware('auth:sanctum');

//get order by restaurant id
Route::get('/order/restaurant', [App\Http\Controllers\Api\OrderController::class, 'getOrdersByRestaurantId'])->middleware('auth:sanctum');

//get order by status waiting pickup
Route::get('/order/driver/waiting-pickup', [App\Http\Controllers\Api\OrderController::class, 'getOrdersWaitingPickup'])->middleware('auth:sanctum');

//get order by status on the way
Route::put('/order/driver/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatusDriver'])->middleware('auth:sanctum');
