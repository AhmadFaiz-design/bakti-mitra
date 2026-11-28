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
use App\Support\BatasHonor;
use Carbon\Carbon;

class MitraController extends Controller
{

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        [$bulan, $bulanTahun, $bulanAngka] = $this->parseBulan($request);

        // ---- Batas atas slider honor (mengikuti bulan terpilih jika ada) ----
        $honorTertinggi = (int) DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->when($bulanTahun && $bulanAngka, fn($q) => $q
                ->where('k.periode_tahun', $bulanTahun)
                ->where('k.periode_bulan', $bulanAngka))
            ->select(DB::raw('SUM(kp.jumlah) as total'))
            ->groupBy('k.mitra_id')
            ->orderByDesc('total')
            ->value('total');

        $honorMaks = max(100000, (int) (ceil($honorTertinggi / 100000) * 100000));
        $status = in_array($request->query('status'), ['tersedia', 'bertugas', 'penuh'], true)
            ? $request->query('status')
            : 'semua';
        $sort = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        $daftarMitra = $this->buildQuery($request)->paginate($perPage)->withQueryString();
        if ($daftarMitra->isEmpty() && $daftarMitra->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftarMitra->lastPage()]));
        }

        // ---- Daftar tugas tiap mitra di halaman ini (mengikuti bulan terpilih jika ada) ----
        $tugasPerMitra = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->whereIn('k.mitra_id', $daftarMitra->pluck('id'))
            ->when($bulanTahun && $bulanAngka, fn($q) => $q
                ->where('k.periode_tahun', $bulanTahun)
                ->where('k.periode_bulan', $bulanAngka))
            ->whereNotNull('kp.uraian_pekerjaan')
            ->select('k.mitra_id', 'kp.uraian_pekerjaan')
            ->distinct()
            ->get()
            ->groupBy('mitra_id')
            ->map(fn($rows) => $rows->pluck('uraian_pekerjaan')->values()->all());

        // ---- Daftar pilihan bulan untuk dropdown ----
        $sekarang   = now();
        $kunciBulan = DB::table('kontrak')
            ->select('periode_tahun', 'periode_bulan')
            ->distinct()
            ->get()
            ->map(fn($r) => sprintf('%04d-%02d', $r->periode_tahun, $r->periode_bulan))
            ->push($sekarang->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $daftarBulan = $kunciBulan->map(function ($k) {
            [$y, $m] = array_map('intval', explode('-', $k));

            return [
                'v' => $k,
                't' => Carbon::create($y, $m, 1)->locale('id')->translatedFormat('F Y'),
            ];
        });

        // ---- Pilihan dropdown filter ----
        $daftarWilayah = Mitra::whereNotNull('wilayah')
            ->distinct()
            ->orderBy('wilayah')
            ->pluck('wilayah');

        $daftarTugas = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->when($bulanTahun && $bulanAngka, fn($q) => $q
                ->where('k.periode_tahun', $bulanTahun)
                ->where('k.periode_bulan', $bulanAngka))
            ->whereNotNull('kp.uraian_pekerjaan')
            ->distinct()
            ->orderBy('kp.uraian_pekerjaan')
            ->pluck('kp.uraian_pekerjaan');

        // ---- Statistik & status ketersediaan (bulan terpilih, atau bulan berjalan jika Semua Bulan) ----
        $acuanTahun = $bulanTahun ?? $sekarang->year;
        $acuanBulan = $bulanAngka ?? $sekarang->month;

        $filterBulanAcuan = fn($q) => $q
            ->where('kontrak.periode_tahun', $acuanTahun)
            ->where('kontrak.periode_bulan', $acuanBulan);

        $peranBulan = $this->peranMitraPadaBulan($acuanTahun, $acuanBulan);
        $total      = Mitra::count();

        $aktifSemua = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->where('users.is_active', true)
            ->select('mitra.id', 'mitra.wilayah')
            ->withSum(['pekerjaan as honor_bulan_ini' => $filterBulanAcuan], 'jumlah')
            ->withCount(['pekerjaan as tugas_bulan_ini' => $filterBulanAcuan])
            ->get();

        $penuh = $bertugas = $hampirPenuh = 0;

        foreach ($aktifSemua as $m) {
            $batas = BatasHonor::untukMitra($m->wilayah, $peranBulan[$m->id] ?? []);
            $honor = (int) $m->honor_bulan_ini;
            $tugas = (int) $m->tugas_bulan_ini;

            if ($batas > 0 && $honor >= $batas) {
                $penuh++;
            } elseif ($tugas > 0) {
                $bertugas++;
                if ($batas > 0 && $honor >= ($batas * 0.8)) {
                    $hampirPenuh++;
                }
            }
        }

        $tersedia    = $aktifSemua->count() - $penuh - $bertugas;
        $jumlahAktif = $aktifSemua->count();
        $persen      = $jumlahAktif > 0
            ? (int) round((($bertugas + $penuh) / $jumlahAktif) * 100)
            : 0;

        // Status per baris mitra pada halaman ini agar badge tabel akurat mengikuti bulan terpilih
        $statusPerMitra = [];
        foreach ($daftarMitra as $m) {
            if (!($m->user?->is_active ?? false)) {
                $statusPerMitra[$m->id] = $m->status_ketersediaan;
                continue;
            }

            $batas = BatasHonor::untukMitra($m->wilayah, $peranBulan[$m->id] ?? []);
            $honor = (int) $m->honor_bulan_ini;
            $tugas = (int) $m->tugas_bulan_ini;

            if ($batas > 0 && $honor >= $batas) {
                $statusPerMitra[$m->id] = ['key' => 'penuh', 'label' => 'Penuh'];
            } elseif ($tugas > 0) {
                $statusPerMitra[$m->id] = ['key' => 'bertugas', 'label' => 'Bertugas'];
            } else {
                $statusPerMitra[$m->id] = ['key' => 'tersedia', 'label' => 'Tersedia'];
            }
        }

        return view('admin.manajemen-mitra', compact(
            'daftarMitra',
            'tugasPerMitra',
            'statusPerMitra',
            'daftarBulan',
            'daftarWilayah',
            'daftarTugas',
            'honorMaks',
            'total',
            'tersedia',
            'bertugas',
            'hampirPenuh',
            'penuh',
            'perPage',
            'persen',
            'bulan',
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
        [$bulan, $bulanTahun, $bulanAngka] = $this->parseBulan($request);

        $status = in_array($request->query('status'), ['tersedia', 'bertugas', 'penuh'], true)
            ? $request->query('status')
            : 'semua';
        $sort   = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        $sekarang   = now();
        $acuanTahun = $bulanTahun ?? $sekarang->year;
        $acuanBulan = $bulanAngka ?? $sekarang->month;

        $filterBulanAcuan = fn($q) => $q
            ->where('kontrak.periode_tahun', $acuanTahun)
            ->where('kontrak.periode_bulan', $acuanBulan);

        $filterHonorDipilih = function ($q) use ($bulanTahun, $bulanAngka) {
            if ($bulanTahun && $bulanAngka) {
                $q->where('kontrak.periode_tahun', $bulanTahun)
                    ->where('kontrak.periode_bulan', $bulanAngka);
            }
        };

        $honorSql = ($bulanTahun && $bulanAngka)
            ? '(select COALESCE(sum(kp.jumlah), 0)
                  from kontrak_pekerjaan kp
                  join kontrak k on k.id = kp.kontrak_id
                  where k.mitra_id = mitra.id
                    and k.periode_tahun = ' . (int) $bulanTahun . '
                    and k.periode_bulan = ' . (int) $bulanAngka . ')'
            : '(select COALESCE(sum(kp.jumlah), 0)
                  from kontrak_pekerjaan kp
                  join kontrak k on k.id = kp.kontrak_id
                  where k.mitra_id = mitra.id)';

        $query = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->select('mitra.*')
            ->with('user')
            ->withSum(['pekerjaan' => $filterHonorDipilih], 'jumlah')
            ->withSum(['pekerjaan as honor_bulan_ini' => $filterBulanAcuan], 'jumlah')
            ->withCount(['pekerjaan as tugas_bulan_ini' => $filterBulanAcuan]);

        // Filter status ketersediaan (mengikuti bulan acuan & batas dinamis per wilayah/peran)
        if ($status !== 'semua') {
            $peranBulan = $this->peranMitraPadaBulan($acuanTahun, $acuanBulan);

            $adaTugasAcuan = fn($s) => $s->select(DB::raw(1))
                ->from('kontrak_pekerjaan as kp')
                ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                ->whereColumn('k.mitra_id', 'mitra.id')
                ->where('k.periode_tahun', $acuanTahun)
                ->where('k.periode_bulan', $acuanBulan);

            $query->where('users.is_active', true);

            if ($status === 'tersedia') {
                $query->whereNotExists($adaTugasAcuan);
            } else {
                // Tentukan ID mitra yang mencapai batas honor pada bulan acuan
                $mitraBertugas = DB::table('mitra')
                    ->join('kontrak as k', 'k.mitra_id', '=', 'mitra.id')
                    ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
                    ->where('k.periode_tahun', $acuanTahun)
                    ->where('k.periode_bulan', $acuanBulan)
                    ->groupBy('mitra.id', 'mitra.wilayah')
                    ->selectRaw('mitra.id, mitra.wilayah, COALESCE(SUM(kp.jumlah), 0) as total_honor')
                    ->get();

                $idPenuh = [];
                foreach ($mitraBertugas as $row) {
                    $batas = BatasHonor::untukMitra($row->wilayah, $peranBulan[$row->id] ?? []);
                    if ($batas > 0 && (int) $row->total_honor >= $batas) {
                        $idPenuh[] = $row->id;
                    }
                }

                if ($status === 'penuh') {
                    if (empty($idPenuh)) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('mitra.id', $idPenuh);
                    }
                } else { // bertugas
                    $query->whereExists($adaTugasAcuan);
                    if (!empty($idPenuh)) {
                        $query->whereNotIn('mitra.id', $idPenuh);
                    }
                }
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

        // Filter tugas (mengikuti bulan terpilih jika ada)
        $tugasDipilih = array_values(array_filter((array) $request->input('tugas', [])));
        if ($tugasDipilih) {
            $query->whereExists(function ($s) use ($tugasDipilih, $bulanTahun, $bulanAngka) {
                $s->select(DB::raw(1))
                    ->from('kontrak_pekerjaan as kp')
                    ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                    ->whereColumn('k.mitra_id', 'mitra.id')
                    ->whereIn('kp.uraian_pekerjaan', $tugasDipilih)
                    ->when($bulanTahun && $bulanAngka, fn($q) => $q
                        ->where('k.periode_tahun', $bulanTahun)
                        ->where('k.periode_bulan', $bulanAngka));
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
        [$bulan, $bulanTahun, $bulanAngka] = $this->parseBulan($request);

        $status = in_array($request->query('status'), ['tersedia', 'bertugas', 'penuh'], true)
            ? $request->query('status')
            : 'semua';
        $format = $request->query('format') === 'pdf' ? 'pdf' : 'csv';

        $daftar   = $this->buildQuery($request)->get();
        $namaFile = 'data-mitra-' . ($bulan !== '' ? $bulan . '-' : '') . now()->format('Ymd-His');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($daftar) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, ['No', 'Nama', 'Sobat ID', 'NIK', 'Wilayah', 'Total Honor', 'Status'], ';', '"', '');

                foreach ($daftar as $i => $m) {
                    fputcsv($out, [
                        $i + 1,
                        $m->user?->name,
                        '="' . $m->user?->username . '"',
                        '="' . $m->nik . '"',
                        $m->wilayah,
                        (int) ($m->pekerjaan_sum_jumlah ?? 0),
                        ($m->user?->is_active ?? false) ? 'Aktif' : 'Tidak Aktif',
                    ], ';', '"', '');
                }
                fclose($out);
            }, $namaFile . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $labelStatus = [
            'tersedia' => 'Tersedia',
            'bertugas' => 'Bertugas',
            'penuh'    => 'Penuh',
            'semua'    => 'Semua',
        ][$status];
        $ringkasan = ['Status: ' . $labelStatus];
        if ($bulanTahun && $bulanAngka) {
            $ringkasan[] = 'Bulan: ' . Carbon::create($bulanTahun, $bulanAngka, 1)->locale('id')->translatedFormat('F Y');
        }
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

    private function parseBulan(Request $request): array
    {
        $bulan = (string) $request->query('bulan', '');
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $bulan, $m)) {
            return [$bulan, (int) $m[1], (int) $m[2]];
        }

        return ['', null, null];
    }

    private function peranMitraPadaBulan(int $tahun, int $bulan): array
    {
        return DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $tahun)
            ->where('k.periode_bulan', $bulan)
            ->whereNotNull('kp.sebagai')
            ->select('k.mitra_id', 'kp.sebagai')
            ->distinct()
            ->get()
            ->groupBy('mitra_id')
            ->map(fn($rows) => $rows->pluck('sebagai')->all())
            ->all();
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
