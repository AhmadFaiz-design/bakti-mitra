<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KoordinatorController;
use App\Http\Controllers\Admin\MitraController;
use App\Http\Controllers\Admin\MitraImportController;
use App\Http\Controllers\Admin\PenugasanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Koordinator\DashboardController as KoordinatorDashboardController;
use App\Http\Controllers\Koordinator\PenugasanController as KoordinatorPenugasanController;
use App\Http\Controllers\Mitra\DashboardController as MitraDashboardController;
use App\Http\Controllers\Mitra\TugasController as MitraTugasController;
use Illuminate\Support\Facades\Route;

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

        // Penugasan (Admin)
        Route::get('/penugasan', [PenugasanController::class, 'index'])->name('penugasan');
        Route::get('/penugasan/buat', [PenugasanController::class, 'create'])->name('penugasan.buat');
        Route::post('/penugasan', [PenugasanController::class, 'store'])->name('penugasan.simpan');
        Route::get('/penugasan/{penugasan}/edit', [PenugasanController::class, 'edit'])
            ->whereNumber('penugasan')->name('penugasan.edit');
        Route::put('/penugasan/{penugasan}', [PenugasanController::class, 'update'])
            ->whereNumber('penugasan')->name('penugasan.update');
        Route::delete('/penugasan/{penugasan}', [PenugasanController::class, 'destroy'])
            ->whereNumber('penugasan')->name('penugasan.hapus');
        Route::patch('/penugasan/{penugasan}/bayar', [PenugasanController::class, 'ubahStatusBayar'])
            ->whereNumber('penugasan')->name('penugasan.bayar');

        Route::get('/penugasan/{penugasan}/assign', [PenugasanController::class, 'assignForm'])
            ->whereNumber('penugasan')->name('penugasan.assign');
        Route::post('/penugasan/{penugasan}/assign', [PenugasanController::class, 'assignStore'])
            ->whereNumber('penugasan')->name('penugasan.assign.simpan');
        Route::put('/penugasan/{penugasan}/petugas/{pekerjaan}', [PenugasanController::class, 'updatePetugas'])
            ->whereNumber(['penugasan', 'pekerjaan'])->name('penugasan.petugas.update');
        Route::delete('/penugasan/{penugasan}/petugas/{pekerjaan}', [PenugasanController::class, 'hapusPetugas'])
            ->whereNumber(['penugasan', 'pekerjaan'])->name('penugasan.petugas.hapus');
        Route::patch('/penugasan/pekerjaan/{pekerjaan}/bayar', [PenugasanController::class, 'ubahStatusBayarPekerjaan'])
            ->whereNumber('pekerjaan')->name('penugasan.pekerjaan.bayar');

        Route::get('/penugasan/export', [PenugasanController::class, 'export'])->name('penugasan.export');
        Route::post('/penugasan/bayar-massal', [PenugasanController::class, 'bayarMassal'])->name('penugasan.bayar-massal');
        Route::delete('/penugasan/hapus-massal', [PenugasanController::class, 'hapusMassal'])->name('penugasan.hapus-massal');
    });
});

/* ======================= KOORDINATOR ===================== */
Route::middleware(['auth', 'role:koordinator'])->group(function () {
    Route::redirect('/koordinator', '/koordinator/dashboard');

    Route::prefix('koordinator')->name('koordinator.')->group(function () {
        Route::get('/dashboard', [KoordinatorDashboardController::class, 'index'])->name('dashboard');

        // Penugasan (Koordinator - menggunakan fitur & view bersama)
        Route::get('/mitra', [KoordinatorPenugasanController::class, 'index'])->name('datamitra');
        Route::get('/mitra/buat', [KoordinatorPenugasanController::class, 'create'])->name('penugasan.buat');
        Route::post('/mitra', [KoordinatorPenugasanController::class, 'store'])->name('penugasan.simpan');
        Route::get('/mitra/{penugasan}/edit', [KoordinatorPenugasanController::class, 'edit'])
            ->whereNumber('penugasan')->name('penugasan.edit');
        Route::put('/mitra/{penugasan}', [KoordinatorPenugasanController::class, 'update'])
            ->whereNumber('penugasan')->name('penugasan.update');
        Route::delete('/mitra/{penugasan}', [KoordinatorPenugasanController::class, 'destroy'])
            ->whereNumber('penugasan')->name('penugasan.hapus');
        Route::patch('/mitra/{penugasan}/bayar', [KoordinatorPenugasanController::class, 'ubahStatusBayar'])
            ->whereNumber('penugasan')->name('penugasan.bayar');

        Route::get('/mitra/{penugasan}/assign', [KoordinatorPenugasanController::class, 'assignForm'])
            ->whereNumber('penugasan')->name('penugasan.assign');
        Route::post('/mitra/{penugasan}/assign', [KoordinatorPenugasanController::class, 'assignStore'])
            ->whereNumber('penugasan')->name('penugasan.assign.simpan');
        Route::put('/mitra/{penugasan}/petugas/{pekerjaan}', [KoordinatorPenugasanController::class, 'updatePetugas'])
            ->whereNumber(['penugasan', 'pekerjaan'])->name('penugasan.petugas.update');
        Route::delete('/mitra/{penugasan}/petugas/{pekerjaan}', [KoordinatorPenugasanController::class, 'hapusPetugas'])
            ->whereNumber(['penugasan', 'pekerjaan'])->name('penugasan.petugas.hapus');
        Route::patch('/mitra/pekerjaan/{pekerjaan}/bayar', [KoordinatorPenugasanController::class, 'ubahStatusBayarPekerjaan'])
            ->whereNumber('pekerjaan')->name('penugasan.pekerjaan.bayar');

        Route::get('/mitra/export', [KoordinatorPenugasanController::class, 'export'])->name('penugasan.export');
        Route::post('/mitra/bayar-massal', [KoordinatorPenugasanController::class, 'bayarMassal'])->name('penugasan.bayar-massal');
        Route::delete('/mitra/hapus-massal', [KoordinatorPenugasanController::class, 'hapusMassal'])->name('penugasan.hapus-massal');
    });
});

/* ========================= MITRA ========================= */
Route::middleware(['auth', 'role:mitra'])->group(function () {
    Route::redirect('/mitra', '/mitra/dashboard');

    Route::prefix('mitra')->name('mitra.')->group(function () {
        Route::get('/dashboard', [MitraDashboardController::class, 'index'])->name('dashboard');
        Route::get('/tugas', [MitraTugasController::class, 'index'])->name('tugas');
    });
});
