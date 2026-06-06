<?php

use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerBillController;
use App\Http\Controllers\Api\Customer\CustomerNotificationController;
use App\Http\Controllers\Api\Shop\ShopAuthController;
use App\Http\Controllers\Api\Shop\ShopBillController;
use App\Http\Controllers\Api\Shop\ShopDashboardController;
use App\Http\Controllers\Api\Shop\ShopExpenseController;
use App\Http\Controllers\Api\Shop\ShopProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->name('customer.')->group(function () {
    Route::post('send-otp', [CustomerAuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('verify-otp', [CustomerAuthController::class, 'verifyOtp'])->name('verify-otp');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [CustomerAuthController::class, 'profile'])->name('profile');
        Route::get('bills', [CustomerBillController::class, 'index'])->name('bills.index');
        Route::get('bills/{bill}', [CustomerBillController::class, 'show'])->name('bills.show');
        Route::get('notifications', [CustomerNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [CustomerNotificationController::class, 'markAsRead'])->name('notifications.read');
    });
});

Route::prefix('shop')->name('shop.')->group(function () {
    Route::post('login', [ShopAuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [ShopAuthController::class, 'profile'])->name('profile');
        Route::get('subscription', [ShopAuthController::class, 'subscription'])->name('subscription');
        Route::get('dashboard', [ShopDashboardController::class, 'index'])->name('dashboard');
        Route::get('bills', [ShopBillController::class, 'index'])->name('bills.index');
        Route::post('bills', [ShopBillController::class, 'store'])->name('bills.store');
        Route::get('bills/{bill}', [ShopBillController::class, 'show'])->name('bills.show');
        Route::put('bills/{bill}', [ShopBillController::class, 'update'])->name('bills.update');
        Route::post('bills/{bill}/status', [ShopBillController::class, 'updateStatus'])->name('bills.status');
        Route::get('products', [ShopProductController::class, 'index'])->name('products.index');
        Route::get('product-categories', [ShopProductController::class, 'categories'])->name('product-categories.index');
        Route::get('customers', [ShopDashboardController::class, 'customers'])->name('customers.index');
        Route::get('expenses', [ShopExpenseController::class, 'index'])->name('expenses.index');
        Route::post('expenses', [ShopExpenseController::class, 'store'])->name('expenses.store');
        Route::get('expense-categories', [ShopExpenseController::class, 'categories'])->name('expense-categories.index');
    });
});
