<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/login', [AuthController::class,'loginPage'])->name('login');
Route::post('/login', [AuthController::class,'login']);

Route::group(['middleware' => ['auth:sanctum']],function(){
    Route::group(['prefix' => 'basket', 'controller' => \App\Http\Controllers\BasketController::class],function(){
       Route::get('/get-details', 'getDetails');
       Route::post('/add-product', 'addProductToBasket');
       Route::post('/remove-product', 'removeProductFromBasket');
    });
    Route::group(['prefix' => 'coupon', 'controller' => \App\Http\Controllers\CouponController::class],function(){
       Route::post('/apply', 'applyCode');
       Route::post('/remove', 'removeCode');
    });
    Route::group(['prefix' => 'order', 'controller' => \App\Http\Controllers\OrderController::class],function(){
        Route::get('/details', 'orderDetails');
        Route::post('/create', 'createOrder');
        Route::post('/increase-quantity', 'increaseQuantity');
        Route::post('/decrease-quantity', 'decreaseQuantity');
        Route::post('/remove-product', 'removeProduct');
    });
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
