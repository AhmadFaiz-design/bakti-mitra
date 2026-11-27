<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use App\Models\Mitra;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KontrakController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $mitra   = Mitra::where('user_id', Auth::id())->first();
        $mitraId = $mitra?->id;

        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['belum', 'verif', 'lengkap'], true)
            ? $request->query('status')
            : 'semua';

        $periode = (string) $request->query('periode', '');
        if ($periode !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $periode = '';
        }

        $query = Kontrak::query()
            ->has('pekerjaan')
            ->when(
                $mitraId !== null,
                fn($query) => $query->where('mitra_id', $mitraId),
                fn($query) => $query->whereRaw('1 = 0')
            )
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('nomor_kontrak', 'like', "%{$q}%")
                    ->orWhere('nomor_urut_kontrak', 'like', "%{$q}%");
            }))
            ->when($status === 'belum', fn($query) => $query->where('cek_kontrak', false))
            ->when($status === 'verif', fn($query) => $query->where('cek_kontrak', true)->where('cek_spj', false))
            ->when($status === 'lengkap', fn($query) => $query->where('cek_kontrak', true)->where('cek_spj', true));

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $periode, $m)) {
            $query->where('periode_tahun', (int) $m[1])
                ->where('periode_bulan', (int) $m[2]);
        }

        $kontrakIdsSub = (clone $query)->select('kontrak.id');
        $ringkasan = [
            'total_kontrak' => (clone $query)->count(),
            'cek_kontrak'   => (clone $query)->where('cek_kontrak', true)->count(),
            'cek_spj'       => (clone $query)->where('cek_spj', true)->count(),
            'total_honor'   => (int) DB::table('kontrak_pekerjaan')
                ->whereIn('kontrak_id', $kontrakIdsSub)
                ->sum('jumlah'),
        ];

        /* ---------- Hari Kontrak & Jumlah Tugas Bulan Sekarang ---------- */
        $tz         = 'Asia/Makassar';
        $sekarang   = now($tz);
        $awalBulan  = $sekarang->copy()->startOfMonth()->startOfDay();
        $akhirBulan = $sekarang->copy()->endOfMonth()->startOfDay();
        $awalStr    = $awalBulan->toDateString();
        $akhirStr   = $akhirBulan->toDateString();

        $hariKontrak   = 0;
        $tugasBulanIni = 0;

        if ($mitraId !== null) {
            $tugasAktifBulan = DB::table('kontrak_pekerjaan as kp')
                ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                ->where('k.mitra_id', $mitraId)
                ->where(function ($w) use ($sekarang, $awalStr, $akhirStr) {
                    $w->where(function ($d) use ($awalStr, $akhirStr) {
                        $d->whereNotNull('kp.tanggal_mulai')
                            ->whereNotNull('kp.tanggal_selesai')
                            ->where('kp.tanggal_mulai', '<=', $akhirStr)
                            ->where('kp.tanggal_selesai', '>=', $awalStr);
                    })->orWhere(function ($p) use ($sekarang) {
                        $p->where(fn($n) => $n->whereNull('kp.tanggal_mulai')->orWhereNull('kp.tanggal_selesai'))
                            ->where('k.periode_tahun', $sekarang->year)
                            ->where('k.periode_bulan', $sekarang->month);
                    });
                })
                ->select('kp.id', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
                ->get();

            $tugasBulanIni = $tugasAktifBulan->count();

            $hariUnik = [];
            foreach ($tugasAktifBulan as $row) {
                if (!$row->tanggal_mulai || !$row->tanggal_selesai) {
                    continue;
                }
                $mulai   = Carbon::parse($row->tanggal_mulai, $tz)->startOfDay();
                $selesai = Carbon::parse($row->tanggal_selesai, $tz)->startOfDay();

                $dari   = $mulai->greaterThan($awalBulan) ? $mulai : $awalBulan->copy();
                $sampai = $selesai->lessThan($akhirBulan) ? $selesai : $akhirBulan->copy();

                for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
                    $hariUnik[$d->toDateString()] = true;
                }
            }
            $hariKontrak = count($hariUnik);
        }

        /* ---------- Tren Total Honor bulan ini vs bulan lalu ---------- */
        $blnIni  = $sekarang;
        $blnLalu = $sekarang->copy()->subMonthNoOverflow();

        $honorBulan = fn(Carbon $tgl) => $mitraId === null
            ? 0
            : (int) DB::table('kontrak as k')
                ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
                ->where('k.mitra_id', $mitraId)
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

        $daftar = $query
            ->with([
                'pekerjaan' => fn($p) => $p->with('koordinator:id,name')->orderBy('urutan')->orderBy('id'),
            ])
            ->withCount('pekerjaan')
            ->withSum('pekerjaan as total_honor', 'jumlah')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->paginate($perPage)
            ->withQueryString();

        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftar->lastPage()]));
        }

        $daftarPeriode = $mitraId !== null
            ? DB::table('kontrak')
                ->where('mitra_id', $mitraId)
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
                })
            : collect();

        return view('mitra.kontrak', compact(
            'daftar',
            'perPage',
            'q',
            'status',
            'periode',
            'ringkasan',
            'hariKontrak',
            'tugasBulanIni',
            'trenHonor',
            'daftarPeriode'
        ));
    }

    public function cetakSpk(Kontrak $kontrak)
    {
        $mitra = Mitra::where('user_id', Auth::id())->first();
        abort_if(!$mitra || (int) $kontrak->mitra_id !== (int) $mitra->id, 403);

        if (!$kontrak->cek_kontrak || empty($kontrak->nomor_kontrak)) {
            return back()->with('gagal', 'SPK dapat diunduh setelah nomor kontrak diisi dan diverifikasi oleh admin.');
        }

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
