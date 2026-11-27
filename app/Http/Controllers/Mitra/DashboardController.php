<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Support\Nama;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\BatasHonor;

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

        /* ---------- Bulan yang dilihat (?bulan=YYYY-MM) ---------- */
        $param = $request->query('bulan');
        $param = is_string($param) ? $param : '';

        $bulan = $sekarang->copy()->startOfMonth()->startOfDay();
        if (
            preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $param, $mm)
            && (int) $mm[1] >= 2000 && (int) $mm[1] <= 2100
        ) {
            $bulan = Carbon::create((int) $mm[1], (int) $mm[2], 1, 0, 0, 0, $tz);
        }

        $bulanKey        = $bulan->format('Y-m');
        $bulanSebelumnya = $bulan->copy()->subMonth()->format('Y-m');
        $bulanBerikutnya = $bulan->copy()->addMonth()->format('Y-m');
        $awalBulan       = $bulan->copy()->startOfMonth();
        $akhirBulan      = $bulan->copy()->endOfMonth()->startOfDay();
        $awalStr         = $awalBulan->toDateString();
        $akhirStr        = $akhirBulan->toDateString();

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
        $tugasSaya = fn() => DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->when(
                $mitraId !== null,
                fn($q) => $q->where('k.mitra_id', $mitraId),
                fn($q) => $q->whereRaw('1 = 0')
            );

        /* ---------- Daftar bulan untuk dropdown ---------- */
        $kunciBulan = collect();
        for ($i = 1; $i <= 12; $i++) {
            $kunciBulan->push(sprintf('%d-%02d', $sekarang->year, $i));
        }
        $tugasSaya()->select('k.periode_tahun', 'k.periode_bulan')->distinct()->get()
            ->each(fn($r) => $kunciBulan->push(sprintf('%d-%02d', $r->periode_tahun, $r->periode_bulan)));
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
        $persenBayar  = $totalHonor > 0 ? (int) round(($honorDibayar / $totalHonor) * 100) : 0;

        /* ---------- Daftar tugas bulan terpilih ---------- */
        /* ---------- Daftar tugas bulan terpilih ---------- */
        $rowsBulan = $tugasSaya()
            ->leftJoin('users as koor', 'koor.id', '=', 'kp.koordinator_id')
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->select(
                'kp.id',
                'kp.uraian_pekerjaan',
                'kp.sebagai',
                'kp.volume',
                'kp.satuan',
                'kp.jumlah',
                'kp.tanggal_mulai',
                'kp.tanggal_selesai',
                'kp.honor_dibayar',
                'kp.created_at',
                'koor.name as koordinator_nama'
            )
            ->orderByDesc('kp.id')
            ->get();

        // Hitung batas honor dinamis (Kendari/Konkep & Pendata/Pengolah, ambil yang tertinggi jika >1 peran)
        $batasHonor = BatasHonor::untukMitra(
            $mitra?->wilayah,
            $rowsBulan->pluck('sebagai')->filter()->unique()->values()->all()
        );
        $persenBatas = $batasHonor > 0 ? min(100, (int) round(($totalHonor / $batasHonor) * 100)) : 0;

        /* ---------- Grafik Progres Honor per Tugas (filter berdasarkan waktu tugas) ---------- */
        $progres = $rowsBulan->map(function ($r) use ($hariIni) {
            $judul = $r->uraian_pekerjaan ?: '(tanpa uraian)';
            $honor = (int) ($r->jumlah ?? 0);

            if ($r->tanggal_selesai && $r->tanggal_selesai < $hariIni) {
                $waktu      = 'selesai';
                $waktuLabel = 'Selesai';
            } elseif ($r->tanggal_mulai && $r->tanggal_mulai > $hariIni) {
                $waktu      = 'akan';
                $waktuLabel = 'Akan Datang';
            } else {
                $waktu      = 'berlangsung';
                $waktuLabel = 'Berlangsung';
            }

            return [
                'id'           => $r->id,
                'nama'         => $judul,
                'label'        => Nama::pendek($judul),
                'waktu'        => $waktu,
                'status_waktu' => $waktuLabel,
                'total'        => $honor,
            ];
        })->values()->all();

        /* ---------- Kalender (Senin - Minggu) ---------- */
        $totalPekerjaan = (int) $tugasSaya()
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->count();

        $calStart = $bulan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $calEnd   = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $rowsKalender = $tugasSaya()
            ->whereNotNull('kp.tanggal_mulai')
            ->whereNotNull('kp.tanggal_selesai')
            ->where('kp.tanggal_mulai', '<=', $calEnd->toDateString())
            ->where('kp.tanggal_selesai', '>=', $calStart->toDateString())
            ->select('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->get();

        $formatRentang = function (Carbon $mulai, Carbon $selesai) {
            if ($mulai->year === $selesai->year && $mulai->month === $selesai->month) {
                return $mulai->day . ' sd. ' . $selesai->copy()->locale('id')->translatedFormat('j F');
            }
            if ($mulai->year === $selesai->year) {
                return $mulai->copy()->locale('id')->translatedFormat('j M')
                    . ' sd. ' . $selesai->copy()->locale('id')->translatedFormat('j M');
            }

            return $mulai->copy()->locale('id')->translatedFormat('j M Y')
                . ' sd. ' . $selesai->copy()->locale('id')->translatedFormat('j M Y');
        };

        $tugas = [];
        foreach ($rowsKalender as $g) {
            $mulai   = Carbon::parse($g->tanggal_mulai, $tz)->startOfDay();
            $selesai = Carbon::parse($g->tanggal_selesai, $tz)->startOfDay();
            $rentang = $formatRentang($mulai, $selesai);

            $dari   = $mulai->greaterThan($calStart) ? $mulai : $calStart->copy();
            $sampai = $selesai->lessThan($calEnd) ? $selesai : $calEnd->copy();

            for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
                $sisa  = (int) $d->diffInDays($selesai, false);
                $badge = null;
                if ($sisa === 0) {
                    $badge = 'Hari Ini';
                } elseif ($sisa > 0 && $sisa <= 7) {
                    $badge = $sisa . ' Hari Lagi';
                }

                $tugas[$d->toDateString()][] = [
                    'judul'    => $g->uraian_pekerjaan ?: '(tanpa uraian)',
                    'rentang'  => $rentang,
                    'deadline' => $d->isSameDay($selesai),
                    'badge'    => $badge,
                ];
            }
        }
        $tugas = collect($tugas)
            ->map(fn($items) => collect($items)->sortByDesc('deadline')->values()->all())
            ->all();

        /* ---------- Daftar Tugas (aktif/mendatang) & Riwayat Tugas (selesai) ---------- */
        $semuaTugasBulan = $tugasSaya()
            ->whereNotNull('kp.uraian_pekerjaan')
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->select('kp.id', 'kp.uraian_pekerjaan as judul', 'kp.tanggal_mulai as mulai', 'kp.tanggal_selesai as selesai')
            ->get();

        $hariIniC = Carbon::parse($hariIni, $tz)->startOfDay();

        $daftarTugas = $semuaTugasBulan
            ->filter(fn($t) => ! $t->selesai || $t->selesai >= $hariIni)
            ->sortBy(fn($t) => $t->selesai ?: '9999-12-31')
            ->map(function ($t) use ($tz, $hariIniC) {
                if (! $t->selesai) {
                    return [
                        'judul'         => $t->judul,
                        'tanggal_label' => '-',
                        'badge_teks'    => 'Berlangsung',
                        'badge_kelas'   => 'bertugas',
                    ];
                }

                $selesai = Carbon::parse($t->selesai, $tz)->startOfDay();
                $sisa    = (int) $hariIniC->diffInDays($selesai, false);

                return [
                    'judul'         => $t->judul,
                    'tanggal_label' => $selesai->copy()->locale('id')->translatedFormat('j F'),
                    'badge_teks'    => $sisa === 0 ? 'Hari Ini' : $sisa . ' Hari Lagi',
                    'badge_kelas'   => $sisa <= 7 ? 'warn' : 'bertugas',
                ];
            })
            ->values();

        $riwayatTugas = $semuaTugasBulan
            ->filter(fn($t) => $t->selesai && $t->selesai < $hariIni)
            ->sortByDesc('selesai')
            ->map(function ($t) use ($tz) {
                $selesai = Carbon::parse($t->selesai, $tz)->startOfDay();

                return [
                    'judul'         => $t->judul,
                    'tanggal_label' => $selesai->copy()->locale('id')->translatedFormat('j F'),
                ];
            })
            ->values();

        /* ---------- Perlu Perhatian ---------- */
        $telat = (int) $tugasSaya()
            ->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
            ->where('kp.tanggal_selesai', '<', $hariIni)
            ->where(fn($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
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
                'level'  => 'merah',
                'jumlah' => ($batasHonor > 0 && $totalHonor >= $batasHonor) ? 1 : 0,
            ],
            [
                'judul'  => $deadlineDekat . ' Tugas',
                'ket'    => 'Mendekati waktu deadline',
                'level'  => 'kuning',
                'jumlah' => $deadlineDekat,
            ],
            [
                'judul'  => 'Mendekati Batas Honor',
                'ket'    => 'Honor bulan ini sudah ' . $persenBatas . '% dari batas maksimal',
                'level'  => 'kuning',
                'jumlah' => ($batasHonor > 0 && $totalHonor < $batasHonor && $totalHonor >= $batasHonor * 0.8) ? 1 : 0,
            ],
            [
                'judul'  => $telat . ' Tugas',
                'ket'    => 'Lewat deadline, honor belum dibayar',
                'level'  => 'merah',
                'jumlah' => $telat,
            ],
            [
                'judul'  => 'Kontrak Bulan Ini',
                'ket'    => 'Menunggu verifikasi kontrak oleh admin',
                'level'  => 'ungu',
                'jumlah' => $belumVerifKontrak,
            ],
        ])->filter(fn($p) => $p['jumlah'] > 0)
            ->map(fn($p) => $p + ['url' => null])
            ->values();

        /* ---------- Notifikasi (popover lonceng) ---------- */
        $tugasBaru = $rowsBulan->take(4)->map(fn($t) => [
            'judul' => 'Tugas Baru Diterima',
            'ket'   => ($t->uraian_pekerjaan ?: '(tanpa uraian)') . ($t->koordinator_nama ? ' · ' . $t->koordinator_nama : ''),
            'level' => 'info',
            'ikon'  => 'tugas',
            'url'   => route('mitra.tugas'),
            'waktu' => $t->created_at ? Carbon::parse($t->created_at)->locale('id')->diffForHumans() : null,
        ]);

        $notifikasi = $perhatian->map(fn($p) => [
            'judul' => $p['judul'],
            'ket'   => $p['ket'],
            'level' => $p['level'],
            'ikon'  => 'warn',
            'url'   => $p['url'],
            'waktu' => null,
        ])->concat($tugasBaru)->values();

        $notifPenting = $perhatian->count();

        return view('mitra.dashboard', compact(
            'persenBayar',
            'totalHonor',
            'batasHonor',
            'persenBatas',
            'totalPekerjaan',
            'tugas',
            'progres',
            'daftarTugas',
            'riwayatTugas',
            'perhatian',
            'notifikasi',
            'notifPenting',
            'bulan',
            'bulanKey',
            'bulanSebelumnya',
            'bulanBerikutnya',
            'labelBulan',
            'opsiBulan',
            'hariIni',
            'hariDipilih',
            'calStart',
            'calEnd'
        ));
    }
}
