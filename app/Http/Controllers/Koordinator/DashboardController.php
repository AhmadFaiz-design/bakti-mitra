<?php

namespace App\Http\Controllers\Koordinator;

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
        $me       = Auth::id();
        $tz       = 'Asia/Makassar';
        $sekarang = now($tz);
        $hariIni  = $sekarang->toDateString();

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
            $acuan = $hariIni;
            $hariDipilih = $hariIni;
        } elseif ($bulanLewat) {
            $acuan = $akhirStr;
            $hariDipilih = $awalStr;
        } else {
            $acuan = $awalStr;
            $hariDipilih = $awalStr;
        }

        /* ---------- Query dasar: HANYA tugas milik koordinator yang login ---------- */
        $milikku = fn () => DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->where('kp.koordinator_id', $me);

        $kunci  = "concat_ws('|', kp.uraian_pekerjaan, kp.tanggal_mulai, kp.tanggal_selesai)";
        $hitung = fn () => $milikku()->selectRaw("count(distinct $kunci) as n");

        // Mitra binaan: semua mitra yang pernah diberi tugas oleh koordinator ini
        $idBinaan = $milikku()->distinct()->pluck('k.mitra_id')->all();

        /* ---------- Daftar bulan untuk dropdown ---------- */
        $kunciBulan = collect();
        for ($i = 1; $i <= 12; $i++) {
            $kunciBulan->push(sprintf('%d-%02d', $sekarang->year, $i));
        }
        $milikku()->select('k.periode_tahun', 'k.periode_bulan')->distinct()->get()
            ->each(fn ($r) => $kunciBulan->push(sprintf('%d-%02d', $r->periode_tahun, $r->periode_bulan)));
        $kunciBulan->push($bulanKey);

        $opsiBulan = $kunciBulan->unique()->sort()->values()->map(function ($k) use ($tz, $sekarang) {
            [$y, $m] = array_map('intval', explode('-', $k));
            $d = Carbon::create($y, $m, 1, 0, 0, 0, $tz)->locale('id');

            return ['k' => $k, 't' => $d->translatedFormat('F') . ($y !== $sekarang->year ? ' ' . $y : '')];
        });
        $labelBulan = $opsiBulan->firstWhere('k', $bulanKey)['t']
            ?? $bulan->copy()->locale('id')->translatedFormat('F');

        /* ---------- Progres pembayaran (tugas saya, bulan terpilih) ---------- */
        $bayar = $milikku()
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->selectRaw('coalesce(sum(kp.jumlah), 0) as total,
                         coalesce(sum(case when kp.honor_dibayar = 1 then kp.jumlah else 0 end), 0) as dibayar')
            ->first();
        $persenBayar = (int) $bayar->total > 0 ? (int) round($bayar->dibayar / $bayar->total * 100) : 0;

        /* ---------- Honor total tiap mitra bulan ini (untuk status & batas honor) ---------- */
        $honorBulan = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->whereIn('k.mitra_id', $idBinaan)
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->groupBy('k.mitra_id')
            ->selectRaw('k.mitra_id, coalesce(sum(kp.jumlah), 0) as honor')
            ->get()
            ->keyBy('mitra_id');

        $peranBulan = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->whereIn('k.mitra_id', $idBinaan)
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->whereNotNull('kp.sebagai')
            ->select('k.mitra_id', 'kp.sebagai')
            ->distinct()
            ->get()
            ->groupBy('mitra_id')
            ->map(fn ($rows) => $rows->pluck('sebagai')->all());

        /* ---------- Volume tugas SAYA per mitra (untuk grafik) ---------- */
        $volumeku = $milikku()
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->groupBy('k.mitra_id')
            ->selectRaw(
                'k.mitra_id,
                 coalesce(sum(kp.volume), 0) as volume,
                 coalesce(sum(case when kp.tanggal_selesai < ? then kp.volume else 0 end), 0) as volume_selesai',
                [$hariIni]
            )
            ->get()
            ->keyBy('mitra_id');

        /* ---------- Mitra binaan yang aktif ---------- */
        $mitraBinaan = Mitra::with('user:id,name,username')
            ->select('id', 'user_id', 'wilayah')
            ->whereIn('id', $idBinaan)
            ->whereHas('user', fn ($u) => $u->where('is_active', true))
            ->get();

        $tersedia    = collect();
        $progres     = [];
        $mitraPenuh  = 0;
        $hampirPenuh = 0;

foreach ($mitraBinaan as $m) {
            $batas = BatasHonor::untukMitra($m->wilayah, $peranBulan->get($m->id, []));
            $honor = (int) ($honorBulan->get($m->id)->honor ?? 0);
            $penuh = $batas > 0 && $honor >= $batas;
            $nama  = Nama::rapikan($m->user?->name);
            $v     = $volumeku->get($m->id);

            if ($penuh) {
                $mitraPenuh++;
            } elseif ($batas > 0 && $honor >= $batas * 0.8) {
                $hampirPenuh++;
            }

            if (! $penuh) {
                $tersedia->push([
                    'nama'    => $nama,
                    'sobat'   => $m->user?->username ?? '-',
                    'wilayah' => $m->wilayah ?: '-',
                    'honor'   => $honor,
                    'status'  => $honor > 0 ? 'Bertugas' : 'Tersedia',
                ]);
            }

            // Grafik: hanya mitra yang punya tugas dari saya pada bulan ini
            if ($v && (float) $v->volume > 0) {
                $progres[] = [
                    'id'      => $m->id,
                    'nama'    => $nama,
                    'label'   => Nama::pendek($nama),
                    'status'  => $penuh ? 'penuh' : 'bertugas',
                    'total'   => round((float) $v->volume, 2),
                    'selesai' => round((float) $v->volume_selesai, 2),
                ];
            }
        }

        $totalMitra    = count($idBinaan);
        $mitraTersedia = $tersedia->count();

        // Status Mitra: yang bertugas dulu, sisanya yang tersedia (maksimal 4)
        $bertugasList = $tersedia->where('status', 'Bertugas')
            ->sortBy([['honor', 'desc'], ['nama', 'asc']])->values();
        $tersediaList = $tersedia->where('status', 'Tersedia')->sortBy('nama')->values();
        $statusMitra  = $bertugasList->concat($tersediaList)->take(4)->values();

        usort($progres, fn ($a, $b) => $b['total'] <=> $a['total']);
        $progres = array_slice($progres, 0, 60);

        /* ---------- Kalender & ringkasan (tugas saya) ---------- */
        $kalender = [
            'deadline'    => (int) $hitung()->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])->value('n'),
            'selesai'     => (int) $hitung()->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
                                ->where('kp.tanggal_selesai', '<', $hariIni)->value('n'),
            'berlangsung' => (int) $hitung()->where('kp.tanggal_mulai', '<=', $acuan)
                                ->where('kp.tanggal_selesai', '>=', $acuan)->value('n'),
        ];

        $totalPekerjaan = (int) $hitung()
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->value('n');

        $calStart = $bulan->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $calEnd   = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY)->startOfDay();

        $grup = $milikku()
            ->whereNotNull('kp.tanggal_mulai')
            ->whereNotNull('kp.tanggal_selesai')
            ->where('kp.tanggal_mulai', '<=', $calEnd->toDateString())
            ->where('kp.tanggal_selesai', '>=', $calStart->toDateString())
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan, kp.tanggal_mulai, kp.tanggal_selesai, count(distinct k.mitra_id) as mitra')
            ->get();

        $tugas = [];
        foreach ($grup as $g) {
            $mulai   = Carbon::parse($g->tanggal_mulai, $tz)->startOfDay();
            $selesai = Carbon::parse($g->tanggal_selesai, $tz)->startOfDay();

            $dari   = $mulai->greaterThan($calStart) ? $mulai : $calStart->copy();
            $sampai = $selesai->lessThan($calEnd) ? $selesai : $calEnd->copy();

            for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
                $tugas[$d->toDateString()][] = [
                    'judul'    => $g->uraian_pekerjaan ?: '(tanpa uraian)',
                    'deadline' => $d->isSameDay($selesai),
                    'mitra'    => (int) $g->mitra,
                ];
            }
        }
        $tugas = collect($tugas)
            ->map(fn ($items) => collect($items)->sortByDesc('deadline')->values()->all())
            ->all();

        /* ---------- Aktivitas penugasan (tugas saya yang beririsan dengan bulan terpilih) ---------- */
        $formatTgl = fn ($d, $denganTahun = false) => Carbon::parse($d, $tz)->locale('id')
            ->translatedFormat($denganTahun ? 'j M Y' : 'j M');

        $aktivitas = $milikku()
            ->whereNotNull('kp.uraian_pekerjaan')
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan as judul, kp.tanggal_mulai as mulai, kp.tanggal_selesai as selesai,
                         count(distinct k.mitra_id) as mitra, max(kp.id) as terakhir')
            ->orderByDesc('terakhir')
            ->limit(20)
            ->get()
            ->map(function ($a) use ($formatTgl) {
                $a->koordinator = null; // semua tugas di sini milik koordinator yang login

                $bedaTahun = $a->mulai && $a->selesai
                    && substr((string) $a->mulai, 0, 4) !== substr((string) $a->selesai, 0, 4);
                $a->periode = ($a->mulai ? $formatTgl($a->mulai, $bedaTahun) : '-')
                    . ' - ' . ($a->selesai ? $formatTgl($a->selesai, $bedaTahun) : '-');

                return $a;
            });

        /* ---------- Perlu perhatian (hanya yang menyangkut tugas/mitra saya) ---------- */
        $telat = (int) $milikku()
            ->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
            ->where('kp.tanggal_selesai', '<', $hariIni)
            ->where(fn ($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
            ->count();

        $deadlineDekat = $bulanLewat ? 0 : (int) $hitung()
            ->whereBetween('kp.tanggal_selesai', [$acuan, Carbon::parse($acuan, $tz)->addDays(7)->toDateString()])
            ->value('n');

        $perhatian = collect([
            ['judul' => $mitraPenuh . ' Mitra',        'ket' => 'Sudah mencapai batas honor',             'tag' => 'Honor',      'level' => 'merah',  'jumlah' => $mitraPenuh],
            ['judul' => $deadlineDekat . ' Pekerjaan', 'ket' => 'Berakhir dalam waktu dekat (7 hari)',    'tag' => 'Deadline',   'level' => 'kuning', 'jumlah' => $deadlineDekat],
            ['judul' => $hampirPenuh . ' Mitra',       'ket' => 'Mendekati batas honor (80% atau lebih)', 'tag' => 'Honor',      'level' => 'kuning', 'jumlah' => $hampirPenuh],
            ['judul' => $telat . ' Tugas',             'ket' => 'Lewat deadline, honor belum dibayar',    'tag' => 'Pembayaran', 'level' => 'merah',  'jumlah' => $telat],
        ])->filter(fn ($p) => $p['jumlah'] > 0)
            ->map(fn ($p) => $p + ['url' => null])
            ->values();

        /* ---------- Sebaran wilayah mitra binaan yang aktif ---------- */
        $ekspresiWilayah = "coalesce(nullif(trim(mitra.wilayah), ''), 'Belum diisi')";
        $semuaWilayah = DB::table('mitra')
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->whereIn('mitra.id', $idBinaan)
            ->where('users.is_active', true)
            ->selectRaw("$ekspresiWilayah as wilayah, count(*) as jumlah")
            ->groupByRaw($ekspresiWilayah)
            ->orderByDesc('jumlah')
            ->get();

        if ($semuaWilayah->count() > 6) {
            $sebaran = $semuaWilayah->take(5)->values();
            $sebaran->push((object) [
                'wilayah' => 'Lainnya',
                'jumlah'  => (int) $semuaWilayah->slice(5)->sum('jumlah'),
            ]);
        } else {
            $sebaran = $semuaWilayah;
        }
        $totalSebaran = (int) $sebaran->sum('jumlah');

        /* ---------- Notifikasi (popover lonceng) ---------- */
        $tugasBaru = $milikku()
            ->whereNotNull('kp.uraian_pekerjaan')
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan as judul, count(distinct k.mitra_id) as mitra,
                         max(kp.id) as terakhir, max(kp.created_at) as dibuat')
            ->orderByDesc('terakhir')
            ->limit(4)
            ->get()
            ->map(fn ($t) => [
                'judul' => 'Tugas yang Anda berikan',
                'ket'   => $t->judul . ' · ' . $t->mitra . ' mitra',
                'level' => 'info',
                'ikon'  => 'tugas',
                'url'   => null,
                'waktu' => $t->dibuat ? Carbon::parse($t->dibuat)->locale('id')->diffForHumans() : null,
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

        return view('koordinator.dashboard', compact(
            'persenBayar', 'totalMitra', 'mitraTersedia', 'totalPekerjaan',
            'kalender', 'tugas', 'progres', 'statusMitra', 'aktivitas', 'perhatian', 'sebaran', 'totalSebaran',
            'notifikasi', 'notifPenting',
            'bulan', 'bulanKey', 'labelBulan', 'opsiBulan', 'hariIni', 'hariDipilih', 'calStart', 'calEnd'
        ));
    }
}
