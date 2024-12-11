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
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::resource('product', App\Http\Controllers\Produck\ProdukController::class);
    route::get('dashboard', [dashboardController::class, 'index'])->name('dashboard.index');
    Route::resource('user', App\Http\Controllers\General\userController::class);
});


