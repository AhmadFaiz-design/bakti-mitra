<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class KoordinatorController extends Controller
{
    public function create()
    {
        return view('admin.tambah-koor');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'     => 'required|string|max:150',
            'nip'      => 'required|digits:18|unique:users,username',
            'email'    => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'nip.digits'         => 'NIP harus 18 digit angka.',
            'nip.unique'         => 'NIP sudah terdaftar.',
            'email.unique'       => 'Email sudah dipakai akun lain.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        try {
            User::create([
                'name'      => $data['nama'],
                'username'  => $data['nip'],
                'email'     => $data['email'] ?? null,
                'password'  => $data['password'], // otomatis di-hash oleh model
                'role'      => 'koordinator',
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['server' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.datakoor')
            ->with('sukses', 'Akun koordinator "' . $data['nama'] . '" berhasil ditambahkan.');
    }
}
