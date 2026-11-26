<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;
use App\Support\Nama;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tz       = 'Asia/Makassar';
        $sekarang = now($tz);
        $hariIni  = $sekarang->toDateString();
        $batas    = (int) config('penugasan.batas_honor', 3500000);

        /* ---------- Bulan yang dilihat (?bulan=YYYY-MM), bawaannya bulan sekarang ---------- */
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
            $hariDipilih = $hariIni;
        } elseif ($bulanLewat) {
            $hariDipilih = $akhirStr;
        } else {
            $hariDipilih = $awalStr;
        }

        /* ---------- Daftar bulan untuk dropdown ---------- */
        $kunciBulan = collect();
        for ($i = 1; $i <= 12; $i++) {
            $kunciBulan->push(sprintf('%d-%02d', $sekarang->year, $i));
        }
        DB::table('kontrak')->select('periode_tahun', 'periode_bulan')->distinct()->get()
            ->each(fn($r) => $kunciBulan->push(sprintf('%d-%02d', $r->periode_tahun, $r->periode_bulan)));
        $kunciBulan->push($bulanKey);

        $opsiBulan = $kunciBulan->unique()->sort()->values()->map(function ($k) use ($tz, $sekarang) {
            [$y, $m] = array_map('intval', explode('-', $k));
            $d = Carbon::create($y, $m, 1, 0, 0, 0, $tz)->locale('id');

            return ['k' => $k, 't' => $d->translatedFormat('F') . ($y !== $sekarang->year ? ' ' . $y : '')];
        });
        $labelBulan = $opsiBulan->firstWhere('k', $bulanKey)['t'] ?? $bulan->copy()->locale('id')->translatedFormat('F');

        /* ---------- Progres pembayaran (bulan terpilih) ---------- */
        $bayarBulan = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->selectRaw('coalesce(sum(kp.jumlah), 0) as total,
                         coalesce(sum(case when kp.honor_dibayar = 1 then kp.jumlah else 0 end), 0) as dibayar')
            ->first();
        $persenBayar = (int) $bayarBulan->total > 0
            ? (int) round($bayarBulan->dibayar / $bayarBulan->total * 100)
            : 0;

        /* ---------- Honor & volume tiap mitra pada bulan terpilih ---------- */
        $honorBulan = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->groupBy('k.mitra_id')
            ->selectRaw(
                'k.mitra_id,
                 coalesce(sum(kp.jumlah), 0) as honor,
                 coalesce(sum(kp.volume), 0) as volume,
                 coalesce(sum(case when kp.tanggal_selesai < ? then kp.volume else 0 end), 0) as volume_selesai',
                [$hariIni]
            )
            ->get()
            ->keyBy('mitra_id');

        /* ---------- Mitra aktif: tersedia, bertugas, penuh ---------- */
        $mitraAktif = Mitra::with('user:id,name,username')
            ->select('id', 'user_id', 'wilayah')
            ->whereHas('user', fn($u) => $u->where('is_active', true))
            ->get();

        $daftarStatus = collect();
        $progres      = [];
        $hampirPenuh  = 0;
        $mitraPenuh   = 0;
        $mitraTersedia = 0;

        foreach ($mitraAktif as $m) {
            $h     = $honorBulan->get($m->id);
            $honor = (int) ($h->honor ?? 0);
            $penuh = $batas > 0 && $honor >= $batas;
            $nama  = Nama::rapikan($m->user?->name);

            if ($penuh) {
                $mitraPenuh++;
                $statusLabel = 'Penuh';
            } else {
                $mitraTersedia++;
                if ($batas > 0 && $honor >= $batas * 0.8) {
                    $hampirPenuh++;
                }
                $statusLabel = $honor > 0 ? 'Bertugas' : 'Tersedia';
            }

            $daftarStatus->push([
                'nama'   => $nama,
                'sobat'  => $m->user?->username ?? '-',
                'honor'  => $honor,
                'status' => $statusLabel,
            ]);

            if ($honor > 0) {
                $progres[] = [
                    'id'      => $m->id,
                    'nama'    => $nama,
                    'label'   => Nama::pendek($nama),
                    'status'  => $penuh ? 'penuh' : 'bertugas',
                    'total'   => round((float) ($h->volume ?? 0), 2),
                    'selesai' => round((float) ($h->volume_selesai ?? 0), 2),
                ];
            }
        }

        // Status Mitra: prioritaskan Bertugas, Penuh, dan Tersedia
        $bertugasList = $daftarStatus->where('status', 'Bertugas')->sortBy([['honor', 'desc'], ['nama', 'asc']])->values();
        $penuhList    = $daftarStatus->where('status', 'Penuh')->sortBy([['honor', 'desc'], ['nama', 'asc']])->values();
        $tersediaList = $daftarStatus->where('status', 'Tersedia')->sortBy('nama')->values();

        $statusMitra = $bertugasList->concat($penuhList)->concat($tersediaList)->take(12)->values();

        // Grafik: progres selesai terbanyak lebih dulu
        usort($progres, fn($a, $b) => ($b['selesai'] <=> $a['selesai']) ?: ($b['total'] <=> $a['total']));
        $progres = array_slice($progres, 0, 60);

        /* ---------- Kalender (awal minggu Senin - Minggu) ---------- */
        $kunci  = "concat_ws('|', uraian_pekerjaan, tanggal_mulai, tanggal_selesai)";
        $hitung = fn() => DB::table('kontrak_pekerjaan')->selectRaw("count(distinct $kunci) as n");

        $calStart = $bulan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $calEnd   = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $grup = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->whereNotNull('kp.tanggal_mulai')
            ->whereNotNull('kp.tanggal_selesai')
            ->where('kp.tanggal_mulai', '<=', $calEnd->toDateString())
            ->where('kp.tanggal_selesai', '>=', $calStart->toDateString())
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan, kp.tanggal_mulai, kp.tanggal_selesai')
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
        foreach ($grup as $g) {
            $mulai   = Carbon::parse($g->tanggal_mulai, $tz)->startOfDay();
            $selesai = Carbon::parse($g->tanggal_selesai, $tz)->startOfDay();
            $rentang = $formatRentang($mulai, $selesai);

            $dari   = $mulai->greaterThan($calStart) ? $mulai : $calStart->copy();
            $sampai = $selesai->lessThan($calEnd) ? $selesai : $calEnd->copy();

            for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
                $sisa = (int) $d->diffInDays($selesai, false);
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

        /* ---------- Aktivitas penugasan (bulan terpilih) ---------- */
        $aktivitas = DB::table('kontrak_pekerjaan as kp')
            ->whereNotNull('kp.uraian_pekerjaan')
            ->where('kp.tanggal_mulai', '<=', $akhirStr)
            ->where('kp.tanggal_selesai', '>=', $awalStr)
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan as judul, kp.tanggal_mulai as mulai, kp.tanggal_selesai as selesai, max(kp.id) as terakhir')
            ->orderByDesc('terakhir')
            ->limit(20)
            ->get()
            ->map(function ($a) use ($tz, $formatRentang) {
                $mulai   = $a->mulai ? Carbon::parse($a->mulai, $tz)->startOfDay() : null;
                $selesai = $a->selesai ? Carbon::parse($a->selesai, $tz)->startOfDay() : null;

                $a->periode = ($mulai && $selesai) ? $formatRentang($mulai, $selesai) : '-';

                return $a;
            });

        /* ---------- Perlu perhatian (bulan terpilih) ---------- */
        $acuan = $bulanIni ? $hariIni : ($bulanLewat ? $akhirStr : $awalStr);

        $telat = (int) DB::table('kontrak_pekerjaan')
            ->whereBetween('tanggal_selesai', [$awalStr, $akhirStr])
            ->where('tanggal_selesai', '<', $hariIni)
            ->where(fn($w) => $w->where('honor_dibayar', false)->orWhereNull('honor_dibayar'))
            ->count();

        $deadlineDekat = $bulanLewat ? 0 : (int) $hitung()
            ->whereBetween('tanggal_selesai', [$acuan, Carbon::parse($acuan, $tz)->addDays(7)->toDateString()])
            ->value('n');

        $belumVerif = (int) DB::table('kontrak as k')
            ->where('k.periode_tahun', $bulan->year)
            ->where('k.periode_bulan', $bulan->month)
            ->where(fn($w) => $w->where('k.cek_kontrak', false)->orWhere('k.cek_spj', false))
            ->whereExists(fn($s) => $s->select(DB::raw(1))->from('kontrak_pekerjaan as kp')->whereColumn('kp.kontrak_id', 'k.id'))
            ->count();

        $perhatian = collect([
            ['judul' => $belumVerif . ' Kontrak',      'ket' => 'Belum Diverifikasi',                     'level' => 'merah',  'jumlah' => $belumVerif,    'url' => route('admin.kontrak')],
            ['judul' => $mitraPenuh . ' Mitra',        'ket' => 'Mencapai Batas Honor',                   'level' => 'merah',  'jumlah' => $mitraPenuh,    'url' => route('admin.datamitra')],
            ['judul' => $deadlineDekat . ' Pekerjaan', 'ket' => 'Berakhir dalam waktu dekat (7 hari)',    'level' => 'kuning', 'jumlah' => $deadlineDekat, 'url' => route('admin.penugasan')],
            ['judul' => $hampirPenuh . ' Mitra',       'ket' => 'Mendekati batas honor (80% atau lebih)', 'level' => 'kuning', 'jumlah' => $hampirPenuh,   'url' => route('admin.datamitra')],
            ['judul' => $telat . ' Tugas',             'ket' => 'Lewat deadline, honor belum dibayar',    'level' => 'merah',  'jumlah' => $telat,         'url' => route('admin.penugasan', ['status' => 'belum'])],
        ])->filter(fn($p) => $p['jumlah'] > 0)->values();

        /* ---------- Sebaran wilayah mitra aktif ---------- */
        $ekspresiWilayah = "coalesce(nullif(trim(mitra.wilayah), ''), 'Belum diisi')";
        $semuaWilayah = DB::table('mitra')
            ->join('users', 'users.id', '=', 'mitra.user_id')
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
        $tugasBaru = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->whereNotNull('kp.uraian_pekerjaan')
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan as judul, count(distinct k.mitra_id) as mitra,
                 max(kp.id) as terakhir, max(kp.created_at) as dibuat')
            ->orderByDesc('terakhir')
            ->limit(4)
            ->get()
            ->map(fn($t) => [
                'judul' => 'Tugas baru diberikan',
                'ket'   => $t->judul . ' · ' . $t->mitra . ' mitra',
                'level' => 'info',
                'ikon'  => 'tugas',
                'url'   => route('admin.penugasan'),
                'waktu' => $t->dibuat ? Carbon::parse($t->dibuat)->locale('id')->diffForHumans() : null,
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

        /* ---------- Kartu angka ---------- */
        $totalMitra = Mitra::count();
        $totalKoor  = User::where('role', 'koordinator')->count();

        return view('admin.dashboard', compact(
            'persenBayar',
            'totalMitra',
            'mitraTersedia',
            'totalKoor',
            'tugas',
            'progres',
            'statusMitra',
            'aktivitas',
            'perhatian',
            'sebaran',
            'bulanKey',
            'bulanSebelumnya',
            'bulanBerikutnya',
            'labelBulan',
            'opsiBulan',
            'hariIni',
            'hariDipilih',
            'calStart',
            'calEnd',
            'bulan',
            'totalSebaran',
            'notifikasi',
            'notifPenting'
        ));
    }
}
