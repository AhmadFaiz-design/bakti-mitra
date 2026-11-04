<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MitraController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status') === 'inactive' ? 'inactive' : 'active';
        $sort   = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        $query = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->select('mitra.*')
            ->with('user')
            ->withSum('pekerjaan', 'jumlah')
            ->where('users.is_active', $status === 'active');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.username', 'like', "%{$q}%")
                    ->orWhere('mitra.nik', 'like', "%{$q}%");
            });
        }

        if ($sort === 'honor-desc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) DESC')->orderBy('users.name');
        } elseif ($sort === 'honor-asc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) ASC')->orderBy('users.name');
        } else {
            $query->orderBy('users.name');
        }

        $daftarMitra = $query->paginate(10)->withQueryString();

        // Statistik dihitung dari seluruh data, bukan hanya halaman ini
        $total    = Mitra::count();
        $aktif    = Mitra::whereHas('user', fn($u) => $u->where('is_active', true))->count();
        $nonaktif = $total - $aktif;

        return view('admin.manajemen-mitra', compact(
            'daftarMitra',
            'total',
            'aktif',
            'nonaktif',
            'status',
            'sort',
            'q'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'          => 'required|string|max:150',
            'nik'           => 'required|digits:16|unique:mitra,nik',
            'pekerjaan'     => 'required|string|max:150',
            'jenis_kelamin' => 'required|in:Lk,Pr',
            'wilayah'       => 'required|string|max:100',
            'alamat'        => 'required|string|max:500',
            'sobat_id'      => 'required|string|max:50|unique:users,username',
            'email'         => 'nullable|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
        ], [
            'nik.digits'         => 'NIK harus 16 digit angka.',
            'nik.unique'         => 'NIK sudah terdaftar.',
            'sobat_id.unique'    => 'Sobat ID sudah terdaftar.',
            'email.unique'       => 'Email sudah dipakai akun lain.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $user = User::create([
                    'name'      => $data['nama'],
                    'username'  => $data['sobat_id'],
                    'email'     => $data['email'] ?? null,
                    'password'  => $data['password'], // otomatis di-hash oleh model
                    'role'      => 'mitra',
                    'is_active' => true,
                ]);

                Mitra::create([
                    'user_id'       => $user->id,
                    'nik'           => $data['nik'],
                    'alamat'        => $data['alamat'],
                    'pekerjaan'     => $data['pekerjaan'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'wilayah'       => $data['wilayah'],
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['server' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.datamitra')
            ->with('sukses', 'Data mitra "' . $data['nama'] . '" berhasil ditambahkan.');
    }

    public function destroy(Mitra $mitra)
    {
        $mitra->load('user');

        $nama   = $mitra->user?->name ?? 'mitra';
        $status = ($mitra->user?->is_active ?? true) ? 'active' : 'inactive';

        // Mitra yang sudah punya kontrak tidak boleh dihapus, agar riwayat honor tidak hilang
        if ($mitra->kontrak()->exists()) {
            return back()->with(
                'gagal',
                'Akun "' . $nama . '" tidak bisa dihapus karena sudah memiliki data kontrak. '
                    . 'Nonaktifkan akunnya sebagai gantinya.'
            );
        }

        try {
            DB::transaction(function () use ($mitra) {
                $user = $mitra->user;
                $mitra->delete();
                $user?->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('gagal', 'Gagal menghapus akun mitra: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.datamitra', ['status' => $status])
            ->with('sukses', 'Akun mitra "' . $nama . '" berhasil dihapus.');
    }
}
