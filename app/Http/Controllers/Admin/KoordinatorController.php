<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\KontrakPekerjaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;

class KoordinatorController extends Controller
{
    /** Daftar koordinator beserta jumlah pekerjaan dan mitra yang dikelola */
    /** Daftar koordinator */
public function index(Request $request)
{
    $status = in_array($request->query('status'), ['active', 'inactive'], true)
        ? $request->query('status')
        : 'semua';
    $sort = in_array($request->query('sort'), ['pekerjaan-desc', 'mitra-desc'])
        ? $request->query('sort')
        : 'name';
    $q = trim((string) $request->query('q', ''));

    $daftarKoordinator = $this->buildQuery($request)->paginate(10)->withQueryString();
    if ($daftarKoordinator->isEmpty() && $daftarKoordinator->currentPage() > 1) {
        return redirect()->to($request->fullUrlWithQuery(['page' => $daftarKoordinator->lastPage()]));
    }

    // Nama pekerjaan tiap koordinator di halaman ini (untuk popover)
    $pekerjaanPerKoordinator = DB::table('kontrak_pekerjaan')
        ->whereIn('koordinator_id', $daftarKoordinator->pluck('id'))
        ->whereNotNull('uraian_pekerjaan')
        ->select('koordinator_id', 'uraian_pekerjaan')
        ->distinct()
        ->get()
        ->groupBy('koordinator_id')
        ->map(fn($rows) => $rows->pluck('uraian_pekerjaan')->values()->all());

    // Pilihan dropdown "Pekerjaan" di panel filter
    $daftarTugas = DB::table('kontrak_pekerjaan')
        ->whereNotNull('koordinator_id')
        ->whereNotNull('uraian_pekerjaan')
        ->distinct()
        ->orderBy('uraian_pekerjaan')
        ->pluck('uraian_pekerjaan');

    // Statistik dari seluruh koordinator
    $total    = User::where('role', 'koordinator')->count();
    $aktif    = User::where('role', 'koordinator')->where('is_active', true)->count();
    $nonaktif = $total - $aktif;

    return view('admin.manajemen-koor', compact(
        'daftarKoordinator',
        'pekerjaanPerKoordinator',
        'daftarTugas',
        'total',
        'aktif',
        'nonaktif',
        'status',
        'sort',
        'q'
    ));
}

/**
 * Query koordinator lengkap dengan pencarian, filter, dan urutan.
 * Dipakai oleh index() dan export().
 */
private function buildQuery(Request $request): Builder
{
    $status = in_array($request->query('status'), ['active', 'inactive'], true)
        ? $request->query('status')
        : 'semua';
    $sort = in_array($request->query('sort'), ['pekerjaan-desc', 'mitra-desc'])
        ? $request->query('sort')
        : 'name';
    $q = trim((string) $request->query('q', ''));

    // Jumlah pekerjaan berbeda & jumlah mitra berbeda per koordinator
    $pekerjaanSql = '(select count(distinct kp.uraian_pekerjaan)
                      from kontrak_pekerjaan kp
                      where kp.koordinator_id = users.id)';
    $mitraSql = '(select count(distinct k.mitra_id)
                  from kontrak_pekerjaan kp
                  join kontrak k on k.id = kp.kontrak_id
                  where kp.koordinator_id = users.id)';

    $query = User::query()
        ->where('role', 'koordinator')
        ->select('users.*')
        ->selectRaw("$pekerjaanSql as jumlah_pekerjaan")
        ->selectRaw("$mitraSql as jumlah_mitra");

    if ($status !== 'semua') {
        $query->where('users.is_active', $status === 'active');
    }

    // Pencarian
    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('users.name', 'like', "%{$q}%")
                ->orWhere('users.username', 'like', "%{$q}%");
        });
    }

    // Filter rentang jumlah pekerjaan & jumlah mitra
    $angka = fn($v) => (int) preg_replace('/\D/', '', (string) $v);

    foreach ([['pekerjaan', $pekerjaanSql], ['mitra', $mitraSql]] as [$kunci, $sql]) {
        if ($request->filled($kunci . '_min')) {
            $query->whereRaw("$sql >= ?", [$angka($request->input($kunci . '_min'))]);
        }
        if ($request->filled($kunci . '_max')) {
            $query->whereRaw("$sql <= ?", [$angka($request->input($kunci . '_max'))]);
        }
    }

    // Filter pekerjaan (koordinator yang pernah memberi salah satu pekerjaan terpilih)
    $tugasDipilih = array_values(array_filter((array) $request->input('tugas', [])));
    if ($tugasDipilih) {
        $query->whereExists(function ($s) use ($tugasDipilih) {
            $s->select(DB::raw(1))
                ->from('kontrak_pekerjaan as kp')
                ->whereColumn('kp.koordinator_id', 'users.id')
                ->whereIn('kp.uraian_pekerjaan', $tugasDipilih);
        });
    }

    // Urutan
    if ($sort === 'pekerjaan-desc') {
        $query->orderByDesc('jumlah_pekerjaan')->orderBy('users.name');
    } elseif ($sort === 'mitra-desc') {
        $query->orderByDesc('jumlah_mitra')->orderBy('users.name');
    } else {
        $query->orderBy('users.name');
    }

    return $query;
}

