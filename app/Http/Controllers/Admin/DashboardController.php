<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalMitra = Mitra::count();
        $mitraAktif = Mitra::whereHas('user', fn ($u) => $u->where('is_active', true))->count();

        $totalKoor = User::where('role', 'koordinator')->count();
        $koorAktif = User::where('role', 'koordinator')->where('is_active', true)->count();

        // 5 mitra yang paling baru ditambahkan
        $statusMitra = Mitra::with('user')->latest('id')->take(5)->get();

        return view('admin.dashboard', compact(
            'totalMitra', 'mitraAktif', 'totalKoor', 'koorAktif', 'statusMitra'
        ));
    }
}
