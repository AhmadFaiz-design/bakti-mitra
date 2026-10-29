<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return app(AuthController::class)->redirectByRole();
    }
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::redirect('/admin', '/admin/dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    });
});

Route::middleware(['auth', 'role:koordinator'])->group(function () {
    Route::redirect('/koordinator', '/koordinator/dashboard');

    Route::prefix('koordinator')->name('koordinator.')->group(function () {
        Route::view('/dashboard', 'koordinator.dashboard')->name('dashboard');
    });
});

Route::middleware(['auth', 'role:mitra'])->group(function () {
    Route::redirect('/mitra', '/mitra/dashboard');

    Route::prefix('mitra')->name('mitra.')->group(function () {
        Route::view('/dashboard', 'mitra.dashboard')->name('dashboard');
    });
});
