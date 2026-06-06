<?php

use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerBillController;
use App\Http\Controllers\Api\Customer\CustomerNotificationController;
use App\Http\Controllers\Api\Shop\ShopAuthController;
use App\Http\Controllers\Api\Shop\ShopBillController;
use App\Http\Controllers\Api\Shop\ShopDashboardController;
use App\Http\Controllers\Api\Shop\ShopExpenseController;
use App\Http\Controllers\Api\Shop\ShopProductController;
use App\Http\Controllers\Api\Shop\ShopReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->name('customer.')->group(function () {
    Route::post('send-otp', [CustomerAuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('verify-otp', [CustomerAuthController::class, 'verifyOtp'])->name('verify-otp');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [CustomerAuthController::class, 'profile'])->name('profile');
        Route::put('profile', [CustomerAuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('logout', [CustomerAuthController::class, 'logout'])->name('logout');
        Route::get('bills', [CustomerBillController::class, 'index'])->name('bills.index');
        Route::get('bills/{bill}', [CustomerBillController::class, 'show'])->name('bills.show');
        Route::get('notifications', [CustomerNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [CustomerNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::put('notifications/{notification}/read', [CustomerNotificationController::class, 'markAsRead'])->name('notifications.read.put');
    });
});

Route::prefix('shop')->name('shop.')->group(function () {
    Route::post('login', [ShopAuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [ShopAuthController::class, 'profile'])->name('profile');
        Route::post('logout', [ShopAuthController::class, 'logout'])->name('logout');
        Route::get('subscription', [ShopAuthController::class, 'subscription'])->name('subscription');
        Route::get('dashboard', [ShopDashboardController::class, 'index'])->name('dashboard');
        Route::get('bills', [ShopBillController::class, 'index'])->name('bills.index');
        Route::post('bills', [ShopBillController::class, 'store'])->name('bills.store');
        Route::get('bills/{bill}', [ShopBillController::class, 'show'])->name('bills.show');
        Route::put('bills/{bill}', [ShopBillController::class, 'update'])->name('bills.update');
        Route::delete('bills/{bill}', [ShopBillController::class, 'destroy'])->name('bills.destroy');
        Route::post('bills/{bill}/status', [ShopBillController::class, 'updateStatus'])->name('bills.status');
        Route::get('items', [ShopProductController::class, 'index'])->name('items.index');
        Route::post('items', [ShopProductController::class, 'store'])->name('items.store');
        Route::put('items/{product}', [ShopProductController::class, 'update'])->name('items.update');
        Route::delete('items/{product}', [ShopProductController::class, 'destroy'])->name('items.destroy');
        Route::get('categories', [ShopProductController::class, 'categories'])->name('categories.index');
        Route::post('categories', [ShopProductController::class, 'storeCategory'])->name('categories.store');
        Route::put('categories/{category}', [ShopProductController::class, 'updateCategory'])->name('categories.update');
        Route::delete('categories/{category}', [ShopProductController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('products', [ShopProductController::class, 'index'])->name('products.index');
        Route::get('product-categories', [ShopProductController::class, 'categories'])->name('product-categories.index');
        Route::get('customers', [ShopDashboardController::class, 'customers'])->name('customers.index');
        Route::get('customers/{customer}', [ShopDashboardController::class, 'customer'])->name('customers.show');
        Route::get('expenses', [ShopExpenseController::class, 'index'])->name('expenses.index');
        Route::post('expenses', [ShopExpenseController::class, 'store'])->name('expenses.store');
        Route::put('expenses/{expense}', [ShopExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ShopExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('expense-categories', [ShopExpenseController::class, 'categories'])->name('expense-categories.index');
        Route::post('expense-categories', [ShopExpenseController::class, 'storeCategory'])->name('expense-categories.store');
        Route::put('expense-categories/{category}', [ShopExpenseController::class, 'updateCategory'])->name('expense-categories.update');
        Route::delete('expense-categories/{category}', [ShopExpenseController::class, 'destroyCategory'])->name('expense-categories.destroy');
        Route::get('reports/sales', [ShopReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/expenses', [ShopReportController::class, 'expenses'])->name('reports.expenses');
        Route::get('reports/profit', [ShopReportController::class, 'profit'])->name('reports.profit');
        Route::get('reports/branches', [ShopReportController::class, 'branches'])->name('reports.branches');
    });
});
