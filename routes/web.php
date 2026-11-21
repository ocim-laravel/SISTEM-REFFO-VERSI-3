<?php

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Advertising\SpkController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Desainer\DesignController;
use App\Http\Controllers\Finishing\FinishingController;
use App\Http\Controllers\Operator\ProductionController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\MaterialReportController;
use App\Http\Controllers\Owner\ProfitLossController;
use App\Http\Controllers\Owner\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->dashboardRouteName());
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}/summary', [OrderController::class, 'summary'])->name('orders.summary');
    Route::post('/orders/{order}/pelunasan', [OrderController::class, 'pelunasan'])->name('orders.pelunasan');
    Route::post('/orders/{order}/serah-terima', [OrderController::class, 'serahTerima'])->name('orders.serah-terima');

    Route::get('/customers/search', [OrderController::class, 'searchCustomers'])->name('customers.search');
});

Route::middleware(['auth', 'role:desainer'])->prefix('desainer')->name('desainer.')->group(function () {
    Route::get('/dashboard', [DesignController::class, 'dashboard'])->name('dashboard');

    Route::post('/items/{orderItem}/preview', [DesignController::class, 'uploadPreview'])->name('items.preview');
    Route::post('/items/{orderItem}/finalisasi', [DesignController::class, 'finalisasi'])->name('items.finalisasi');
    Route::get('/items/{orderItem}/briefing', [DesignController::class, 'unduhBriefing'])->name('items.briefing');
    Route::get('/items/{orderItem}/preview/{revisi}', [DesignController::class, 'unduhPreview'])->name('items.preview.download');
    Route::get('/arsip/{orderItem}/unduh', [DesignController::class, 'unduhMaster'])->name('arsip.unduh');
});

Route::middleware(['auth', 'role:operator_cetak'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', [ProductionController::class, 'dashboard'])->name('dashboard');

    Route::get('/items/{orderItem}/tiff', [ProductionController::class, 'unduhTiff'])->name('items.tiff');
    Route::post('/items/{orderItem}/mulai', [ProductionController::class, 'mulaiCetak'])->name('items.mulai');
    Route::post('/items/{orderItem}/selesai', [ProductionController::class, 'selesaiCetak'])->name('items.selesai');
    Route::post('/items/{orderItem}/gagal-cetak', [ProductionController::class, 'laporGagalCetak'])->name('items.gagal-cetak');
});

Route::middleware(['auth', 'role:finishing'])->prefix('finishing')->name('finishing.')->group(function () {
    Route::get('/dashboard', [FinishingController::class, 'dashboard'])->name('dashboard');

    Route::post('/items/{orderItem}/konfirmasi', [FinishingController::class, 'konfirmasi'])->name('items.konfirmasi');
});

Route::middleware(['auth', 'role:advertising'])->prefix('advertising')->name('advertising.')->group(function () {
    Route::get('/dashboard', [SpkController::class, 'dashboard'])->name('dashboard');

    Route::post('/spks/{spk}/status', [SpkController::class, 'updateStatus'])->name('spks.status');
});

Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/materials', [MaterialReportController::class, 'index'])->name('reports.materials');
    Route::get('/reports/profit-loss', [ProfitLossController::class, 'index'])->name('reports.profit-loss');

    Route::post('/expenses', [ProfitLossController::class, 'storeExpense'])->name('expenses.store');
    Route::delete('/expenses/{operationalExpense}', [ProfitLossController::class, 'destroyExpense'])->name('expenses.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/products', [SettingsController::class, 'updateProducts'])->name('settings.products');
    Route::post('/settings/rolls', [SettingsController::class, 'storeRoll'])->name('settings.rolls');
    Route::post('/settings/stok-pcs', [SettingsController::class, 'tambahStokPcs'])->name('settings.stok-pcs');
    Route::put('/settings/finishings', [SettingsController::class, 'updateFinishings'])->name('settings.finishings');
});
