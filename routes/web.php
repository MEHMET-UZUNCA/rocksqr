<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\AdminQrController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/table/{tableNo}', [MenuController::class, 'show'])->name('menu.table');
Route::post('/order/place', [MenuController::class, 'placeOrderPublic'])->name('order.place.public');
Route::post('/order/{tableNo}/place', [MenuController::class, 'placeOrder'])->name('order.place');
Route::post('/waiter-call', [MenuController::class, 'callWaiterPublic'])->name('waiter.call.public');
Route::post('/waiter-call/{tableNo}', [MenuController::class, 'callWaiter'])->name('waiter.call');
Route::get('/order/{order}/success', [MenuController::class, 'orderSuccess'])->name('order.success');

// Kitchen Display System (public - no auth required)
Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen');
Route::get('/kitchen/api/orders', [KitchenController::class, 'apiOrders'])->name('kitchen.api');
Route::patch('/kitchen/orders/{order}/status', [KitchenController::class, 'updateStatus'])->name('kitchen.order.status');
Route::patch('/kitchen/waiter-calls/{waiterCall}/attend', [KitchenController::class, 'attendWaiterCall'])->name('kitchen.waiter.attend');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::patch('products/{product}/toggle', [AdminProductController::class, 'toggle'])->name('products.toggle');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Oracle Settings
    Route::get('oracle-settings', [SettingsController::class, 'oracleIndex'])->name('oracle-settings');
    Route::put('oracle-settings', [SettingsController::class, 'oracleUpdate'])->name('oracle-settings.update');
    Route::post('oracle-settings/test', [SettingsController::class, 'oracleTest'])->name('oracle-settings.test');

    // MSSQL Settings
    Route::get('mssql-settings', [SettingsController::class, 'mssqlIndex'])->name('mssql-settings');
    Route::put('mssql-settings', [SettingsController::class, 'mssqlUpdate'])->name('mssql-settings.update');
    Route::post('mssql-settings/test', [SettingsController::class, 'mssqlTest'])->name('mssql-settings.test');

    // Sync
    Route::get('sync', [SyncController::class, 'index'])->name('sync');
    Route::patch('sync/oracle/{product}', [SyncController::class, 'updateOracleId'])->name('sync.oracle');
    Route::patch('sync/mssql/{product}', [SyncController::class, 'updateMssqlId'])->name('sync.mssql');
    Route::post('sync/preview', [SyncController::class, 'previewBulk'])->name('sync.preview');
    Route::post('sync/bulk-update', [SyncController::class, 'bulkUpdate'])->name('sync.bulk');
    Route::post('sync/fetch-oracle', [SyncController::class, 'fetchOracle'])->name('sync.fetch-oracle');
    Route::post('sync/apply-oracle', [SyncController::class, 'applyOracle'])->name('sync.apply-oracle');
    Route::post('sync/fetch-mssql', [SyncController::class, 'fetchMssql'])->name('sync.fetch-mssql');
    Route::post('sync/apply-mssql', [SyncController::class, 'applyMssql'])->name('sync.apply-mssql');

    // QR Codes
    Route::get('qr-codes', [AdminQrController::class, 'index'])->name('qr-codes.index');
    Route::post('qr-codes/preview', [AdminQrController::class, 'preview'])->name('qr-codes.preview');
    Route::post('qr-codes/download', [AdminQrController::class, 'download'])->name('qr-codes.download');
    Route::post('qr-codes/print', [AdminQrController::class, 'print'])->name('qr-codes.print');
    Route::post('qr-codes/save', [AdminQrController::class, 'save'])->name('qr-codes.save');
    Route::get('qr-codes/archive/{archiveId}/download', [AdminQrController::class, 'archiveDownload'])->name('qr-codes.archive.download');
    Route::get('qr-codes/archive/{archiveId}/print', [AdminQrController::class, 'archivePrint'])->name('qr-codes.archive.print');
});

require __DIR__.'/auth.php';
