<?php

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\DesignReviewController;
use App\Http\Controllers\Admin\PelunasanController;
use App\Http\Controllers\Advertising\SpkController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Desainer\DesignController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\SerahTerimaController;
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
    Route::get('/orders/{order}/detail', [OrderDetailController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/items/{orderItem}', [OrderDetailController::class, 'showItem'])->name('orders.items.show');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}/summary', [OrderController::class, 'summary'])->name('orders.summary');
    Route::get('/orders/{order}/pdf', [OrderController::class, 'pdf'])->name('orders.pdf');
    Route::get('/orders/{order}/pelunasan', [PelunasanController::class, 'create'])->name('orders.pelunasan.create');
    Route::post('/orders/{order}/pelunasan', [PelunasanController::class, 'store'])->name('orders.pelunasan');

    Route::get('/serah-terima', [SerahTerimaController::class, 'index'])->name('serah-terima.index');
    Route::get('/orders/{order}/serah-terima', [SerahTerimaController::class, 'create'])->name('orders.serah-terima.create');
    Route::post('/orders/{order}/serah-terima', [SerahTerimaController::class, 'store'])->name('orders.serah-terima');

    Route::get('/customers/search', [OrderController::class, 'searchCustomers'])->name('customers.search');
    Route::post('/instansis', [OrderController::class, 'storeInstansi'])->name('instansis.store');

    Route::get('/design-reviews', [DesignReviewController::class, 'index'])->name('design-reviews.index');
    Route::post('/design-reviews/{orderItem}/setujui', [DesignReviewController::class, 'setujui'])->name('design-reviews.setujui');
    Route::post('/design-reviews/{orderItem}/revisi', [DesignReviewController::class, 'ajukanRevisi'])->name('design-reviews.revisi');
});

Route::middleware(['auth', 'role:desainer'])->prefix('desainer')->name('desainer.')->group(function () {
    Route::get('/dashboard', [DesignController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat-selesai', [DesignController::class, 'riwayat'])->name('riwayat');
    Route::get('/orders/{order}/detail', [OrderDetailController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/items/{orderItem}', [OrderDetailController::class, 'showItem'])->name('orders.items.show');
    Route::get('/orders/{order}/items', [DesignController::class, 'detailItem'])->name('orders.items');

    Route::get('/serah-terima', [SerahTerimaController::class, 'index'])->name('serah-terima.index');
    Route::get('/orders/{order}/serah-terima', [SerahTerimaController::class, 'create'])->name('orders.serah-terima.create');
    Route::post('/orders/{order}/serah-terima', [SerahTerimaController::class, 'store'])->name('orders.serah-terima');

    Route::post('/orders/{order}/terima', [DesignController::class, 'terimaJob'])->name('orders.terima');
    Route::post('/orders/{order}/finalisasi', [DesignController::class, 'finalisasiBatch'])->name('orders.finalisasi');
    Route::post('/orders/{order}/master', [DesignController::class, 'unggahMaster'])->name('orders.master');
    Route::post('/orders/{order}/preview', [DesignController::class, 'uploadPreview'])->name('orders.preview');
    Route::get('/orders/{order}/preview/{revisi}', [DesignController::class, 'unduhPreview'])->name('orders.preview.download');
    Route::post('/items/{orderItem}/finalisasi', [DesignController::class, 'finalisasi'])->name('items.finalisasi');
    Route::get('/items/{orderItem}/briefing', [DesignController::class, 'unduhBriefing'])->name('items.briefing');
    Route::get('/items/{orderItem}/tiff', [DesignController::class, 'unduhTiff'])->name('items.tiff');
    Route::get('/arsip/{orderItem}/unduh', [DesignController::class, 'unduhMasterItem'])->name('arsip.unduh');
    Route::get('/arsip/order/{order}/unduh', [DesignController::class, 'unduhMaster'])->name('arsip.order.unduh');
});

Route::middleware(['auth', 'role:operator_cetak'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', [ProductionController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat-selesai', [ProductionController::class, 'riwayat'])->name('riwayat');
    Route::get('/orders/{order}/detail', [OrderDetailController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/items/{orderItem}', [OrderDetailController::class, 'showItem'])->name('orders.items.show');

    Route::get('/serah-terima', [SerahTerimaController::class, 'index'])->name('serah-terima.index');
    Route::get('/orders/{order}/serah-terima', [SerahTerimaController::class, 'create'])->name('orders.serah-terima.create');
    Route::post('/orders/{order}/serah-terima', [SerahTerimaController::class, 'store'])->name('orders.serah-terima');

    Route::get('/items/{orderItem}/tiff', [ProductionController::class, 'unduhTiff'])->name('items.tiff');
    Route::post('/items/{orderItem}/mulai', [ProductionController::class, 'mulaiCetak'])->name('items.mulai');
    Route::post('/items/{orderItem}/selesai', [ProductionController::class, 'selesaiCetak'])->name('items.selesai');
    Route::post('/items/{orderItem}/gagal-cetak', [ProductionController::class, 'laporGagalCetak'])->name('items.gagal-cetak');
});

Route::middleware(['auth', 'role:finishing'])->prefix('finishing')->name('finishing.')->group(function () {
    Route::get('/dashboard', [FinishingController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat-selesai', [FinishingController::class, 'riwayat'])->name('riwayat');
    Route::get('/orders/{order}/detail', [OrderDetailController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/items/{orderItem}', [OrderDetailController::class, 'showItem'])->name('orders.items.show');

    Route::get('/serah-terima', [SerahTerimaController::class, 'index'])->name('serah-terima.index');
    Route::get('/orders/{order}/serah-terima', [SerahTerimaController::class, 'create'])->name('orders.serah-terima.create');
    Route::post('/orders/{order}/serah-terima', [SerahTerimaController::class, 'store'])->name('orders.serah-terima');

    Route::post('/items/{orderItem}/konfirmasi', [FinishingController::class, 'konfirmasi'])->name('items.konfirmasi');
});

Route::middleware(['auth', 'role:advertising'])->prefix('advertising')->name('advertising.')->group(function () {
    Route::get('/dashboard', [SpkController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat-selesai', [SpkController::class, 'riwayat'])->name('riwayat');
    Route::get('/orders/{order}/detail', [OrderDetailController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/items/{orderItem}', [OrderDetailController::class, 'showItem'])->name('orders.items.show');

    Route::get('/serah-terima', [SerahTerimaController::class, 'index'])->name('serah-terima.index');
    Route::get('/orders/{order}/serah-terima', [SerahTerimaController::class, 'create'])->name('orders.serah-terima.create');
    Route::post('/orders/{order}/serah-terima', [SerahTerimaController::class, 'store'])->name('orders.serah-terima');

    Route::post('/spks/{spk}/status', [SpkController::class, 'updateStatus'])->name('spks.status');
});

Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/rekap-orders', [OwnerDashboardController::class, 'rekapOrders'])->name('rekap-orders');
    Route::get('/orders/{order}/detail', [OrderDetailController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/items/{orderItem}', [OrderDetailController::class, 'showItem'])->name('orders.items.show');
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
