<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Support\Nama;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId     = Auth::id();
        $mitra      = Mitra::where('user_id', $userId)->first();
        $mitraId    = $mitra?->id;
        $tz         = 'Asia/Makassar';
        $sekarang   = now($tz);
        $hariIni    = $sekarang->toDateString();
        $batasHonor = (int) config('mitra.batas_honor', config('penugasan.batas_honor', 3500000));

        /* ---------- Bulan yang dilihat (?bulan=YYYY-MM) ---------- */
        $param = $request->query('bulan');
        $param = is_string($param) ? $param : '';

        $bulan = $sekarang->copy()->startOfMonth()->startOfDay();
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $param, $mm)
            && (int) $mm[1] >= 2000 && (int) $mm[1] <= 2100) {
            $bulan = Carbon::create((int) $mm[1], (int) $mm[2], 1, 0, 0, 0, $tz);
        }

        $bulanKey   = $bulan->format('Y-m');
        $awalBulan  = $bulan->copy()->startOfMonth();
        $akhirBulan = $bulan->copy()->endOfMonth()->startOfDay();
        $awalStr    = $awalBulan->toDateString();
        $akhirStr   = $akhirBulan->toDateString();

        $bulanIni   = $bulanKey === $sekarang->format('Y-m');
        $bulanLewat = ! $bulanIni && $akhirBulan->lt($sekarang->copy()->startOfDay());

        if ($bulanIni) {
            $acuan       = $hariIni;
            $hariDipilih = $hariIni;
        } elseif ($bulanLewat) {
            $acuan       = $akhirStr;
            $hariDipilih = $awalStr;
        } else {
            $acuan       = $awalStr;
            $hariDipilih = $awalStr;
        }

        /* ---------- Query dasar: HANYA tugas milik mitra yang login ---------- */
        $tugasSaya = fn () => DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->when(
                $mitraId !== null,
                fn ($q) => $q->where('k.mitra_id', $mitraId),
                fn ($q) => $q->whereRaw('1 = 0')
            );

        /* ---------- Daftar bulan untuk dropdown ---------- */
        $kunciBulan = collect();
        for ($i = 1; $i <= 12; $i++) {
            $kunciBulan->push(sprintf('%d-%02d', $sekarang->year, $i));
        }
        $tugasSaya()->select('k.periode_tahun', 'k.periode_bulan')->distinct()->get()
            ->each(fn ($r) => $kunciBulan->push(sprintf('%d-%02d', $r->periode_tahun, $r->periode_bulan)));
        $kunciBulan->push($bulanKey);

        $opsiBulan = $kunciBulan->unique()->sort()->values()->map(function ($k) use ($tz, $sekarang) {
            [$y, $m] = array_map('intval', explode('-', $k));
            $d = Carbon::create($y, $m, 1, 0, 0, 0, $tz)->locale('id');

            return ['k' => $k, 't' => $d->translatedFormat('F') . ($y !== $sekarang->year ? ' ' . $y : '')];
        });
        $labelBulan = $opsiBulan->firstWhere('k', $bulanKey)['t']
            ?? $bulan->copy()->locale('id')->translatedFormat('F');

        /* ---------- Ringkasan honor & pembayaran bulan terpilih ---------- */
        $bayar = $tugasSaya()
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->selectRaw('coalesce(sum(kp.jumlah), 0) as total,
                         coalesce(sum(case when kp.honor_dibayar = 1 then kp.jumlah else 0 end), 0) as dibayar')
            ->first();

        $totalHonor   = (int) ($bayar->total ?? 0);
        $honorDibayar = (int) ($bayar->dibayar ?? 0);
        $honorBelum   = max(0, $totalHonor - $honorDibayar);
        $persenBayar  = $totalHonor > 0 ? (int) round(($honorDibayar / $totalHonor) * 100) : 0;
        $persenBatas  = $batasHonor > 0 ? min(100, (int) round(($totalHonor / $batasHonor) * 100)) : 0;
        $sisaKuota    = $batasHonor > 0 ? max(0, $batasHonor - $totalHonor) : 0;

        /* ---------- Daftar tugas bulan terpilih ---------- */
        $rowsBulan = $tugasSaya()
            ->leftJoin('users as koor', 'koor.id', '=', 'kp.koordinator_id')
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->select(
                'kp.id',
                'kp.uraian_pekerjaan',
                'kp.ro',
                'kp.volume',
                'kp.satuan',
                'kp.harga_satuan',
                'kp.jumlah',
                'kp.tanggal_mulai',
                'kp.tanggal_selesai',
                'kp.honor_dibayar',
                'kp.dibayar_pada',
                'kp.created_at',
                'koor.name as koordinator_nama'
            )
            ->orderByDesc('kp.id')
            ->get();

        /* ---------- Grafik Honor per Tugas ---------- */
        $progres = $rowsBulan->map(function ($r) {
            $judul = $r->uraian_pekerjaan ?: '(tanpa uraian)';
            $vol   = rtrim(rtrim(number_format((float) $r->volume, 2, ',', '.'), '0'), ',') ?: '0';
            $honor = (int) ($r->jumlah ?? 0);
            $lunas = (bool) $r->honor_dibayar;

            return [
                'id'      => $r->id,
                'nama'    => $judul,
                'label'   => Nama::pendek($judul),
                'status'  => $lunas ? 'lunas' : 'belum',
                'total'   => $honor,
                'selesai' => $lunas ? $honor : 0,
                'volume'  => $vol . ' ' . $r->satuan,
            ];
        })->values()->all();

        /* ---------- Kalender & ringkasan pekerjaan ---------- */
        $kalender = [
            'deadline'    => (int) $tugasSaya()->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])->count(),
            'selesai'     => (int) $tugasSaya()->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
                                ->where('kp.tanggal_selesai', '<', $hariIni)->count(),
            'berlangsung' => (int) $tugasSaya()->where('kp.tanggal_mulai', '<=', $acuan)
                                ->where('kp.tanggal_selesai', '>=', $acuan)->count(),
        ];

        $totalPekerjaan = (int) $tugasSaya()
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->count();

        $calStart = $bulan->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $calEnd   = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY)->startOfDay();

        $rowsKalender = $tugasSaya()
            ->leftJoin('users as koor', 'koor.id', '=', 'kp.koordinator_id')
            ->whereNotNull('kp.tanggal_mulai')
            ->whereNotNull('kp.tanggal_selesai')
            ->where('kp.tanggal_mulai', '<=', $calEnd->toDateString())
            ->where('kp.tanggal_selesai', '>=', $calStart->toDateString())
            ->select('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai', 'koor.name as koordinator_nama')
            ->get();

        $tugas = [];
        foreach ($rowsKalender as $g) {
            $mulai   = Carbon::parse($g->tanggal_mulai, $tz)->startOfDay();
            $selesai = Carbon::parse($g->tanggal_selesai, $tz)->startOfDay();

            $dari   = $mulai->greaterThan($calStart) ? $mulai : $calStart->copy();
            $sampai = $selesai->lessThan($calEnd) ? $selesai : $calEnd->copy();

            for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
                $tugas[$d->toDateString()][] = [
                    'judul'       => $g->uraian_pekerjaan ?: '(tanpa uraian)',
                    'deadline'    => $d->isSameDay($selesai),
                    'koordinator' => $g->koordinator_nama ? Nama::pendek($g->koordinator_nama) : null,
                ];
            }
        }
        $tugas = collect($tugas)
            ->map(fn ($items) => collect($items)->sortByDesc('deadline')->values()->all())
            ->all();

        /* ---------- Kartu Status Pekerjaan & Jadwal Tugas ---------- */
        $formatTgl = fn ($d, $denganTahun = false) => Carbon::parse($d, $tz)->locale('id')
            ->translatedFormat($denganTahun ? 'j M Y' : 'j M');

        $daftarTugas = $rowsBulan->map(function ($r) {
            $vol = rtrim(rtrim(number_format((float) $r->volume, 2, ',', '.'), '0'), ',') ?: '0';

            return [
                'judul'       => $r->uraian_pekerjaan ?: '(tanpa uraian)',
                'koordinator' => $r->koordinator_nama ?: '-',
                'volume'      => $vol . ' ' . $r->satuan,
                'honor'       => (int) ($r->jumlah ?? 0),
                'dibayar'     => (bool) $r->honor_dibayar,
            ];
        })->values();

        $aktivitas = $tugasSaya()
            ->leftJoin('users as koor', 'koor.id', '=', 'kp.koordinator_id')
            ->whereNotNull('kp.uraian_pekerjaan')
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->select(
                'kp.id',
                'kp.uraian_pekerjaan as judul',
                'kp.tanggal_mulai as mulai',
                'kp.tanggal_selesai as selesai',
                'kp.volume',
                'kp.satuan',
                'koor.name as koordinator'
            )
            ->orderByDesc('kp.id')
            ->limit(20)
            ->get()
            ->map(function ($a) use ($formatTgl, $hariIni) {
                $bedaTahun = $a->mulai && $a->selesai
                    && substr((string) $a->mulai, 0, 4) !== substr((string) $a->selesai, 0, 4);

                $a->periode = ($a->mulai ? $formatTgl($a->mulai, $bedaTahun) : '-')
                    . ' - ' . ($a->selesai ? $formatTgl($a->selesai, $bedaTahun) : '-');

                $vol = rtrim(rtrim(number_format((float) $a->volume, 2, ',', '.'), '0'), ',') ?: '0';
                $a->volume_teks = $vol . ' ' . $a->satuan;

                if ($a->selesai && $a->selesai < $hariIni) {
                    $a->status_waktu = 'Selesai';
                } elseif ($a->mulai && $a->mulai > $hariIni) {
                    $a->status_waktu = 'Akan Datang';
                } else {
                    $a->status_waktu = 'Berlangsung';
                }

                return $a;
            });

        /* ---------- Perlu Perhatian ---------- */
        $telat = (int) $tugasSaya()
            ->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
            ->where('kp.tanggal_selesai', '<', $hariIni)
            ->where(fn ($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
            ->count();

        $deadlineDekat = $bulanLewat ? 0 : (int) $tugasSaya()
            ->whereBetween('kp.tanggal_selesai', [$acuan, Carbon::parse($acuan, $tz)->addDays(7)->toDateString()])
            ->count();

        $kontrakBulan = $mitraId
            ? DB::table('kontrak')
                ->where('mitra_id', $mitraId)
                ->where('periode_tahun', $bulan->year)
                ->where('periode_bulan', $bulan->month)
                ->first()
            : null;

        $belumVerifKontrak = ($rowsBulan->isNotEmpty() && $kontrakBulan && ! $kontrakBulan->cek_kontrak) ? 1 : 0;

        $perhatian = collect([
            [
                'judul'  => 'Batas Honor Tercapai',
                'ket'    => 'Total honor bulan ini mencapai batas maksimal Rp ' . number_format($batasHonor, 0, ',', '.'),
                'tag'    => 'Honor',
                'level'  => 'merah',
                'jumlah' => ($batasHonor > 0 && $totalHonor >= $batasHonor) ? 1 : 0,
            ],
            [
                'judul'  => $deadlineDekat . ' Pekerjaan',
                'ket'    => 'Berakhir dalam waktu dekat (7 hari)',
                'tag'    => 'Deadline',
                'level'  => 'kuning',
                'jumlah' => $deadlineDekat,
            ],
            [
                'judul'  => 'Mendekati Batas Honor',
                'ket'    => 'Honor bulan ini sudah ' . $persenBatas . '% dari batas maksimal',
                'tag'    => 'Honor',
                'level'  => 'kuning',
                'jumlah' => ($batasHonor > 0 && $totalHonor < $batasHonor && $totalHonor >= $batasHonor * 0.8) ? 1 : 0,
            ],
            [
                'judul'  => $telat . ' Tugas',
                'ket'    => 'Lewat deadline, honor belum dibayar',
                'tag'    => 'Pembayaran',
                'level'  => 'merah',
                'jumlah' => $telat,
            ],
            [
                'judul'  => 'Kontrak Bulan Ini',
                'ket'    => 'Menunggu verifikasi kontrak oleh admin',
                'tag'    => 'Kontrak',
                'level'  => 'kuning',
                'jumlah' => $belumVerifKontrak,
            ],
        ])->filter(fn ($p) => $p['jumlah'] > 0)
            ->map(fn ($p) => $p + ['url' => null])
            ->values();

        /* ---------- Komposisi Honor & Kuota Bulanan ---------- */
        $komposisiHonor = collect([
            (object) ['label' => 'Sudah Dibayar', 'jumlah' => $honorDibayar],
            (object) ['label' => 'Belum Dibayar', 'jumlah' => $honorBelum],
            (object) ['label' => 'Sisa Kuota',    'jumlah' => $sisaKuota],
        ])->filter(fn ($item) => $item->jumlah > 0)->values();

        $totalKomposisi = (int) $komposisiHonor->sum('jumlah');

        /* ---------- Notifikasi (popover lonceng) ---------- */
        $tugasBaru = $rowsBulan->take(4)->map(fn ($t) => [
            'judul' => 'Tugas Baru Diterima',
            'ket'   => ($t->uraian_pekerjaan ?: '(tanpa uraian)') . ($t->koordinator_nama ? ' · ' . $t->koordinator_nama : ''),
            'level' => 'info',
            'ikon'  => 'tugas',
            'url'   => null,
            'waktu' => $t->created_at ? Carbon::parse($t->created_at)->locale('id')->diffForHumans() : null,
        ]);

        $notifikasi = $perhatian->map(fn ($p) => [
            'judul' => $p['judul'],
            'ket'   => $p['ket'],
            'level' => $p['level'],
            'ikon'  => 'warn',
            'url'   => $p['url'],
            'waktu' => null,
        ])->concat($tugasBaru)->values();

        $notifPenting = $perhatian->count();

        return view('mitra.dashboard', compact(
            'persenBayar', 'totalHonor', 'honorDibayar', 'honorBelum', 'batasHonor', 'persenBatas', 'sisaKuota',
            'totalPekerjaan', 'kalender', 'tugas', 'progres', 'daftarTugas', 'aktivitas',
            'perhatian', 'komposisiHonor', 'totalKomposisi', 'notifikasi', 'notifPenting',
            'bulan', 'bulanKey', 'labelBulan', 'opsiBulan', 'hariIni', 'hariDipilih', 'calStart', 'calEnd'
        ));
    }
}
