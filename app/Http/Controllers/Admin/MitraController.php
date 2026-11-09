<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;

class MitraController extends Controller
{

    public function index(Request $request)
    {
        // ---- Batas atas slider honor (total honor tertinggi, dibulatkan ke atas per Rp100.000) ----
        $honorTertinggi = (int) DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->select(DB::raw('SUM(kp.jumlah) as total'))
            ->groupBy('k.mitra_id')
            ->orderByDesc('total')
            ->value('total');

        $honorMaks = max(100000, (int) (ceil($honorTertinggi / 100000) * 100000));
        $status = in_array($request->query('status'), ['tersedia', 'bertugas', 'penuh'], true)
            ? $request->query('status')
            : 'semua';
        $sort   = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        $daftarMitra = $this->buildQuery($request)->paginate(10)->withQueryString();
        if ($daftarMitra->isEmpty() && $daftarMitra->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftarMitra->lastPage()]));
        }

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
        // ---- Statistik (dari seluruh data) ----
        $total = Mitra::count();

        // Aturan sama persis dengan accessor status_ketersediaan (bulan berjalan, akun aktif)
        $bulanIni = now();
        $filterBulanIni = fn($q) => $q
            ->where('kontrak.periode_tahun', $bulanIni->year)
            ->where('kontrak.periode_bulan', $bulanIni->month);

        $batas = (int) config('mitra.batas_honor');

        $aktifSemua = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->where('users.is_active', true)
            ->select('mitra.id')
            ->withSum(['pekerjaan as honor_bulan_ini' => $filterBulanIni], 'jumlah')
            ->withCount(['pekerjaan as tugas_bulan_ini' => $filterBulanIni])
            ->get();

        $penuh    = $aktifSemua->filter(fn($m) => (int) $m->honor_bulan_ini >= $batas)->count();
        $bertugas = $aktifSemua->filter(fn($m) => (int) $m->honor_bulan_ini < $batas && (int) $m->tugas_bulan_ini > 0)->count();
        $tersedia = $aktifSemua->count() - $penuh - $bertugas;

        $jumlahAktif = $aktifSemua->count();
        $persen = $jumlahAktif > 0
            ? (int) round((($bertugas + $penuh) / $jumlahAktif) * 100)
            : 0;

        return view('admin.manajemen-mitra', compact(
            'daftarMitra',
            'tugasPerMitra',
            'daftarWilayah',
            'daftarTugas',
            'total',
            'tersedia',
            'bertugas',
            'penuh',
            'persen',
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
            ->route('admin.datamitra')
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
            ->route('admin.datamitra')
            ->with('sukses', 'Data mitra "' . $data['nama'] . '" berhasil diperbarui.');
    }

    /**
     * Query daftar mitra lengkap dengan pencarian, filter, dan urutan.
     * Dipakai oleh index() dan export().
     */
    private function buildQuery(Request $request): Builder
    {
        $status = in_array($request->query('status'), ['tersedia', 'bertugas', 'penuh'], true)
            ? $request->query('status')
            : 'semua';
        $sort   = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        $honorSql = '(select COALESCE(sum(kp.jumlah), 0)
                  from kontrak_pekerjaan kp
                  join kontrak k on k.id = kp.kontrak_id
                  where k.mitra_id = mitra.id)';

        $bulanIni = now();
        $filterBulanIni = fn($q) => $q
            ->where('kontrak.periode_tahun', $bulanIni->year)
            ->where('kontrak.periode_bulan', $bulanIni->month);

        $query = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->select('mitra.*')
            ->with('user')
            ->withSum('pekerjaan', 'jumlah') // tetap, untuk kolom Total Honor, filter, dan sort
            ->withSum(['pekerjaan as honor_bulan_ini' => $filterBulanIni], 'jumlah')
            ->withCount(['pekerjaan as tugas_bulan_ini' => $filterBulanIni]);

        // Filter status ketersediaan (aturannya sama dengan accessor status_ketersediaan)
        if ($status !== 'semua') {
            $batas = (int) config('mitra.batas_honor');

            $honorBulanSql = '(select COALESCE(sum(kp.jumlah), 0)
                      from kontrak_pekerjaan kp
                      join kontrak k on k.id = kp.kontrak_id
                      where k.mitra_id = mitra.id
                        and k.periode_tahun = ? and k.periode_bulan = ?)';

            $adaTugasBulanIni = fn($s) => $s->select(DB::raw(1))
                ->from('kontrak_pekerjaan as kp')
                ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                ->whereColumn('k.mitra_id', 'mitra.id')
                ->where('k.periode_tahun', $bulanIni->year)
                ->where('k.periode_bulan', $bulanIni->month);

            // Status ini hanya berlaku untuk akun aktif
            $query->where('users.is_active', true);

            if ($status === 'penuh') {
                $query->whereRaw("$honorBulanSql >= ?", [$bulanIni->year, $bulanIni->month, $batas]);
            } elseif ($status === 'bertugas') {
                $query->whereRaw("$honorBulanSql < ?", [$bulanIni->year, $bulanIni->month, $batas])
                    ->whereExists($adaTugasBulanIni);
            } else { // tersedia
                $query->whereNotExists($adaTugasBulanIni);
            }
        }

        // Pencarian
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.username', 'like', "%{$q}%")
                    ->orWhere('mitra.nik', 'like', "%{$q}%");
            });
        }

        // Filter honor
        $parse = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
        $min = $max = null;

        if ($request->input('honor_mode') === 'rentang') {
            if ($request->filled('honor_dari'))   $min = $parse($request->honor_dari);
            if ($request->filled('honor_sampai')) $max = $parse($request->honor_sampai);
        } elseif ($request->filled('honor_jumlah')) {
            $min = $max = $parse($request->honor_jumlah);
        }

        if ($min !== null) $query->whereRaw("$honorSql >= ?", [$min]);
        if ($max !== null) $query->whereRaw("$honorSql <= ?", [$max]);

        // Filter wilayah
        if ($request->filled('wilayah')) {
            $query->where('mitra.wilayah', $request->wilayah);
        }

        // Filter tugas
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

        // Urutan
        if ($sort === 'honor-desc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) DESC')->orderBy('users.name');
        } elseif ($sort === 'honor-asc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) ASC')->orderBy('users.name');
        } else {
            $query->orderBy('users.name');
        }

        return $query;
    }

    public function export(Request $request)
    {
        $status = in_array($request->query('status'), ['tersedia', 'bertugas', 'penuh'], true)
            ? $request->query('status')
            : 'semua';
        $format = $request->query('format') === 'pdf' ? 'pdf' : 'csv';

        $daftar   = $this->buildQuery($request)->get();
        $namaFile = 'data-mitra-' . now()->format('Ymd-His');

        // ================= CSV =================
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($daftar) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

                fputcsv($out, ['No', 'Nama', 'Sobat ID', 'NIK', 'Wilayah', 'Total Honor', 'Status'], ';', '"', '');

                foreach ($daftar as $i => $m) {
                    fputcsv($out, [
                        $i + 1,
                        $m->user?->name,
                        '="' . $m->user?->username . '"', // cegah Excel mengubah angka panjang
                        '="' . $m->nik . '"',
                        $m->wilayah,
                        (int) ($m->pekerjaan_sum_jumlah ?? 0),
                        ($m->user?->is_active ?? false) ? 'Aktif' : 'Tidak Aktif',
                    ], ';', '"', '');
                }
                fclose($out);
            }, $namaFile . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // ================= PDF =================
        $labelStatus = [
            'tersedia' => 'Tersedia',
            'bertugas' => 'Bertugas',
            'penuh'    => 'Penuh',
            'semua'    => 'Semua',
        ][$status];
        $ringkasan = ['Status: ' . $labelStatus];
        if (trim((string) $request->query('q')) !== '') {
            $ringkasan[] = 'Pencarian: "' . trim($request->query('q')) . '"';
        }
        if ($request->filled('wilayah')) {
            $ringkasan[] = 'Wilayah: ' . $request->wilayah;
        }
        if ($request->input('honor_mode') === 'rentang' && ($request->filled('honor_dari') || $request->filled('honor_sampai'))) {
            $ringkasan[] = 'Honor: Rp ' . number_format((int) preg_replace('/\D/', '', (string) $request->honor_dari), 0, ',', '.')
                . ' - Rp ' . number_format((int) preg_replace('/\D/', '', (string) $request->honor_sampai), 0, ',', '.');
        } elseif ($request->filled('honor_jumlah')) {
            $ringkasan[] = 'Honor: Rp ' . number_format((int) preg_replace('/\D/', '', (string) $request->honor_jumlah), 0, ',', '.');
        }
        $tugasDipilih = array_filter((array) $request->input('tugas', []));
        if ($tugasDipilih) {
            $ringkasan[] = 'Tugas: ' . implode(', ', $tugasDipilih);
        }

        $html = view('admin.export-mitra', [
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

    /** Hapus banyak akun mitra sekaligus */
    public function destroyMassal(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = collect($request->input('ids'))->map(fn($i) => (int) $i)->all();

        $daftar = Mitra::with('user')->whereIn('id', $ids)->get();

        // Mitra yang sudah punya kontrak tidak dihapus, agar riwayat honor tidak hilang
        $punyaKontrak = DB::table('kontrak')
            ->whereIn('mitra_id', $ids)
            ->distinct()
            ->pluck('mitra_id')
            ->all();

        $dihapus = 0;
        $dilewati = 0;

        foreach ($daftar as $mitra) {
            if (in_array($mitra->id, $punyaKontrak)) {
                $dilewati++;
                continue;
            }

            try {
                DB::transaction(function () use ($mitra) {
                    $user = $mitra->user;
                    $mitra->delete();
                    $user?->delete();
                });
                $dihapus++;
            } catch (\Throwable $e) {
                report($e);
                $dilewati++;
            }
        }

        if ($dihapus === 0) {
            return back()->with(
                'gagal',
                'Tidak ada akun yang dihapus karena semua mitra yang dipilih sudah memiliki data kontrak. '
                    . 'Nonaktifkan akunnya sebagai gantinya.'
            );
        }

        $pesan = $dihapus . ' akun mitra berhasil dihapus.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' akun dilewati karena sudah memiliki data kontrak.';
        }

        return back()->with('sukses', $pesan);
    }
}
