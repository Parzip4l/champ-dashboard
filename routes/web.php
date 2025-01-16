<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Produck\ProdukController;
use App\Http\Controllers\General\dashboardController;
use App\Http\Controllers\General\userController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Http;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

require __DIR__ . '/auth.php';

Route::get('/', function () {
    if (Auth::check()) {
        // Jika sudah login, redirect ke dashboard
        return redirect()->route('dashboard.index');
    } else {
        // Jika belum login, redirect ke halaman login
        return redirect()->route('login');
    }
});

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    // Setting
    Route::resource('menu', App\Http\Controllers\Setting\MenuController::class);
        Route::post('/update-status/{id}', [App\Http\Controllers\Setting\MenuController::class, 'updateStatus'])->name('update.status');
    Route::resource('user', App\Http\Controllers\General\userController::class);
    Route::resource('role', App\Http\Controllers\Setting\RoleController::class);

    // Page
    Route::get('dashboard', [dashboardController::class, 'index'])->name('dashboard.index');
    Route::resource('product', App\Http\Controllers\Produck\ProdukController::class);
    Route::resource('distributor', App\Http\Controllers\General\DistributorController::class);
    Route::resource('delivery-order', App\Http\Controllers\General\DeliveryOrder::class);

    // Rnd
    Route::resource('rnd-check', App\Http\Controllers\General\PenetrasiController::class);
    Route::resource('log-riset-grease', App\Http\Controllers\Rnd\RstGreaseController::class);
    Route::delete('/log-riset-grease/detail/{id}', [App\Http\Controllers\Rnd\RstGreaseController::class, 'destroyDetail']);


    // Oli
    Route::get('/pencatatan-oli', [App\Http\Controllers\Produck\OliController::class, 'index'])->name('oli.index');
    Route::delete('/pencatatan-oli/{id}', [App\Http\Controllers\Produck\OliController::class, 'destroy'])->name('oli.destoy');
    Route::post('/update-harga-oli', [App\Http\Controllers\Produck\OliController::class, 'updateHarga'])->name('oli.update_all');
    Route::get('/pencatatan-oli/edit/{id}', [App\Http\Controllers\Produck\OliController::class, 'edit'])->name('oli.edit');
    Route::put('/pencatatan-oli/update/{id}', [App\Http\Controllers\Produck\OliController::class, 'update'])->name('oli.update');
    Route::post('/download-report-oli', [App\Http\Controllers\Produck\OliController::class, 'download'])->name('download.report');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/voltage', [App\Http\Controllers\General\PenetrasiController::class, 'indexVolt'])->name('excavator.volt');
    
});

Route::get('/proxy-api', function () {
    $response = Http::get('http://api.champoil.co.id/index.php'); // Ganti URL API jika diperlukan
    return response()->json($response->json()); // Mengembalikan data dalam format JSON
});
Route::get('/pencatatan-oli/create', [App\Http\Controllers\Produck\OliController::class, 'create'])->name('oli.create');
Route::post('/pencatatan-oli/simpan', [App\Http\Controllers\Produck\OliController::class, 'store'])->name('oli.store');
