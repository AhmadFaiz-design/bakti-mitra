<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MitraController;
use App\Http\Controllers\Admin\KoordinatorController;
use App\Http\Controllers\Admin\MitraImportController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PenugasanController;

/*
 | Halaman awal selalu diarahkan ke /login.
 | Jika pengguna sudah login, AuthController@showLogin meneruskannya
 | ke dashboard sesuai peran (admin, koordinator, mitra).
 */

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/* ========================= ADMIN ========================= */
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::redirect('/admin', '/admin/dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Mitra
        Route::get('/mitra', [MitraController::class, 'index'])->name('datamitra');
        Route::get('/mitra/import', [MitraImportController::class, 'form'])->name('mitra.import');
        Route::post('/mitra/import', [MitraImportController::class, 'proses'])->name('mitra.import.proses');
        Route::get('/tambah-mitra', fn() => view('admin.tambah-mitra'))->name('tambahmitra');
        Route::post('/tambah-mitra', [MitraController::class, 'store'])->name('tambahmitra.simpan');
        Route::get('/exportPDF', [MitraController::class, 'export'])->name('mitra.export');
        Route::get('/mitra/{mitra}/edit', [MitraController::class, 'edit'])
            ->whereNumber('mitra')->name('mitra.edit');
        Route::put('/mitra/{mitra}', [MitraController::class, 'update'])
            ->whereNumber('mitra')->name('mitra.update');
        Route::delete('/mitra/{mitra}', [MitraController::class, 'destroy'])
            ->whereNumber('mitra')->name('mitra.destroy');
        Route::delete('/mitra/hapus-massal', [MitraController::class, 'destroyMassal'])->name('mitra.hapus-massal');

        // Koordinator
        Route::get('/data-koor', [KoordinatorController::class, 'index'])->name('datakoor');
        Route::get('/data-koor/export', [KoordinatorController::class, 'export'])->name('koordinator.export');
        Route::get('/tambah-koor', [KoordinatorController::class, 'create'])->name('tambahkoor');
        Route::post('/tambah-koor', [KoordinatorController::class, 'store'])->name('tambahkoor.simpan');
        Route::delete('/koordinator/{koordinator}', [KoordinatorController::class, 'destroy'])
            ->whereNumber('koordinator')->name('koordinator.destroy');
        Route::get('/koordinator/{koordinator}/edit', [KoordinatorController::class, 'edit'])
            ->whereNumber('koordinator')->name('koordinator.edit');
        Route::put('/koordinator/{koordinator}', [KoordinatorController::class, 'update'])
            ->whereNumber('koordinator')->name('koordinator.update');
        Route::delete('/koordinator/hapus-massal', [KoordinatorController::class, 'destroyMassal'])->name('koordinator.hapus-massal');

        // Penugasan
        Route::get('/penugasan', [PenugasanController::class, 'index'])->name('penugasan');
        Route::get('/penugasan/buat', [PenugasanController::class, 'create'])->name('penugasan.buat');
        Route::post('/penugasan', [PenugasanController::class, 'store'])->name('penugasan.simpan');
        Route::delete('/penugasan/{pekerjaan}', [PenugasanController::class, 'destroy'])
            ->whereNumber('pekerjaan')->name('penugasan.hapus');
        Route::patch('/penugasan/{pekerjaan}/bayar', [PenugasanController::class, 'ubahStatusBayar'])
            ->whereNumber('pekerjaan')->name('penugasan.bayar');
        Route::get('/penugasan/{pekerjaan}/edit', [PenugasanController::class, 'edit'])
            ->whereNumber('pekerjaan')->name('penugasan.edit');
        Route::put('/penugasan/{pekerjaan}', [PenugasanController::class, 'update'])
            ->whereNumber('pekerjaan')->name('penugasan.update');
        Route::get('/penugasan/export', [PenugasanController::class, 'export'])->name('penugasan.export');
        Route::post('/penugasan/bayar-massal', [PenugasanController::class, 'bayarMassal'])->name('penugasan.bayar-massal');
        Route::delete('/penugasan/hapus-massal', [PenugasanController::class, 'hapusMassal'])->name('penugasan.hapus-massal');
    });
});

/* ======================= KOORDINATOR ===================== */
Route::middleware(['auth', 'role:koordinator'])->group(function () {
    Route::redirect('/koordinator', '/koordinator/dashboard');

    Route::prefix('koordinator')->name('koordinator.')->group(function () {
        Route::view('/dashboard', 'koordinator.dashboard')->name('dashboard');
    });
});

/* ========================= MITRA ========================= */
Route::middleware(['auth', 'role:mitra'])->group(function () {
    Route::redirect('/mitra', '/mitra/dashboard');

    Route::prefix('mitra')->name('mitra.')->group(function () {
        Route::view('/dashboard', 'mitra.dashboard')->name('dashboard');
    });
});
