<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;
use App\Support\Nama;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $tz         = 'Asia/Makassar';
        $today      = now($tz);
        $hariIni    = $today->toDateString();
        $awalBulan  = $today->copy()->startOfMonth()->toDateString();
        $akhirBulan = $today->copy()->endOfMonth()->toDateString();
        $batas      = (int) config('penugasan.batas_honor', 3500000);

        /* ---------- 1. Progres pembayaran (seluruh data, sama dengan halaman penugasan) ---------- */
        $totalHonor  = (int) DB::table('kontrak_pekerjaan')->sum('jumlah');
        $dibayar     = (int) DB::table('kontrak_pekerjaan')->where('honor_dibayar', true)->sum('jumlah');
        $persenBayar = $totalHonor > 0 ? (int) round($dibayar / $totalHonor * 100) : 0;

        /* ---------- Honor & volume tiap mitra pada bulan berjalan ---------- */
        $honorBulan = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $today->year)
            ->where('k.periode_bulan', $today->month)
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

        /* ---------- 2, 4, 5. Mitra aktif: tersedia, bertugas, penuh ---------- */
        $mitraAktif = Mitra::with('user:id,name')
            ->select('id', 'user_id', 'wilayah')
            ->whereHas('user', fn($u) => $u->where('is_active', true))
            ->get();

        $tersedia = collect();
        $progres  = [];

        foreach ($mitraAktif as $m) {
            $h      = $honorBulan->get($m->id);
            $honor  = (int) ($h->honor ?? 0);
            $penuh  = $batas > 0 && $honor >= $batas;
            $nama   = Nama::rapikan($m->user?->name);

            if (! $penuh) {
                $tersedia->push([
                    'nama'    => $nama,
                    'wilayah' => $m->wilayah ?: '-',
                    'honor'   => $honor,
                    'status'  => $honor > 0 ? 'Bertugas' : 'Tersedia',
                ]);
            }

            if ($honor > 0) {
                $progres[] = [
                    'id'      => $m->id,
                    'nama'    => $nama,
                    'label'   => explode(' ', $nama)[0],
                    'status'  => $penuh ? 'penuh' : 'bertugas',
                    'total'   => round((float) ($h->volume ?? 0), 2),
                    'selesai' => round((float) ($h->volume_selesai ?? 0), 2),
                ];
            }
        }

        $mitraTersedia = $tersedia->count();

        // Yang paling longgar ditampilkan lebih dulu
        $statusMitra = $tersedia
            ->sortBy([['honor', 'asc'], ['nama', 'asc']])
            ->take(6)
            ->values();

        // Grafik: volume terbanyak lebih dulu
        usort($progres, fn($a, $b) => $b['total'] <=> $a['total']);

        /* ---------- 3. Kalender & ringkasan bulan ini ---------- */
        $kunci = "concat_ws('|', uraian_pekerjaan, tanggal_mulai, tanggal_selesai)";

        $hitung = fn() => DB::table('kontrak_pekerjaan')->selectRaw("count(distinct $kunci) as n");

        $kalender = [
            'deadline'    => (int) $hitung()->whereBetween('tanggal_selesai', [$awalBulan, $akhirBulan])->value('n'),
            'selesai'     => (int) $hitung()->whereBetween('tanggal_selesai', [$awalBulan, $akhirBulan])
                ->where('tanggal_selesai', '<', $hariIni)->value('n'),
            'berlangsung' => (int) $hitung()->where('tanggal_mulai', '<=', $hariIni)
                ->where('tanggal_selesai', '>=', $hariIni)->value('n'),
        ];

        // Tugas per tanggal untuk seluruh kotak kalender (termasuk tanggal bulan sebelum/sesudahnya)
        $calStart = $today->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $calEnd   = $today->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY)->startOfDay();

        $grup = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
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
                    'deadline' => $d->isSameDay($selesai),   // titik merah & label Deadline
                    'mitra'    => (int) $g->mitra,
                ];
            }
        }

        // Yang deadline-nya hari itu tampil paling atas
        $tugas = collect($tugas)
            ->map(fn($items) => collect($items)->sortByDesc('deadline')->values()->all())
            ->all();

        /* ---------- 6. Aktivitas penugasan ---------- */
        $aktivitas = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->leftJoin('users as u', 'u.id', '=', 'kp.koordinator_id')
            ->whereNotNull('kp.uraian_pekerjaan')
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan as judul, kp.tanggal_mulai as mulai, kp.tanggal_selesai as selesai,
                 max(u.name) as koordinator, count(distinct k.mitra_id) as mitra, max(kp.id) as terakhir')
            ->orderByDesc('terakhir')
            ->limit(6)
            ->get()
            ->map(function ($a) {
                $a->koordinator = $a->koordinator ? Nama::rapikan($a->koordinator) : null;
                $a->periode = ($a->mulai ? Carbon::parse($a->mulai)->format('d/m') : '-')
                    . ' – ' . ($a->selesai ? Carbon::parse($a->selesai)->format('d/m/Y') : '-');
                return $a;
            });

        /* ---------- Ringkasan honor bulan ini ---------- */
        $totalHonorBulan = (int) $honorBulan->sum('honor');

        $belumBayarBulan = (int) DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $today->year)
            ->where('k.periode_bulan', $today->month)
            ->where(fn($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
            ->sum('kp.jumlah');

        $jumlahAktif   = $mitraAktif->count();
        $mitraBertugas = $mitraAktif->filter(fn($m) => (int) ($honorBulan->get($m->id)->honor ?? 0) > 0)->count();
        $mitraPenuh    = $batas > 0
            ? $mitraAktif->filter(fn($m) => (int) ($honorBulan->get($m->id)->honor ?? 0) >= $batas)->count()
            : 0;
        $hampirPenuh   = $batas > 0
            ? $mitraAktif->filter(function ($m) use ($honorBulan, $batas) {
                $h = (int) ($honorBulan->get($m->id)->honor ?? 0);
                return $h >= $batas * 0.8 && $h < $batas;
            })->count()
            : 0;

        /* ---------- Perlu perhatian ---------- */
        $telat = DB::table('kontrak_pekerjaan')
            ->whereNotNull('tanggal_selesai')
            ->where('tanggal_selesai', '<', $hariIni)
            ->where(fn($w) => $w->where('honor_dibayar', false)->orWhereNull('honor_dibayar'))
            ->count();

        $deadline7 = (int) DB::table('kontrak_pekerjaan')
            ->selectRaw("count(distinct $kunci) as n")
            ->whereBetween('tanggal_selesai', [$hariIni, $today->copy()->addDays(7)->toDateString()])
            ->value('n');

        $belumVerif = DB::table('kontrak as k')
            ->where('k.periode_tahun', $today->year)
            ->where('k.periode_bulan', $today->month)
            ->where(fn($w) => $w->where('k.cek_kontrak', false)->orWhere('k.cek_spj', false))
            ->whereExists(fn($s) => $s->select(DB::raw(1))->from('kontrak_pekerjaan as kp')->whereColumn('kp.kontrak_id', 'k.id'))
            ->count();

        $perhatian = collect([
            ['judul' => 'Tugas lewat deadline',     'ket' => 'Honor mitra belum dibayar',  'jumlah' => $telat,       'level' => 'merah',  'url' => route('admin.penugasan', ['status' => 'belum'])],
            ['judul' => 'Deadline 7 hari ke depan', 'ket' => 'Pekerjaan yang segera berakhir', 'jumlah' => $deadline7,  'level' => 'kuning', 'url' => route('admin.penugasan')],
            ['judul' => 'Mendekati batas honor',    'ket' => 'Honor bulan ini sudah 80% atau lebih', 'jumlah' => $hampirPenuh, 'level' => 'kuning', 'url' => route('admin.datamitra')],
            ['judul' => 'Kontrak belum diverifikasi', 'ket' => 'Kontrak bulan ini',         'jumlah' => $belumVerif,  'level' => 'ungu',   'url' => null],
        ])->filter(fn($p) => $p['jumlah'] > 0)->values();

        /* ---------- Sebaran wilayah mitra aktif ---------- */
        $ekspresiWilayah = "coalesce(nullif(trim(mitra.wilayah), ''), 'Belum diisi')";
        $sebaran = DB::table('mitra')
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->where('users.is_active', true)
            ->selectRaw("$ekspresiWilayah as wilayah, count(*) as jumlah")
            ->groupByRaw($ekspresiWilayah)
            ->orderByDesc('jumlah')
            ->limit(6)
            ->get();

        /* ---------- Kartu angka ---------- */
        $totalMitra = Mitra::count();
        $totalKoor  = User::where('role', 'koordinator')->count();
        $koorAktif  = User::where('role', 'koordinator')->where('is_active', true)->count();

        return view('admin.dashboard', compact(
            'persenBayar',
            'totalMitra',
            'mitraTersedia',
            'totalKoor',
            'koorAktif',
            'kalender',
            'tugas',
            'progres',
            'statusMitra',
            'aktivitas',
            'totalHonorBulan',
            'belumBayarBulan',
            'jumlahAktif',
            'mitraBertugas',
            'mitraPenuh',
            'batas',
            'perhatian',
            'sebaran'
        ));
    }
}
