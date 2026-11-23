<?php

namespace App\Providers;

use App\Models\Mitra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as BladeView;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('components.notif', function (BladeView $view): void {
            $data = $view->getData();

            // Jika halaman (seperti Dashboard) sudah mengirim $notifikasi via props, jangan query ulang
            if (($data['notifikasi'] ?? null) !== null) {
                return;
            }

            /** @var User|null $user */
            $user = Auth::user();
            if (! $user) {
                $view->with([
                    'notifikasi'   => collect(),
                    'notifPenting' => 0,
                ]);
                return;
            }

            $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

            [$notifikasi, $notifPenting] = match ($role) {
                'admin'       => $this->notifikasiAdmin(),
                'koordinator' => $this->notifikasiKoordinator($user),
                'mitra'       => $this->notifikasiMitra($user),
                default       => [collect(), 0],
            };

            $view->with([
                'notifikasi'   => $notifikasi,
                'notifPenting' => $notifPenting,
            ]);
        });
    }

    private function notifikasiAdmin(): array
    {
        $tz       = 'Asia/Makassar';
        $sekarang = now($tz);
        $hariIni  = $sekarang->toDateString();
        $awalStr  = $sekarang->copy()->startOfMonth()->toDateString();
        $akhirStr = $sekarang->copy()->endOfMonth()->startOfDay()->toDateString();
        $tahun    = $sekarang->year;
        $bulan    = $sekarang->month;
        $batas    = (int) config('penugasan.batas_honor', 3500000);

        // Hitung mitra penuh & hampir penuh pada bulan berjalan
        $honorBulan = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $tahun)
            ->where('k.periode_bulan', $bulan)
            ->groupBy('k.mitra_id')
            ->selectRaw('k.mitra_id, coalesce(sum(kp.jumlah), 0) as honor')
            ->get()
            ->keyBy('mitra_id');

        $mitraAktifIds = Mitra::whereHas('user', fn ($u) => $u->where('is_active', true))->pluck('id');
        $hampirPenuh   = 0;
        $mitraPenuh    = 0;

        foreach ($mitraAktifIds as $mitraId) {
            $honor = (int) ($honorBulan->get($mitraId)->honor ?? 0);
            if ($batas > 0 && $honor >= $batas) {
                $mitraPenuh++;
            } elseif ($batas > 0 && $honor >= $batas * 0.8) {
                $hampirPenuh++;
            }
        }

        $kunci = "concat_ws('|', uraian_pekerjaan, tanggal_mulai, tanggal_selesai)";

        $telat = (int) DB::table('kontrak_pekerjaan')
            ->whereBetween('tanggal_selesai', [$awalStr, $akhirStr])
            ->where('tanggal_selesai', '<', $hariIni)
            ->where(fn ($w) => $w->where('honor_dibayar', false)->orWhereNull('honor_dibayar'))
            ->count();

        $deadlineDekat = (int) DB::table('kontrak_pekerjaan')
            ->whereBetween('tanggal_selesai', [$hariIni, $sekarang->copy()->addDays(7)->toDateString()])
            ->selectRaw("count(distinct $kunci) as n")
            ->value('n');

        $belumVerif = (int) DB::table('kontrak as k')
            ->where('k.periode_tahun', $tahun)
            ->where('k.periode_bulan', $bulan)
            ->where(fn ($w) => $w->where('k.cek_kontrak', false)->orWhere('k.cek_spj', false))
            ->whereExists(fn ($s) => $s->select(DB::raw(1))->from('kontrak_pekerjaan as kp')->whereColumn('kp.kontrak_id', 'k.id'))
            ->count();

        $perhatian = collect([
            ['judul' => $mitraPenuh . ' Mitra',        'ket' => 'Sudah mencapai batas honor',             'level' => 'merah',  'jumlah' => $mitraPenuh,    'url' => route('admin.datamitra')],
            ['judul' => $deadlineDekat . ' Pekerjaan', 'ket' => 'Berakhir dalam waktu dekat (7 hari)',    'level' => 'kuning', 'jumlah' => $deadlineDekat, 'url' => route('admin.penugasan')],
            ['judul' => $belumVerif . ' Kontrak',      'ket' => 'Belum diverifikasi (kontrak / SPJ)',     'level' => 'ungu',   'jumlah' => $belumVerif,    'url' => null],
            ['judul' => $hampirPenuh . ' Mitra',       'ket' => 'Mendekati batas honor (80% atau lebih)', 'level' => 'kuning', 'jumlah' => $hampirPenuh,   'url' => route('admin.datamitra')],
            ['judul' => $telat . ' Tugas',             'ket' => 'Lewat deadline, honor belum dibayar',    'level' => 'merah',  'jumlah' => $telat,         'url' => route('admin.penugasan', ['status' => 'belum'])],
        ])->filter(fn ($p) => $p['jumlah'] > 0)->values();

        $tugasBaru = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->whereNotNull('kp.uraian_pekerjaan')
            ->groupBy('kp.uraian_pekerjaan', 'kp.tanggal_mulai', 'kp.tanggal_selesai')
            ->selectRaw('kp.uraian_pekerjaan as judul, count(distinct k.mitra_id) as mitra,
                 max(kp.id) as terakhir, max(kp.created_at) as dibuat')
            ->orderByDesc('terakhir')
            ->limit(4)
            ->get()
            ->map(fn ($t) => [
                'judul' => 'Tugas baru diberikan',
                'ket'   => $t->judul . ' · ' . $t->mitra . ' mitra',
                'level' => 'info',
                'ikon'  => 'tugas',
                'url'   => route('admin.penugasan'),
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

        return [$notifikasi, $perhatian->count()];
    }

    private function notifikasiKoordinator(User $user): array
    {
        $me       = $user->id;
        $tz       = 'Asia/Makassar';
        $sekarang = now($tz);
        $hariIni  = $sekarang->toDateString();
        $awalStr  = $sekarang->copy()->startOfMonth()->toDateString();
        $akhirStr = $sekarang->copy()->endOfMonth()->startOfDay()->toDateString();
        $tahun    = $sekarang->year;
        $bulan    = $sekarang->month;
        $batas    = (int) config('mitra.batas_honor', config('penugasan.batas_honor', 3500000));

        $milikku = fn () => DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->where('kp.koordinator_id', $me);

        $kunci    = "concat_ws('|', kp.uraian_pekerjaan, kp.tanggal_mulai, kp.tanggal_selesai)";
        $idBinaan = $milikku()->distinct()->pluck('k.mitra_id')->all();

        $honorBulan = empty($idBinaan)
            ? collect()
            : DB::table('kontrak as k')
                ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
                ->whereIn('k.mitra_id', $idBinaan)
                ->where('k.periode_tahun', $tahun)
                ->where('k.periode_bulan', $bulan)
                ->groupBy('k.mitra_id')
                ->selectRaw('k.mitra_id, coalesce(sum(kp.jumlah), 0) as honor')
                ->get()
                ->keyBy('mitra_id');

        $mitraBinaanIds = empty($idBinaan)
            ? collect()
            : Mitra::whereIn('id', $idBinaan)
                ->whereHas('user', fn ($u) => $u->where('is_active', true))
                ->pluck('id');

        $mitraPenuh  = 0;
        $hampirPenuh = 0;

        foreach ($mitraBinaanIds as $mitraId) {
            $honor = (int) ($honorBulan->get($mitraId)->honor ?? 0);
            if ($batas > 0 && $honor >= $batas) {
                $mitraPenuh++;
            } elseif ($batas > 0 && $honor >= $batas * 0.8) {
                $hampirPenuh++;
            }
        }

        $telat = (int) $milikku()
            ->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
            ->where('kp.tanggal_selesai', '<', $hariIni)
            ->where(fn ($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
            ->count();

        $deadlineDekat = (int) $milikku()
            ->whereBetween('kp.tanggal_selesai', [$hariIni, $sekarang->copy()->addDays(7)->toDateString()])
            ->selectRaw("count(distinct $kunci) as n")
            ->value('n');

        $perhatian = collect([
            ['judul' => $mitraPenuh . ' Mitra',        'ket' => 'Sudah mencapai batas honor',             'level' => 'merah',  'jumlah' => $mitraPenuh],
            ['judul' => $deadlineDekat . ' Pekerjaan', 'ket' => 'Berakhir dalam waktu dekat (7 hari)',    'level' => 'kuning', 'jumlah' => $deadlineDekat],
            ['judul' => $hampirPenuh . ' Mitra',       'ket' => 'Mendekati batas honor (80% atau lebih)', 'level' => 'kuning', 'jumlah' => $hampirPenuh],
            ['judul' => $telat . ' Tugas',             'ket' => 'Lewat deadline, honor belum dibayar',    'level' => 'merah',  'jumlah' => $telat],
        ])->filter(fn ($p) => $p['jumlah'] > 0)
            ->map(fn ($p) => $p + ['url' => null])
            ->values();

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

        return [$notifikasi, $perhatian->count()];
    }

    private function notifikasiMitra(User $user): array
    {
        $mitraId = Mitra::where('user_id', $user->id)->value('id');
        if (! $mitraId) {
            return [collect(), 0];
        }

        $tz         = 'Asia/Makassar';
        $sekarang   = now($tz);
        $hariIni    = $sekarang->toDateString();
        $awalStr    = $sekarang->copy()->startOfMonth()->toDateString();
        $akhirStr   = $sekarang->copy()->endOfMonth()->startOfDay()->toDateString();
        $tahun      = $sekarang->year;
        $bulan      = $sekarang->month;
        $batasHonor = (int) config('mitra.batas_honor', config('penugasan.batas_honor', 3500000));

        $tugasSaya = fn () => DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->where('k.mitra_id', $mitraId);

        $totalHonor = (int) $tugasSaya()
            ->where('k.periode_tahun', $tahun)
            ->where('k.periode_bulan', $bulan)
            ->sum('kp.jumlah');

        $persenBatas = $batasHonor > 0 ? min(100, (int) round(($totalHonor / $batasHonor) * 100)) : 0;

        $rowsBulan = $tugasSaya()
            ->leftJoin('users as koor', 'koor.id', '=', 'kp.koordinator_id')
            ->where('k.periode_tahun', $tahun)
            ->where('k.periode_bulan', $bulan)
            ->select('kp.uraian_pekerjaan', 'kp.created_at', 'koor.name as koordinator_nama')
            ->orderByDesc('kp.id')
            ->limit(4)
            ->get();

        $telat = (int) $tugasSaya()
            ->whereBetween('kp.tanggal_selesai', [$awalStr, $akhirStr])
            ->where('kp.tanggal_selesai', '<', $hariIni)
            ->where(fn ($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
            ->count();

        $deadlineDekat = (int) $tugasSaya()
            ->whereBetween('kp.tanggal_selesai', [$hariIni, $sekarang->copy()->addDays(7)->toDateString()])
            ->count();

        $kontrakBulan = DB::table('kontrak')
            ->where('mitra_id', $mitraId)
            ->where('periode_tahun', $tahun)
            ->where('periode_bulan', $bulan)
            ->first();

        $belumVerifKontrak = ($rowsBulan->isNotEmpty() && $kontrakBulan && ! $kontrakBulan->cek_kontrak) ? 1 : 0;

        $perhatian = collect([
            [
                'judul'  => 'Batas Honor Tercapai',
                'ket'    => 'Total honor bulan ini mencapai batas maksimal Rp ' . number_format($batasHonor, 0, ',', '.'),
                'level'  => 'merah',
                'jumlah' => ($batasHonor > 0 && $totalHonor >= $batasHonor) ? 1 : 0,
            ],
            [
                'judul'  => $deadlineDekat . ' Pekerjaan',
                'ket'    => 'Berakhir dalam waktu dekat (7 hari)',
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
                'level'  => 'kuning',
                'jumlah' => $belumVerifKontrak,
            ],
        ])->filter(fn ($p) => $p['jumlah'] > 0)
            ->map(fn ($p) => $p + ['url' => null])
            ->values();

        $tugasBaru = $rowsBulan->map(fn ($t) => [
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

        return [$notifikasi, $perhatian->count()];
    }
}
