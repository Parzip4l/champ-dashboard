<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Produck\ProdukController;
use App\Http\Controllers\General\dashboardController;
use App\Http\Controllers\General\userController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
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

    // Oli
    Route::get('/pencatatan-oli', [App\Http\Controllers\Produck\OliController::class, 'index'])->name('oli.index');
    Route::delete('/pencatatan-oli/{id}', [App\Http\Controllers\Produck\OliController::class, 'destroy'])->name('oli.destoy');
    

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
});

Route::get('/pencatatan-oli/create', [App\Http\Controllers\Produck\OliController::class, 'create'])->name('oli.create');
Route::post('/pencatatan-oli/simpan', [App\Http\Controllers\Produck\OliController::class, 'store'])->name('oli.store');
