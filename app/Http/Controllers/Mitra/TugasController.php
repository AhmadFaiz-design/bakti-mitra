<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\KontrakPekerjaan;
use App\Models\Mitra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TugasController extends Controller
{
    public function index(Request $request)
    {
        $mitra   = Mitra::where('user_id', Auth::id())->first();
        $mitraId = $mitra?->id;
        $hariIni = now('Asia/Makassar')->toDateString();

        $maxHonor = (int) (KontrakPekerjaan::query()
            ->when(
                $mitraId !== null,
                fn($q) => $q->whereHas('kontrak', fn($k) => $k->where('mitra_id', $mitraId)),
                fn($q) => $q->whereRaw('1 = 0')
            )
            ->max('jumlah') ?? 0);

        $honorMaks = max(100000, (int) (ceil($maxHonor / 100000) * 100000));
        $q         = trim((string) $request->query('q', ''));
        $status    = in_array($request->query('status'), ['belum', 'lunas'], true)
            ? $request->query('status')
            : 'semua';

        $query = $this->filterQuery($request, $mitraId, $hariIni);

        // Ringkasan honor mengikuti pencarian & filter aktif
        $ringkasan = [
            'total'   => (int) (clone $query)->sum('jumlah'),
            'dibayar' => (int) (clone $query)->where('honor_dibayar', true)->sum('jumlah'),
        ];
        $ringkasan['belum'] = $ringkasan['total'] - $ringkasan['dibayar'];

        $daftar = $query
            ->with([
                'kontrak:id,mitra_id,periode_tahun,periode_bulan,cek_kontrak,cek_spj',
                'koordinator:id,name,username',
            ])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftar->lastPage()]));
        }

        // Opsi filter Periode Kontrak milik mitra
        $daftarPeriode = $mitraId !== null
            ? DB::table('kontrak')
                ->where('mitra_id', $mitraId)
                ->select('periode_tahun', 'periode_bulan')
                ->distinct()
                ->orderByDesc('periode_tahun')
                ->orderByDesc('periode_bulan')
                ->get()
                ->map(function ($row) {
                    $key = sprintf('%04d-%02d', $row->periode_tahun, $row->periode_bulan);
                    $label = Carbon::create((int) $row->periode_tahun, (int) $row->periode_bulan, 1)
                        ->locale('id')
                        ->translatedFormat('F Y');

                    return ['v' => $key, 't' => $label];
                })
            : collect();

        // Opsi filter Koordinator pemberi tugas pada mitra ini
        $koorIds = $mitraId !== null
            ? KontrakPekerjaan::query()
                ->whereHas('kontrak', fn($k) => $k->where('mitra_id', $mitraId))
                ->whereNotNull('koordinator_id')
                ->distinct()
                ->pluck('koordinator_id')
            : collect();

        $daftarKoordinator = $koorIds->isNotEmpty()
            ? User::whereIn('id', $koorIds)->orderBy('name')->get(['id', 'name'])
            : collect();

        // Opsi filter Pekerjaan
        $daftarTugas = $mitraId !== null
            ? KontrakPekerjaan::query()
                ->whereHas('kontrak', fn($k) => $k->where('mitra_id', $mitraId))
                ->whereNotNull('uraian_pekerjaan')
                ->distinct()
                ->orderBy('uraian_pekerjaan')
                ->pluck('uraian_pekerjaan')
            : collect();

        return view('mitra.tugas', compact(
            'daftar',
            'q',
            'status',
            'ringkasan',
            'honorMaks',
            'hariIni',
            'daftarPeriode',
            'daftarKoordinator',
            'daftarTugas'
        ));
    }

    private function filterQuery(Request $request, ?int $mitraId, string $hariIni): Builder
    {
        $q      = trim((string) $request->input('q', ''));
        $status = $request->input('status');

        $query = KontrakPekerjaan::query()
            ->when(
                $mitraId !== null,
                fn($query) => $query->whereHas('kontrak', fn($k) => $k->where('mitra_id', $mitraId)),
                fn($query) => $query->whereRaw('1 = 0')
            )
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('uraian_pekerjaan', 'like', "%{$q}%")
                    ->orWhere('ro', 'like', "%{$q}%")
                    ->orWhere('kro_ro', 'like', "%{$q}%")
                    ->orWhere('komponen', 'like', "%{$q}%")
                    ->orWhereHas('koordinator', fn($k) => $k
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            }))
            ->when($status === 'lunas', fn($query) => $query->where('honor_dibayar', true))
            ->when($status === 'belum', fn($query) => $query->where('honor_dibayar', false));

        // Rentang honor
        $parse = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
        $min = $max = null;

        if ($request->input('honor_mode') === 'rentang') {
            if ($request->filled('honor_dari'))   $min = $parse($request->input('honor_dari'));
            if ($request->filled('honor_sampai')) $max = $parse($request->input('honor_sampai'));
        } elseif ($request->filled('honor_jumlah')) {
            $min = $max = $parse($request->input('honor_jumlah'));
        }
        if ($min !== null) $query->where('jumlah', '>=', $min);
        if ($max !== null) $query->where('jumlah', '<=', $max);

        // Periode kontrak (YYYY-MM)
        $periode = (string) $request->input('periode', '');
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $periode, $m)) {
            $query->whereHas('kontrak', fn($k) => $k
                ->where('periode_tahun', (int) $m[1])
                ->where('periode_bulan', (int) $m[2]));
        }

        // Status pelaksanaan (berlangsung / akan_datang / selesai)
        $waktu = $request->input('waktu');
        if ($waktu === 'berlangsung') {
            $query->where('tanggal_mulai', '<=', $hariIni)
                ->where('tanggal_selesai', '>=', $hariIni);
        } elseif ($waktu === 'akan_datang') {
            $query->where('tanggal_mulai', '>', $hariIni);
        } elseif ($waktu === 'selesai') {
            $query->where('tanggal_selesai', '<', $hariIni);
        }

        // Koordinator
        if ($request->filled('koordinator')) {
            $query->where('koordinator_id', (int) $request->input('koordinator'));
        }

        // Pekerjaan
        $tugas = array_values(array_filter((array) $request->input('tugas', [])));
        if ($tugas) {
            $query->whereIn('uraian_pekerjaan', $tugas);
        }

        return $query;
    }
}
