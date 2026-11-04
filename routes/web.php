<?php

use App\Http\Controllers\Admin\MitraController;
use App\Http\Controllers\Admin\MitraImportController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

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
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/mitra', [MitraController::class, 'index'])->name('datamitra');
        Route::get('/mitra/import', [MitraImportController::class, 'form'])->name('mitra.import');
        Route::post('/mitra/import', [MitraImportController::class, 'proses'])->name('mitra.import.proses');
        Route::view('/data-koor', 'admin.manajemen-koor')->name('datakoor');
        Route::view('/tambah-mitra', 'admin.tambahmitra')->name('tambahmitra');
        Route::post('/tambah-mitra', [MitraController::class, 'store'])->name('tambahmitra.simpan');
        Route::delete('/mitra/{mitra}', [MitraController::class, 'destroy'])
            ->whereNumber('mitra')
            ->name('mitra.destroy');
        Route::get('/mitra/{mitra}/edit', [MitraController::class, 'edit'])
            ->whereNumber('mitra')
            ->name('mitra.edit');
        Route::put('/mitra/{mitra}', [MitraController::class, 'update'])
            ->whereNumber('mitra')
            ->name('mitra.update');
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

Route::get('/admin/data-mitra', function () {
    return view('admin.manajemen-mitra');
})->name('admin.datamitra');

Route::get('/admin/data-koor', function () {
    return view('admin.manajemen-koor');
})->name('admin.datakoor');

Route::get('/admin/tambah-mitra', function () {
    return view('admin.tambahmitra');
})->name('admin.tambahmitra');

Route::get('/cek-php', function () {
    return [
        'versi_php' => PHP_VERSION,
        'file_ini'  => php_ini_loaded_file(),
        'zip_aktif' => extension_loaded('zip'),
    ];
});
Route::get('/admin/exportPDF', [MitraController::class, 'export'])->name('admin.mitra.export');
