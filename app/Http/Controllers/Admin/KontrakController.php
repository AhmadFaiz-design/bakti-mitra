<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use App\Models\Mitra;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KontrakController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['belum_nomor', 'belum_kontrak', 'menunggu_spj', 'lengkap'], true)
            ? $request->query('status')
            : 'semua';

        // Daftar periode yang memiliki kontrak
        $daftarPeriode = DB::table('kontrak')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('kontrak_pekerjaan as kp')
                    ->whereColumn('kp.kontrak_id', 'kontrak.id');
            })
            ->select('periode_tahun', 'periode_bulan')
            ->distinct()
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->get()
            ->map(function ($r) {
                $key = sprintf('%04d-%02d', $r->periode_tahun, $r->periode_bulan);
                $label = Carbon::create((int) $r->periode_tahun, (int) $r->periode_bulan, 1)
                    ->locale('id')
                    ->translatedFormat('F Y');

                return ['v' => $key, 't' => $label];
            });

        $periode = (string) $request->query('periode', '');
        if ($periode !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $periode = '';
        }

        $query = $this->filterQuery($request, $periode);

        // Ringkasan sesuai filter
        $kontrakIdsSub = (clone $query)->select('kontrak.id');
        $ringkasan = [
            'total_kontrak' => (clone $query)->count(),
            'cek_kontrak'   => (clone $query)->where('cek_kontrak', true)->count(),
            'cek_spj'       => (clone $query)->where('cek_spj', true)->count(),
            'total_honor'   => (int) DB::table('kontrak_pekerjaan')
                ->whereIn('kontrak_id', $kontrakIdsSub)
                ->sum('jumlah'),
        ];

        $daftar = $query
            ->with([
                'mitra:id,user_id,wilayah',
                'mitra.user:id,name,username',
                'pekerjaan' => fn($p) => $p->with('koordinator:id,name')->orderBy('urutan')->orderBy('id'),
            ])
            ->withCount('pekerjaan')
            ->withSum('pekerjaan as total_honor', 'jumlah')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftar->lastPage()]));
        }

        $daftarWilayah = Mitra::whereNotNull('wilayah')
            ->distinct()->orderBy('wilayah')->pluck('wilayah');

        // Informasi saran kode x dan nomor urut y berikutnya per periode untuk modal Generate
        $saranPerPeriode = [];
        foreach ($daftarPeriode as $pItem) {
            [$th, $bl] = array_map('intval', explode('-', $pItem['v']));
            $sudahAda = Kontrak::periode($th, $bl)
                ->whereNotNull('nomor_urut_kontrak')
                ->where('nomor_urut_kontrak', '!=', '')
                ->pluck('nomor_urut_kontrak');

            $kodeX = '';
            $maxY  = 0;

            foreach ($sudahAda as $urut) {
                if (preg_match('/^([^.]+)\.(\d+)$/', trim((string) $urut), $m)) {
                    if ($kodeX === '') {
                        $kodeX = $m[1];
                    }
                    $maxY = max($maxY, (int) $m[2]);
                }
            }

            $saranPerPeriode[$pItem['v']] = [
                'kode_x'      => $kodeX,
                'mulai_y'     => $maxY + 1,
                'total'       => Kontrak::periode($th, $bl)->has('pekerjaan')->count(),
                'belum_nomor' => Kontrak::periode($th, $bl)
                    ->has('pekerjaan')
                    ->where(fn($w) => $w->whereNull('nomor_kontrak')->orWhere('nomor_kontrak', ''))
                    ->count(),
            ];
        }

        $periodeDefaultGenerate = $periode !== ''
            ? $periode
            : ($daftarPeriode->first()['v'] ?? now()->format('Y-m'));

        /* ---------- Tren Total Honor bulan ini vs bulan lalu ---------- */
        $blnIni  = now('Asia/Makassar');
        $blnLalu = $blnIni->copy()->subMonthNoOverflow();

        $honorBulan = fn(Carbon $tgl) => (int) DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $tgl->year)
            ->where('k.periode_bulan', $tgl->month)
            ->sum('kp.jumlah');

        $honorBlnIni  = $honorBulan($blnIni);
        $honorBlnLalu = $honorBulan($blnLalu);

        if ($honorBlnLalu <= 0) {
            $trenHonor = $honorBlnIni <= 0
                ? ['teks' => 'Tetap 0%', 'kelas' => 'neutral']
                : ['teks' => 'Baru bln ini', 'kelas' => 'lime'];
        } else {
            $diff = (int) round((($honorBlnIni - $honorBlnLalu) / $honorBlnLalu) * 100);
            if ($diff > 0) {
                $trenHonor = ['teks' => 'Naik ' . $diff . '%', 'kelas' => 'lime'];
            } elseif ($diff < 0) {
                $trenHonor = ['teks' => 'Turun ' . abs($diff) . '%', 'kelas' => 'warn'];
            } else {
                $trenHonor = ['teks' => 'Tetap 0%', 'kelas' => 'neutral'];
            }
        }

        return view('admin.kontrak', compact(
            'daftar',
            'q',
            'status',
            'periode',
            'ringkasan',
            'trenHonor',
            'daftarPeriode',
            'daftarWilayah',
            'saranPerPeriode',
            'periodeDefaultGenerate',
            'perPage'
        ));
    }

    /** Generate nomor kontrak format B-x.y/74710/PL-300/YYYY secara massal untuk 1 bulan */
    public function generateNomor(Request $request)
    {
        $data = $request->validate([
            'periode'           => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'kode_x'            => 'required|string|max:20',
            'mulai_y'           => 'required|integer|min:1|max:99999',
            'kode_unit'         => 'required|string|max:50',
            'tanggal_spk'       => 'required|date',
            'urut_berdasarkan'  => 'required|in:nama,id',
            'timpa'             => 'nullable|boolean',
        ], [
            'periode.required'     => 'Pilih periode bulan kontrak.',
            'kode_x.required'      => 'Kode bulan (x) wajib diisi.',
            'mulai_y.required'     => 'Nomor urut awal (y) wajib diisi.',
            'kode_unit.required'   => 'Kode unit/klasifikasi wajib diisi.',
            'tanggal_spk.required' => 'Tanggal SPK wajib diisi.',
        ]);

        [$tahun, $bulan] = array_map('intval', explode('-', $data['periode']));
        $kodeX    = trim($data['kode_x']);
        $kodeUnit = trim($data['kode_unit'], " /\t\n\r\0\x0B");
        $mulaiY   = (int) $data['mulai_y'];
        $timpa    = $request->boolean('timpa');

        $kontraks = Kontrak::with('mitra.user:id,name')
            ->periode($tahun, $bulan)
            ->has('pekerjaan')
            ->when(!$timpa, fn($q) => $q->where(fn($w) => $w->whereNull('nomor_kontrak')->orWhere('nomor_kontrak', '')))
            ->where('cek_kontrak', false)
            ->where('cek_spj', false)
            ->get();

        if ($kontraks->isEmpty()) {
            return back()->with('gagal', 'Tidak ada kontrak yang bisa diberi nomor pada periode tersebut (semua sudah bernomor atau sudah diverifikasi).');
        }

        if ($data['urut_berdasarkan'] === 'nama') {
            $kontraks = $kontraks->sortBy(fn($k) => strtolower($k->mitra?->user?->name ?? ''))->values();
        } else {
            $kontraks = $kontraks->sortBy('id')->values();
        }

        $y             = $mulaiY;
        $jumlah        = 0;
        $pertamaNomor  = null;
        $terakhirNomor = null;

        DB::transaction(function () use (
            $kontraks,
            $kodeX,
            $kodeUnit,
            $tahun,
            $data,
            $timpa,
            &$y,
            &$jumlah,
            &$pertamaNomor,
            &$terakhirNomor
        ) {
            // Jika timpa ulang, kosongkan dulu nomor pada kontrak target agar tidak bentrok saat tukar urutan
            if ($timpa) {
                Kontrak::whereIn('id', $kontraks->pluck('id'))->update([
                    'nomor_urut_kontrak' => null,
                    'nomor_kontrak'      => null,
                ]);
            }

            // Ambil semua nomor kontrak yang masih terpakai di tabel agar dilewati otomatis
            $terpakai = DB::table('kontrak')
                ->whereNotNull('nomor_kontrak')
                ->pluck('nomor_kontrak')
                ->flip()
                ->all();

            foreach ($kontraks as $kontrak) {
                do {
                    $calonUrut  = Kontrak::formatNomorUrut($kodeX, $y);
                    $calonNomor = Kontrak::formatNomorKontrak($kodeX, $y, $tahun, $kodeUnit);
                    $y++;
                } while (isset($terpakai[$calonNomor]));

                $terpakai[$calonNomor] = true;

                $kontrak->update([
                    'nomor_urut_kontrak' => $calonUrut,
                    'nomor_kontrak'      => $calonNomor,
                    'tanggal_spk'        => $data['tanggal_spk'],
                ]);

                $pertamaNomor ??= $calonNomor;
                $terakhirNomor = $calonNomor;
                $jumlah++;
            }
        });

        return back()->with(
            'sukses',
            $jumlah . ' kontrak berhasil diberi nomor (' . $pertamaNomor . ' s.d. ' . $terakhirNomor . ').'
        );
    }

    /** Edit manual 1 kontrak (nomor urut, nomor kontrak, tanggal SPK) */
    public function update(Request $request, Kontrak $kontrak)
    {
        if ($kontrak->terkunci()) {
            return back()->with('gagal', 'Kontrak yang sudah diverifikasi tidak bisa diubah. Batalkan centang verifikasi terlebih dahulu.');
        }

        $request->merge([
            'nomor_urut_kontrak' => trim((string) $request->input('nomor_urut_kontrak')),
            'nomor_kontrak'      => trim((string) $request->input('nomor_kontrak')),
        ]);

        $data = $request->validate([
            'nomor_urut_kontrak' => 'required|string|max:50',
            'nomor_kontrak'      => ['required', 'string', 'max:100', Rule::unique('kontrak', 'nomor_kontrak')->ignore($kontrak->id)],
            'tanggal_spk'        => 'required|date',
        ], [
            'nomor_urut_kontrak.required' => 'Nomor urut kontrak wajib diisi.',
            'nomor_kontrak.required'      => 'Nomor kontrak wajib diisi.',
            'nomor_kontrak.unique'        => 'Nomor kontrak tersebut sudah digunakan oleh kontrak lain.',
            'tanggal_spk.required'        => 'Tanggal SPK wajib diisi.',
        ]);

        $kontrak->update([
            'nomor_urut_kontrak' => $data['nomor_urut_kontrak'],
            'nomor_kontrak'      => $data['nomor_kontrak'],
            'tanggal_spk'        => $data['tanggal_spk'],
        ]);

        return back()->with('sukses', 'Nomor kontrak ' . ($kontrak->mitra?->user?->name ?? 'mitra') . ' berhasil diperbarui.');
    }

    /** Toggle cek_kontrak atau cek_spj via fetch */
    public function verifikasi(Request $request, Kontrak $kontrak)
    {
        $data = $request->validate([
            'kolom' => 'required|in:cek_kontrak,cek_spj',
            'nilai' => 'required|boolean',
        ]);

        $kolom = $data['kolom'];
        $nilai = (bool) $data['nilai'];

        if ($kolom === 'cek_kontrak' && $nilai) {
            if (empty($kontrak->nomor_kontrak) || empty($kontrak->tanggal_spk)) {
                return response()->json([
                    'message' => 'Isi atau generate Nomor Kontrak & Tanggal SPK terlebih dahulu sebelum memverifikasi kontrak.',
                ], 422);
            }
        }

        $kontrak->update([$kolom => $nilai]);

        return response()->json([
            'cek_kontrak' => (bool) $kontrak->cek_kontrak,
            'cek_spj'     => (bool) $kontrak->cek_spj,
        ]);
    }

    /** Verifikasi atau batalkan verifikasi massal kontrak dan/atau SPJ */
    public function verifikasiMassal(Request $request)
    {
        $request->validate([
            'mode'      => 'required|in:verifikasi,batal',
            'targets'   => 'required|array|min:1',
            'targets.*' => 'in:kontrak,spj',
            'ids'       => 'nullable|array',
            'ids.*'     => 'integer',
        ], [
            'targets.required' => 'Pilih minimal satu jenis verifikasi (Kontrak atau SPJ).',
            'targets.min'      => 'Pilih minimal satu jenis verifikasi (Kontrak atau SPJ).',
        ]);

        $ids = $request->boolean('semua_hasil')
            ? $this->filterQuery($request, (string) $request->input('periode', ''))->pluck('kontrak.id')
            : collect((array) $request->input('ids', []))->filter()->values();

        if ($ids->isEmpty()) {
            return back()->with('gagal', 'Belum ada kontrak yang dipilih.');
        }

        $nilai   = $request->input('mode') === 'verifikasi';
        $targets = collect($request->input('targets', []))->unique()->values();

        $jumlahKontrak      = 0;
        $jumlahSpj          = 0;
        $dilewatiTanpaNomor = 0;

        DB::transaction(function () use ($ids, $targets, $nilai, &$jumlahKontrak, &$jumlahSpj, &$dilewatiTanpaNomor) {
            if ($targets->contains('kontrak')) {
                if ($nilai) {
                    $dilewatiTanpaNomor = Kontrak::whereIn('id', $ids)
                        ->where('cek_kontrak', false)
                        ->where(fn($w) => $w
                            ->whereNull('nomor_kontrak')
                            ->orWhere('nomor_kontrak', '')
                            ->orWhereNull('tanggal_spk'))
                        ->count();
                }

                $qKontrak = Kontrak::whereIn('id', $ids)->where('cek_kontrak', !$nilai);

                if ($nilai) {
                    $qKontrak->whereNotNull('nomor_kontrak')
                        ->where('nomor_kontrak', '!=', '')
                        ->whereNotNull('tanggal_spk');
                }

                $jumlahKontrak = $qKontrak->update(['cek_kontrak' => $nilai]);
            }

            if ($targets->contains('spj')) {
                $jumlahSpj = Kontrak::whereIn('id', $ids)
                    ->where('cek_spj', !$nilai)
                    ->update(['cek_spj' => $nilai]);
            }
        });

        if ($jumlahKontrak === 0 && $jumlahSpj === 0) {
            return back()->with(
                'gagal',
                $dilewatiTanpaNomor > 0
                    ? 'Tidak ada kontrak yang diverifikasi. Pastikan nomor kontrak dan tanggal SPK sudah terisi.'
                    : 'Tidak ada status yang berubah.'
            );
        }

        $bagian = [];
        if ($jumlahKontrak > 0) {
            $bagian[] = $jumlahKontrak . ' kontrak';
        }
        if ($jumlahSpj > 0) {
            $bagian[] = $jumlahSpj . ' SPJ';
        }

        $pesan = implode(' dan ', $bagian) . ' ' . ($nilai ? 'berhasil diverifikasi.' : 'berhasil dibatalkan verifikasinya.');

        if ($dilewatiTanpaNomor > 0) {
            $pesan .= ' (' . $dilewatiTanpaNomor . ' kontrak dilewati karena belum bernomor/bertanggal SPK).';
        }

        return back()->with('sukses', $pesan);
    }

    /** Download / Cetak PDF SPK resmi untuk 1 kontrak mitra */
    public function cetakSpk(Kontrak $kontrak)
    {
        $kontrak->load([
            'mitra.user',
            'pekerjaan' => fn($q) => $q->with('koordinator:id,name')->orderBy('urutan')->orderBy('id'),
        ]);

        if ($kontrak->pekerjaan->isEmpty()) {
            return back()->with('gagal', 'Kontrak ini belum memiliki rincian pekerjaan.');
        }

        $totalHonor = (int) $kontrak->pekerjaan->sum('jumlah');
        $namaMitra  = $kontrak->mitra?->user?->name ?? 'Mitra';
        $periodeStr = sprintf('%04d-%02d', $kontrak->periode_tahun, $kontrak->periode_bulan);
        $namaFile   = 'SPK-' . preg_replace('/[^A-Za-z0-9\-]/', '_', $namaMitra) . '-' . $periodeStr . '.pdf';

        $html = view('admin.spk-pdf', [
            'kontrak'    => $kontrak,
            'mitra'      => $kontrak->mitra,
            'user'       => $kontrak->mitra?->user,
            'pekerjaan'  => $kontrak->pekerjaan,
            'totalHonor' => $totalHonor,
            'terbilang'  => ucfirst(trim($this->terbilang($totalHonor))) . ' rupiah',
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $namaFile . '"',
        ]);
    }

    /** Export hasil filter kontrak ke CSV atau PDF */
    public function export(Request $request)
    {
        $format  = $request->query('format') === 'pdf' ? 'pdf' : 'csv';
        $status  = in_array($request->query('status'), ['belum_nomor', 'belum_kontrak', 'menunggu_spj', 'lengkap'], true)
            ? $request->query('status')
            : 'semua';

        $periode = (string) $request->query('periode', '');
        if ($periode !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $periode = '';
        }

        $daftar = $this->filterQuery($request, $periode)
            ->with([
                'mitra:id,user_id,wilayah',
                'mitra.user:id,name,username',
            ])
            ->withCount('pekerjaan')
            ->withSum('pekerjaan as total_honor', 'jumlah')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->orderBy('id')
            ->get();

        $namaFile = 'kontrak-mitra-' . now()->format('Ymd-His');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($daftar) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, [
                    'No',
                    'Nama Mitra',
                    'Sobat ID',
                    'Wilayah',
                    'Periode',
                    'Jumlah Tugas',
                    'No. Urut',
                    'Nomor Kontrak',
                    'Tanggal SPK',
                    'Total Honor',
                    'Cek Kontrak',
                    'Cek SPJ',
                ], ';', '"', '');

                foreach ($daftar as $i => $k) {
                    $user = $k->mitra?->user;
                    fputcsv($out, [
                        $i + 1,
                        $user?->name ?? '-',
                        '="' . ($user?->username ?? '-') . '"',
                        $k->mitra?->wilayah ?? '-',
                        $k->periode_label,
                        (int) $k->pekerjaan_count,
                        $k->nomor_urut_kontrak ?: '-',
                        $k->nomor_kontrak ?: 'Belum bernomor',
                        $k->tanggal_spk ? $k->tanggal_spk->format('d/m/Y') : '-',
                        (int) ($k->total_honor ?? 0),
                        $k->cek_kontrak ? 'Terverifikasi' : 'Belum',
                        $k->cek_spj ? 'Lengkap' : 'Belum',
                    ], ';', '"', '');
                }
                fclose($out);
            }, $namaFile . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $statusLabel = [
            'semua'         => 'Semua',
            'belum_nomor'   => 'Belum Bernomor',
            'belum_kontrak' => 'Belum Verif. Kontrak',
            'menunggu_spj'  => 'Menunggu SPJ',
            'lengkap'       => 'Lengkap',
        ][$status];

        $keterangan = ['Status: ' . $statusLabel];
        if (trim((string) $request->query('q')) !== '') {
            $keterangan[] = 'Pencarian: "' . trim((string) $request->query('q')) . '"';
        }
        if ($periode !== '') {
            [$th, $bl] = array_map('intval', explode('-', $periode));
            $keterangan[] = 'Periode: ' . Carbon::create($th, $bl, 1)->locale('id')->translatedFormat('F Y');
        }
        if ($request->filled('wilayah')) {
            $keterangan[] = 'Wilayah: ' . $request->query('wilayah');
        }

        $html = view('admin.export-kontrak', [
            'daftar'     => $daftar,
            'keterangan' => $keterangan,
            'tanggal'    => now()->translatedFormat('d F Y H:i'),
            'totalHonor' => (int) $daftar->sum('total_honor'),
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

    private function filterQuery(Request $request, string $periode): Builder
    {
        $q      = trim((string) $request->input('q', ''));
        $status = $request->input('status');

        $query = Kontrak::query()
            ->has('pekerjaan')
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('nomor_kontrak', 'like', "%{$q}%")
                    ->orWhere('nomor_urut_kontrak', 'like', "%{$q}%")
                    ->orWhereHas('mitra.user', fn($u) => $u
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            }))
            ->when($status === 'belum_nomor', fn($query) => $query
                ->where(fn($w) => $w->whereNull('nomor_kontrak')->orWhere('nomor_kontrak', '')))
            ->when($status === 'belum_kontrak', fn($query) => $query->where('cek_kontrak', false))
            ->when($status === 'menunggu_spj', fn($query) => $query->where('cek_kontrak', true)->where('cek_spj', false))
            ->when($status === 'lengkap', fn($query) => $query->where('cek_kontrak', true)->where('cek_spj', true));

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $periode, $m)) {
            $query->where('periode_tahun', (int) $m[1])
                ->where('periode_bulan', (int) $m[2]);
        }

        if ($request->filled('wilayah')) {
            $query->whereHas('mitra', fn($m) => $m->where('wilayah', $request->input('wilayah')));
        }

        return $query;
    }

    private function terbilang(int $angka): string
    {
        if ($angka === 0) {
            return 'nol';
        }

        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($angka < 12) {
            return $satuan[$angka];
        }
        if ($angka < 20) {
            return $satuan[$angka - 10] . ' belas';
        }
        if ($angka < 100) {
            return $satuan[intdiv($angka, 10)] . ' puluh' . ($angka % 10 ? ' ' . $this->terbilang($angka % 10) : '');
        }
        if ($angka < 200) {
            return 'seratus' . ($angka % 100 ? ' ' . $this->terbilang($angka % 100) : '');
        }
        if ($angka < 1000) {
            return $satuan[intdiv($angka, 100)] . ' ratus' . ($angka % 100 ? ' ' . $this->terbilang($angka % 100) : '');
        }
        if ($angka < 2000) {
            return 'seribu' . ($angka % 1000 ? ' ' . $this->terbilang($angka % 1000) : '');
        }
        if ($angka < 1000000) {
            return $this->terbilang(intdiv($angka, 1000)) . ' ribu' . ($angka % 1000 ? ' ' . $this->terbilang($angka % 1000) : '');
        }
        if ($angka < 1000000000) {
            return $this->terbilang(intdiv($angka, 1000000)) . ' juta' . ($angka % 1000000 ? ' ' . $this->terbilang($angka % 1000000) : '');
        }

        return $this->terbilang(intdiv($angka, 1000000000)) . ' miliar' . ($angka % 1000000000 ? ' ' . $this->terbilang($angka % 1000000000) : '');
    }
}
