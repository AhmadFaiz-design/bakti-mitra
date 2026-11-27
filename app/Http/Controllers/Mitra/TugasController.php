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
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

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

        /* ---------- Tren bulan ini vs bulan lalu untuk badge kartu ---------- */
        $blnIni  = now('Asia/Makassar');
        $blnLalu = $blnIni->copy()->subMonthNoOverflow();

        $ambilBulan = function (Carbon $tgl) use ($mitraId) {
            if ($mitraId === null) {
                return ['total' => 0, 'dibayar' => 0, 'belum' => 0];
            }

            $row = DB::table('kontrak_pekerjaan as kp')
                ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                ->where('k.mitra_id', $mitraId)
                ->where('k.periode_tahun', $tgl->year)
                ->where('k.periode_bulan', $tgl->month)
                ->selectRaw('COALESCE(SUM(kp.jumlah), 0) as total,
                             COALESCE(SUM(CASE WHEN kp.honor_dibayar = 1 THEN kp.jumlah ELSE 0 END), 0) as dibayar')
                ->first();

            $total   = (int) ($row->total ?? 0);
            $dibayar = (int) ($row->dibayar ?? 0);

            return [
                'total'   => $total,
                'dibayar' => $dibayar,
                'belum'   => $total - $dibayar,
            ];
        };

        $dataBlnIni  = $ambilBulan($blnIni);
        $dataBlnLalu = $ambilBulan($blnLalu);

        $hitungTren = function (int $sekarang, int $lalu, bool $balikWarna = false): array {
            if ($lalu <= 0) {
                if ($sekarang <= 0) {
                    return ['teks' => 'Tetap 0%', 'kelas' => 'neutral'];
                }

                return ['teks' => 'Baru bln ini', 'kelas' => $balikWarna ? 'warn' : 'lime'];
            }

            $diff = (int) round((($sekarang - $lalu) / $lalu) * 100);
            if ($diff > 0) {
                return ['teks' => 'Naik ' . $diff . '%', 'kelas' => $balikWarna ? 'warn' : 'lime'];
            }
            if ($diff < 0) {
                return ['teks' => 'Turun ' . abs($diff) . '%', 'kelas' => $balikWarna ? 'lime' : 'warn'];
            }

            return ['teks' => 'Tetap 0%', 'kelas' => 'neutral'];
        };

        $tren = [
            'total'   => $hitungTren($dataBlnIni['total'], $dataBlnLalu['total']),
            'dibayar' => $hitungTren($dataBlnIni['dibayar'], $dataBlnLalu['dibayar']),
            'belum'   => $hitungTren($dataBlnIni['belum'], $dataBlnLalu['belum'], true),
        ];

        $daftar = $query
            ->with([
                'kontrak:id,mitra_id,periode_tahun,periode_bulan,cek_kontrak,cek_spj',
                'koordinator:id,name,username',
            ])
            ->latest('id')
            ->paginate($perPage)
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
            'perPage',
            'q',
            'status',
            'ringkasan',
            'tren',
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
