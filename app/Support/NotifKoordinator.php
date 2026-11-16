<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotifKoordinator
{
    /** @return array{notifikasi: \Illuminate\Support\Collection, penting: int} */
    public static function untuk(int $me): array
    {
        $tz      = 'Asia/Makassar';
        $now     = now($tz);
        $hariIni = $now->toDateString();
        $awal    = $now->copy()->startOfMonth()->toDateString();
        $akhir   = $now->copy()->endOfMonth()->toDateString();
        $batas   = (int) config('mitra.batas_honor', config('penugasan.batas_honor', 3500000));

        $milikku = fn () => DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->where('kp.koordinator_id', $me);

        // Honor bulan ini tiap mitra binaan yang aktif
        $idBinaan = $milikku()->distinct()->pluck('k.mitra_id');
        $honor = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->join('mitra', 'mitra.id', '=', 'k.mitra_id')
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->where('users.is_active', true)
            ->whereIn('k.mitra_id', $idBinaan)
            ->where('k.periode_tahun', $now->year)
            ->where('k.periode_bulan', $now->month)
            ->groupBy('k.mitra_id')
            ->selectRaw('coalesce(sum(kp.jumlah), 0) as honor')
            ->pluck('honor');

        $penuh  = $batas > 0 ? $honor->filter(fn ($h) => (int) $h >= $batas)->count() : 0;
        $hampir = $batas > 0 ? $honor->filter(fn ($h) => $h >= $batas * 0.8 && $h < $batas)->count() : 0;

        $telat = (int) $milikku()
            ->whereBetween('kp.tanggal_selesai', [$awal, $akhir])
            ->where('kp.tanggal_selesai', '<', $hariIni)
            ->where(fn ($w) => $w->where('kp.honor_dibayar', false)->orWhereNull('kp.honor_dibayar'))
            ->count();

        $kunci = "concat_ws('|', kp.uraian_pekerjaan, kp.tanggal_mulai, kp.tanggal_selesai)";
        $dekat = (int) $milikku()
            ->selectRaw("count(distinct $kunci) as n")
            ->whereBetween('kp.tanggal_selesai', [$hariIni, $now->copy()->addDays(7)->toDateString()])
            ->value('n');

        $perhatian = collect([
            ['judul' => $penuh . ' Mitra',    'ket' => 'Sudah mencapai batas honor',             'level' => 'merah',  'jumlah' => $penuh,  'url' => null],
            ['judul' => $dekat . ' Pekerjaan', 'ket' => 'Berakhir dalam waktu dekat (7 hari)',    'level' => 'kuning', 'jumlah' => $dekat,  'url' => route('koordinator.datamitra')],
            ['judul' => $hampir . ' Mitra',   'ket' => 'Mendekati batas honor (80% atau lebih)', 'level' => 'kuning', 'jumlah' => $hampir, 'url' => null],
            ['judul' => $telat . ' Tugas',    'ket' => 'Lewat deadline, honor belum dibayar',    'level' => 'merah',  'jumlah' => $telat,  'url' => route('koordinator.datamitra', ['status' => 'belum'])],
        ])->filter(fn ($p) => $p['jumlah'] > 0)->values();

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
                'url'   => route('koordinator.datamitra'),
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

        return ['notifikasi' => $notifikasi, 'penting' => $perhatian->count()];
    }
}
