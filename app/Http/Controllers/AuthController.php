<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('auth.login'); // sesuaikan dengan lokasi file login Anda
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $berhasil = Auth::attempt([
            'username'  => $request->username,
            'password'  => $request->password,
            'is_active' => true,
        ], $request->boolean('remember'));

        if ($berhasil) {
            $request->session()->regenerate();
            return $this->redirectByRole();
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah, atau akun tidak aktif.'])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function redirectByRole()
    {
        return match (Auth::user()->role) {
            'admin'       => redirect()->route('admin.dashboard'),
            'koordinator' => redirect()->route('koordinator.dashboard'),
            default       => redirect()->route('mitra.dashboard'),
        };
    }
}
