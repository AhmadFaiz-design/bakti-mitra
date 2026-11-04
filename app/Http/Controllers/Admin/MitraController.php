<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MitraController extends Controller
{

    public function index(Request $request)
    {
        $status = $request->query('status') === 'inactive' ? 'inactive' : 'active';
        $sort   = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        // Subquery total honor per mitra (dipakai untuk filter honor)
        $honorSql = '(select COALESCE(sum(kp.jumlah), 0)
                  from kontrak_pekerjaan kp
                  join kontrak k on k.id = kp.kontrak_id
                  where k.mitra_id = mitra.id)';

        $query = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->select('mitra.*')
            ->with('user')
            ->withSum('pekerjaan', 'jumlah')
            ->where('users.is_active', $status === 'active');

        // ---- Pencarian ----
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.username', 'like', "%{$q}%")
                    ->orWhere('mitra.nik', 'like', "%{$q}%");
            });
        }

        // ---- Filter honor ----
        $parse = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
        $min = $max = null;

        if ($request->input('honor_mode') === 'rentang') {
            if ($request->filled('honor_dari'))   $min = $parse($request->honor_dari);
            if ($request->filled('honor_sampai')) $max = $parse($request->honor_sampai);
        } elseif ($request->filled('honor_jumlah')) {
            $min = $max = $parse($request->honor_jumlah); // nominal: honor sama dengan jumlah
        }

        if ($min !== null) $query->whereRaw("$honorSql >= ?", [$min]);
        if ($max !== null) $query->whereRaw("$honorSql <= ?", [$max]);

        // ---- Filter wilayah ----
        if ($request->filled('wilayah')) {
            $query->where('mitra.wilayah', $request->wilayah);
        }

        // ---- Filter tugas (cocok dengan salah satu tugas yang dipilih) ----
        $tugasDipilih = array_values(array_filter((array) $request->input('tugas', [])));
        if ($tugasDipilih) {
            $query->whereExists(function ($s) use ($tugasDipilih) {
                $s->select(DB::raw(1))
                    ->from('kontrak_pekerjaan as kp')
                    ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                    ->whereColumn('k.mitra_id', 'mitra.id')
                    ->whereIn('kp.uraian_pekerjaan', $tugasDipilih);
            });
        }

        // ---- Urutan ----
        if ($sort === 'honor-desc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) DESC')->orderBy('users.name');
        } elseif ($sort === 'honor-asc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) ASC')->orderBy('users.name');
        } else {
            $query->orderBy('users.name');
        }

        $daftarMitra = $query->paginate(10)->withQueryString();

        // ---- Daftar tugas tiap mitra di halaman ini (untuk popover aksi) ----
        $tugasPerMitra = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->whereIn('k.mitra_id', $daftarMitra->pluck('id'))
            ->whereNotNull('kp.uraian_pekerjaan')
            ->select('k.mitra_id', 'kp.uraian_pekerjaan')
            ->distinct()
            ->get()
            ->groupBy('mitra_id')
            ->map(fn($rows) => $rows->pluck('uraian_pekerjaan')->values()->all());

        // ---- Pilihan dropdown filter ----
        $daftarWilayah = Mitra::whereNotNull('wilayah')
            ->distinct()
            ->orderBy('wilayah')
            ->pluck('wilayah');

        $daftarTugas = DB::table('kontrak_pekerjaan')
            ->whereNotNull('uraian_pekerjaan')
            ->distinct()
            ->orderBy('uraian_pekerjaan')
            ->pluck('uraian_pekerjaan');

        // ---- Statistik (dari seluruh data) ----
        $total    = Mitra::count();
        $aktif    = Mitra::whereHas('user', fn($u) => $u->where('is_active', true))->count();
        $nonaktif = $total - $aktif;

        return view('admin.manajemen-mitra', compact(
            'daftarMitra',
            'tugasPerMitra',
            'daftarWilayah',
            'daftarTugas',
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

    public function edit(Mitra $mitra)
    {
        $mitra->load('user');

        return view('admin.edit-mitra', compact('mitra'));
    }

    public function update(Request $request, Mitra $mitra)
    {
        $mitra->load('user');
        $user = $mitra->user;

        $data = $request->validate([
            'nama'          => 'required|string|max:150',
            'nik'           => ['required', 'digits:16', Rule::unique('mitra', 'nik')->ignore($mitra->id)],
            'pekerjaan'     => 'nullable|string|max:150',
            'jenis_kelamin' => 'nullable|in:Lk,Pr',
            'wilayah'       => 'nullable|string|max:100',
            'alamat'        => 'nullable|string|max:500',
            'sobat_id'      => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email'         => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active'     => 'required|in:0,1',
            'password'      => 'nullable|string|min:8|confirmed',
        ], [
            'nik.digits'         => 'NIK harus 16 digit angka.',
            'nik.unique'         => 'NIK sudah dipakai mitra lain.',
            'sobat_id.unique'    => 'Sobat ID sudah dipakai akun lain.',
            'email.unique'       => 'Email sudah dipakai akun lain.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        try {
            DB::transaction(function () use ($data, $mitra, $user) {
                $akun = [
                    'name'      => $data['nama'],
                    'username'  => $data['sobat_id'],
                    'email'     => $data['email'] ?? null,
                    'is_active' => (bool) (int) $data['is_active'],
                ];

                // Password hanya diubah jika diisi
                if (!empty($data['password'])) {
                    $akun['password'] = $data['password']; // otomatis di-hash oleh model
                }

                $user->update($akun);

                $mitra->update([
                    'nik'           => $data['nik'],
                    'pekerjaan'     => $data['pekerjaan'] ?? null,
                    'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                    'wilayah'       => $data['wilayah'] ?? null,
                    'alamat'        => $data['alamat'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['server' => 'Gagal menyimpan perubahan: ' . $e->getMessage()]);
        }

        // Kembali ke tab status yang sesuai, agar baris yang diedit tetap terlihat
        return redirect()
            ->route('admin.datamitra', ['status' => $data['is_active'] ? 'active' : 'inactive'])
            ->with('sukses', 'Data mitra "' . $data['nama'] . '" berhasil diperbarui.');
    }
}
