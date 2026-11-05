<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\KontrakPekerjaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KoordinatorController extends Controller
{
    /** Daftar koordinator beserta jumlah pekerjaan dan mitra yang dikelola */
    /** Daftar koordinator */
public function index(Request $request)
{
    $status = $request->query('status') === 'inactive' ? 'inactive' : 'active';
    $sort   = in_array($request->query('sort'), ['pekerjaan-desc', 'mitra-desc'])
        ? $request->query('sort')
        : 'name';
    $q = trim((string) $request->query('q', ''));

    $query = User::query()
        ->where('role', 'koordinator')
        ->where('is_active', $status === 'active')
        ->select('users.*')
        // jumlah tugas yang diberikan koordinator ini
        ->selectSub(
            fn ($s) => $s->from('kontrak_pekerjaan as kp')
                ->whereColumn('kp.koordinator_id', 'users.id')
                ->selectRaw('count(distinct kp.uraian_pekerjaan)'),
            'jumlah_pekerjaan'
        )
        // jumlah mitra berbeda yang menerima tugasnya
        ->selectSub(
            fn ($s) => $s->from('kontrak_pekerjaan as kp')
                ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                ->whereColumn('kp.koordinator_id', 'users.id')
                ->selectRaw('count(distinct k.mitra_id)'),
            'jumlah_mitra'
        );

    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('users.name', 'like', "%{$q}%")
                ->orWhere('users.username', 'like', "%{$q}%");
        });
    }

    if ($sort === 'pekerjaan-desc') {
        $query->orderByDesc('jumlah_pekerjaan')->orderBy('users.name');
    } elseif ($sort === 'mitra-desc') {
        $query->orderByDesc('jumlah_mitra')->orderBy('users.name');
    } else {
        $query->orderBy('users.name');
    }

    $daftarKoordinator = $query->paginate(10)->withQueryString();

    // Nama pekerjaan tiap koordinator di halaman ini (untuk popover)
    $pekerjaanPerKoordinator = DB::table('kontrak_pekerjaan')
        ->whereIn('koordinator_id', $daftarKoordinator->pluck('id'))
        ->whereNotNull('uraian_pekerjaan')
        ->select('koordinator_id', 'uraian_pekerjaan')
        ->distinct()
        ->get()
        ->groupBy('koordinator_id')
        ->map(fn ($rows) => $rows->pluck('uraian_pekerjaan')->values()->all());

    // Statistik dari seluruh koordinator
    $total    = User::where('role', 'koordinator')->count();
    $aktif    = User::where('role', 'koordinator')->where('is_active', true)->count();
    $nonaktif = $total - $aktif;

    return view('admin.manajemen-koor', compact(
        'daftarKoordinator',
        'pekerjaanPerKoordinator',
        'total',
        'aktif',
        'nonaktif',
        'status',
        'sort',
        'q'
    ));
}

/** Hapus akun koordinator */
public function destroy(User $koordinator)
{
    if ($koordinator->role !== 'koordinator') {
        abort(404);
    }

    if ($koordinator->id === Auth::id()) {
        return back()->with('gagal', 'Anda tidak bisa menghapus akun Anda sendiri.');
    }

    // Koordinator yang sudah pernah memberi tugas tidak dihapus, agar riwayat tidak kehilangan pemberi tugasnya
    $sudahMemberiTugas = DB::table('kontrak_pekerjaan')
        ->where('koordinator_id', $koordinator->id)
        ->exists();

    if ($sudahMemberiTugas) {
        return back()->with(
            'gagal',
            'Akun "' . $koordinator->name . '" tidak bisa dihapus karena sudah memberi tugas. '
            . 'Nonaktifkan akunnya sebagai gantinya.'
        );
    }

    $nama = $koordinator->name;
    $koordinator->delete();

    return redirect()
        ->route('admin.datakoor')
        ->with('sukses', 'Akun koordinator "' . $nama . '" berhasil dihapus.');
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

    /** Pengaman: hanya akun berperan koordinator yang boleh diubah/dihapus dari sini */
    private function pastikanKoordinator(User $user): void
    {
        abort_unless($user->role === 'koordinator', 404);
    }
}
