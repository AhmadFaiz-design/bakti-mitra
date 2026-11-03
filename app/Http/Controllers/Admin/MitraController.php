<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;

class MitraController extends Controller
{
    public function index()
    {
        $daftarMitra = Mitra::with('user')
            ->withSum('pekerjaan', 'jumlah')
            ->get()
            ->sortBy(fn($m) => strtolower($m->user->name))
            ->values();

        $total    = $daftarMitra->count();
        $aktif    = $daftarMitra->filter(fn($m) => $m->user->is_active)->count();
        $nonaktif = $total - $aktif;

        return view('admin.manajemen-mitra', compact('daftarMitra', 'total', 'aktif', 'nonaktif'));
    }
}
