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

// Display Screens (public - no auth required)
Route::get('/bar', [KitchenController::class, 'bar'])->name('bar');
Route::get('/bar/api/orders', [KitchenController::class, 'barApiOrders'])->name('bar.api');
Route::patch('/bar/orders/{order}/status', [KitchenController::class, 'barUpdateStatus'])->name('bar.order.status');
Route::post('/bar/symphony/delivered', [KitchenController::class, 'barSymphonyDelivered'])->name('bar.symphony.delivered');
Route::patch('/bar/waiter-calls/{waiterCall}/attend', [KitchenController::class, 'attendWaiterCall'])->name('bar.waiter.attend');

Route::get('/kitchen', [KitchenController::class, 'kitchen'])->name('kitchen');
Route::get('/kitchen/api/orders', [KitchenController::class, 'kitchenApiOrders'])->name('kitchen.api');
Route::get('/kitchen/sse', [KitchenController::class, 'kitchenSse'])->name('kitchen.sse');
Route::patch('/kitchen/orders/{order}/status', [KitchenController::class, 'kitchenUpdateStatus'])->name('kitchen.order.status');

// Symphony POS tabanlı KDS ekranı (Symphony hesapları read-only, QR siparişleri onaylanabilir)
Route::get('/kitchen-pos', [KitchenController::class, 'kitchenPos'])->name('kitchen.pos');
Route::get('/kitchen-pos/api', [KitchenController::class, 'kitchenPosApi'])->name('kitchen.pos.api');
Route::post('/kitchen-pos/complete', [KitchenController::class, 'kitchenPosComplete'])->name('kitchen.pos.complete');
Route::post('/kitchen-pos/uncomplete', [KitchenController::class, 'kitchenPosUncomplete'])->name('kitchen.pos.uncomplete');
Route::patch('/kitchen-pos/qr/{order}/confirm', [KitchenController::class, 'kitchenPosConfirmQr'])->name('kitchen.pos.qr.confirm');
Route::patch('/kitchen-pos/qr/{order}/undo', [KitchenController::class, 'kitchenPosUndoQr'])->name('kitchen.pos.qr.undo');

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

    // MSSQL Settings
    Route::get('mssql-settings', [SettingsController::class, 'mssqlIndex'])->name('mssql-settings');
    Route::put('mssql-settings', [SettingsController::class, 'mssqlUpdate'])->name('mssql-settings.update');
    Route::post('mssql-settings/test', [SettingsController::class, 'mssqlTest'])->name('mssql-settings.test');
    Route::post('mssql-settings/preview', [SettingsController::class, 'mssqlPreview'])->name('mssql-settings.preview');

    // Sync
    Route::get('sync', [SyncController::class, 'index'])->name('sync');
    Route::patch('sync/mssql/{product}', [SyncController::class, 'updateMssqlId'])->name('sync.mssql');
    Route::post('sync/preview', [SyncController::class, 'previewBulk'])->name('sync.preview');
    Route::post('sync/bulk-update', [SyncController::class, 'bulkUpdate'])->name('sync.bulk');
    Route::post('sync/fetch-mssql', [SyncController::class, 'fetchMssql'])->name('sync.fetch-mssql');
    Route::post('sync/apply-mssql', [SyncController::class, 'applyMssql'])->name('sync.apply-mssql');
    Route::post('sync/symphony-fetch', [SyncController::class, 'symphonyFetch'])->name('sync.symphony-fetch');
    Route::post('sync/symphony-import', [SyncController::class, 'symphonyImport'])->name('sync.symphony-import');

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
