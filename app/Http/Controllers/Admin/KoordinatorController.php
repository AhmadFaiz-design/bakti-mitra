<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KontrakPekerjaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KoordinatorController extends Controller
{
    /** Daftar koordinator beserta jumlah pekerjaan dan mitra yang dikelola */
    public function index(Request $request)
    {
        $status = $request->query('status') === 'inactive' ? 'inactive' : 'active';
        $sort   = in_array($request->query('sort'), ['pekerjaan-desc', 'mitra-desc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        // Jumlah pekerjaan = banyaknya uraian pekerjaan yang berbeda.
        // (Satu pekerjaan yang diberikan ke 10 mitra tetap dihitung 1.)
        $hitungPekerjaan = DB::table('kontrak_pekerjaan as kp')
            ->whereColumn('kp.koordinator_id', 'users.id')
            ->selectRaw('COUNT(DISTINCT kp.uraian_pekerjaan)');

        // Jumlah mitra = banyaknya mitra berbeda yang mendapat pekerjaan dari koordinator ini.
        $hitungMitra = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->whereColumn('kp.koordinator_id', 'users.id')
            ->selectRaw('COUNT(DISTINCT k.mitra_id)');

        $query = User::query()
            ->where('role', 'koordinator')
            ->where('is_active', $status === 'active')
            ->select('users.*')
            ->selectSub($hitungPekerjaan, 'jumlah_pekerjaan')
            ->selectSub($hitungMitra, 'jumlah_mitra');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                  ->orWhere('users.username', 'like', "%{$q}%");
            });
        }

        match ($sort) {
            'pekerjaan-desc' => $query->orderByDesc('jumlah_pekerjaan')->orderBy('users.name'),
            'mitra-desc'     => $query->orderByDesc('jumlah_mitra')->orderBy('users.name'),
            default          => $query->orderBy('users.name'),
        };

        $daftarKoordinator = $query->paginate(25)->withQueryString();

        // Daftar nama pekerjaan per koordinator (untuk popover), hanya untuk halaman ini
        $pekerjaanPerKoordinator = KontrakPekerjaan::query()
            ->whereIn('koordinator_id', $daftarKoordinator->pluck('id'))
            ->whereNotNull('uraian_pekerjaan')
            ->select('koordinator_id', 'uraian_pekerjaan')
            ->distinct()
            ->orderBy('uraian_pekerjaan')
            ->get()
            ->groupBy('koordinator_id')
            ->map(fn ($g) => $g->pluck('uraian_pekerjaan')->take(10)->values()->all());

        // Statistik dihitung dari seluruh koordinator, bukan hanya halaman ini
        $total    = User::where('role', 'koordinator')->count();
        $aktif    = User::where('role', 'koordinator')->where('is_active', true)->count();
        $nonaktif = $total - $aktif;

        return view('admin.manajemen-koor', compact(
            'daftarKoordinator', 'pekerjaanPerKoordinator',
            'total', 'aktif', 'nonaktif', 'status', 'sort', 'q'
        ));
    }

    /** Form buat akun koordinator */
    public function create()
    {
        return view('admin.tambah-koor');
    }

    /** Simpan akun koordinator baru */
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

    /** Form edit akun koordinator */
    public function edit(User $koordinator)
    {
        $this->pastikanKoordinator($koordinator);

        return view('admin.edit-koor', compact('koordinator'));
    }

    /** Simpan perubahan akun koordinator */
    public function update(Request $request, User $koordinator)
    {
        $this->pastikanKoordinator($koordinator);

        $data = $request->validate([
            'nama'      => 'required|string|max:150',
            'nip'       => ['required', 'digits:18', Rule::unique('users', 'username')->ignore($koordinator->id)],
            'email'     => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($koordinator->id)],
            'is_active' => 'required|in:0,1',
            'password'  => 'nullable|string|min:8|confirmed',
        ], [
            'nip.digits'         => 'NIP harus 18 digit angka.',
            'nip.unique'         => 'NIP sudah dipakai akun lain.',
            'email.unique'       => 'Email sudah dipakai akun lain.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        try {
            $akun = [
                'name'      => $data['nama'],
                'username'  => $data['nip'],
                'email'     => $data['email'] ?? null,
                'is_active' => (bool) (int) $data['is_active'],
            ];

            // Password hanya diubah jika diisi
            if (!empty($data['password'])) {
                $akun['password'] = $data['password']; // otomatis di-hash oleh model
            }

            $koordinator->update($akun);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['server' => 'Gagal menyimpan perubahan: ' . $e->getMessage()]);
        }

        // Kembali ke tab status yang sesuai, agar baris yang diedit tetap terlihat
        return redirect()
            ->route('admin.datakoor', ['status' => $data['is_active'] ? 'active' : 'inactive'])
            ->with('sukses', 'Data koordinator "' . $data['nama'] . '" berhasil diperbarui.');
    }

    /** Hapus akun koordinator */
    public function destroy(User $koordinator)
    {
        $this->pastikanKoordinator($koordinator);

        $nama   = $koordinator->name;
        $status = $koordinator->is_active ? 'active' : 'inactive';

        // Koordinator yang tercatat pada data pekerjaan tidak boleh dihapus,
        // agar riwayat penugasan tidak kehilangan koordinatornya.
        if (KontrakPekerjaan::where('koordinator_id', $koordinator->id)->exists()) {
            return back()->with(
                'gagal',
                'Akun "' . $nama . '" tidak bisa dihapus karena tercatat sebagai koordinator pada data '
                . 'pekerjaan. Nonaktifkan akunnya sebagai gantinya.'
            );
        }

        try {
            $koordinator->delete();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('gagal', 'Gagal menghapus akun koordinator: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.datakoor', ['status' => $status])
            ->with('sukses', 'Akun koordinator "' . $nama . '" berhasil dihapus.');
    }

    /** Pengaman: hanya akun berperan koordinator yang boleh diubah/dihapus dari sini */
    private function pastikanKoordinator(User $user): void
    {
        abort_unless($user->role === 'koordinator', 404);
    }
}