/** Export hasil filter ke CSV atau PDF */
public function export(Request $request)
{
    $format = $request->query('format') === 'pdf' ? 'pdf' : 'csv';
    $status = in_array($request->query('status'), ['active', 'inactive'], true)
        ? $request->query('status')
        : 'semua';

    $daftar   = $this->buildQuery($request)->get();
    $namaFile = 'data-koordinator-' . now()->format('Ymd-His');

    // ================= CSV =================
    if ($format === 'csv') {
        return response()->streamDownload(function () use ($daftar) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($out, ['No', 'Nama', 'NIP / ID', 'Email', 'Jumlah Pekerjaan', 'Jumlah Mitra', 'Status'], ';', '"', '');

            foreach ($daftar as $i => $k) {
                fputcsv($out, [
                    $i + 1,
                    $k->name,
                    '="' . $k->username . '"', // cegah Excel mengubah angka panjang
                    $k->email,
                    (int) $k->jumlah_pekerjaan,
                    (int) $k->jumlah_mitra,
                    $k->is_active ? 'Aktif' : 'Tidak Aktif',
                ], ';', '"', '');
            }
            fclose($out);
        }, $namaFile . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ================= PDF =================
    $ringkasan = ['Status: ' . ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'semua' => 'Semua'][$status]];

    if (trim((string) $request->query('q')) !== '') {
        $ringkasan[] = 'Pencarian: "' . trim($request->query('q')) . '"';
    }

    foreach ([['pekerjaan', 'Jumlah pekerjaan'], ['mitra', 'Jumlah mitra']] as [$kunci, $label]) {
        if ($request->filled($kunci . '_min') || $request->filled($kunci . '_max')) {
            $dari   = $request->filled($kunci . '_min') ? (int) $request->input($kunci . '_min') : 0;
            $sampai = $request->filled($kunci . '_max') ? (int) $request->input($kunci . '_max') : 'maks';
            $ringkasan[] = $label . ': ' . $dari . ' - ' . $sampai;
        }
    }

    $tugasDipilih = array_filter((array) $request->input('tugas', []));
    if ($tugasDipilih) {
        $ringkasan[] = 'Pekerjaan: ' . implode(', ', $tugasDipilih);
    }

    $html = view('admin.export-koor', [
        'daftar'    => $daftar,
        'ringkasan' => $ringkasan,
        'tanggal'   => now()->translatedFormat('d F Y H:i'),
    ])->render();

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);

    $pdf = new Dompdf($options);
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'landscape');
    $pdf->render();

    return response($pdf->output(), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $namaFile . '.pdf"',
    ]);
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
    /** Hapus banyak akun koordinator sekaligus */
    public function destroyMassal(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = collect($request->input('ids'))->map(fn($i) => (int) $i)->all();

        $daftar = User::where('role', 'koordinator')->whereIn('id', $ids)->get();

        // Koordinator yang sudah memberi tugas tidak dihapus, agar riwayat tidak kehilangan pemberi tugasnya
        $punyaTugas = DB::table('kontrak_pekerjaan')
            ->whereIn('koordinator_id', $ids)
            ->distinct()
            ->pluck('koordinator_id')
            ->all();

        $dihapus = 0;
        $dilewati = 0;

        foreach ($daftar as $koordinator) {
            if (in_array($koordinator->id, $punyaTugas)) {
                $dilewati++;
                continue;
            }

            $koordinator->delete();
            $dihapus++;
        }

        if ($dihapus === 0) {
            return back()->with(
                'gagal',
                'Tidak ada akun yang dihapus karena semua koordinator yang dipilih sudah memberi tugas. '
                    . 'Nonaktifkan akunnya sebagai gantinya.'
            );
        }

        $pesan = $dihapus . ' akun koordinator berhasil dihapus.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' akun dilewati karena sudah memberi tugas.';
        }

        return back()->with('sukses', $pesan);
    }
}
