<?php

namespace App\Http\Controllers\Koordinator;

use App\Http\Controllers\Controller;
use App\Models\KontrakPekerjaan;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenugasanController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['belum', 'lunas'], true)
            ? $request->query('status')
            : 'semua';
        $me = Auth::id();

        $daftar = $this->buildQuery($request)
            ->with('kontrak.mitra.user')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftar->lastPage()]));
        }

        // Ringkasan mengikuti pencarian & filter, tetapi tidak mengikuti dropdown pembayaran
        $dasar     = $this->buildQuery($request, false);
        $total     = (int) (clone $dasar)->sum('jumlah');
        $dibayar   = (int) (clone $dasar)->where('honor_dibayar', true)->sum('jumlah');
        $ringkasan = ['total' => $total, 'dibayar' => $dibayar, 'belum' => $total - $dibayar];

        // Pilihan panel filter (hanya yang berhubungan dengan tugas saya)
        $daftarTugas = KontrakPekerjaan::where('koordinator_id', $me)
            ->whereNotNull('uraian_pekerjaan')
            ->distinct()->orderBy('uraian_pekerjaan')
            ->pluck('uraian_pekerjaan');

        $daftarWilayah = DB::table('kontrak_pekerjaan as kp')
            ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
            ->join('mitra as m', 'm.id', '=', 'k.mitra_id')
            ->where('kp.koordinator_id', $me)
            ->whereNotNull('m.wilayah')
            ->distinct()->orderBy('m.wilayah')
            ->pluck('m.wilayah');

        $honorTertinggi = (int) KontrakPekerjaan::where('koordinator_id', $me)->max('jumlah');
        $honorMaks      = max(100000, (int) (ceil($honorTertinggi / 100000) * 100000));

        return view('koordinator.manajemen-penugasan', compact(
            'daftar', 'ringkasan', 'q', 'status', 'daftarTugas', 'daftarWilayah', 'honorMaks'
        ));
    }

    /** Query tugas milik koordinator yang login, dengan pencarian dan filter */
    private function buildQuery(Request $request, bool $denganStatus = true): Builder
    {
        $q      = trim((string) $request->input('q', ''));
        $status = $request->input('status');

        $query = KontrakPekerjaan::query()
            ->where('koordinator_id', Auth::id());

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('uraian_pekerjaan', 'like', "%{$q}%")
                    ->orWhere('ro', 'like', "%{$q}%")
                    ->orWhereHas('kontrak.mitra.user', fn ($u) => $u
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            });
        }

        if ($denganStatus) {
            if ($status === 'lunas') {
                $query->where('honor_dibayar', true);
            } elseif ($status === 'belum') {
                $query->where(fn ($w) => $w->where('honor_dibayar', false)->orWhereNull('honor_dibayar'));
            }
        }

        // Rentang honor per tugas
        $parse = fn ($v) => (int) preg_replace('/\D/', '', (string) $v);
        if ($request->filled('honor_dari')) {
            $query->where('jumlah', '>=', $parse($request->input('honor_dari')));
        }
        if ($request->filled('honor_sampai')) {
            $query->where('jumlah', '<=', $parse($request->input('honor_sampai')));
        }

        // Wilayah mitra
        if ($request->filled('wilayah')) {
            $query->whereHas('kontrak.mitra', fn ($m) => $m->where('wilayah', $request->input('wilayah')));
        }

        // Pekerjaan (cocok dengan salah satu yang dipilih)
        $tugas = array_values(array_filter((array) $request->input('tugas', [])));
        if ($tugas) {
            $query->whereIn('uraian_pekerjaan', $tugas);
        }

        return $query;
    }

    public function export(Request $request)
    {
        $format   = $request->input('format') === 'pdf' ? 'pdf' : 'csv';
        $daftar   = $this->buildQuery($request)
            ->with('kontrak.mitra.user', 'koordinator')
            ->latest('id')
            ->get();
        $namaFile = 'penugasan-saya-' . now()->format('Ymd-His');
        $tgl      = fn ($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '-';

        // ================= CSV =================
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($daftar, $tgl) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, [
                    'No', 'Nama Mitra', 'Sobat ID', 'Wilayah', 'Uraian Pekerjaan', 'RO',
                    'Tanggal Mulai', 'Tanggal Selesai', 'Volume', 'Satuan', 'Honor',
                    'Status Pembayaran', 'Dibayar Pada',
                ], ';', '"', '');

                foreach ($daftar as $i => $p) {
                    $user = $p->kontrak?->mitra?->user;
                    fputcsv($out, [
                        $i + 1,
                        $user?->name,
                        '="' . $user?->username . '"',
                        $p->kontrak?->mitra?->wilayah,
                        $p->uraian_pekerjaan,
                        $p->ro,
                        $tgl($p->tanggal_mulai),
                        $tgl($p->tanggal_selesai),
                        $p->volume,
                        $p->satuan,
                        (int) ($p->jumlah ?? 0),
                        $p->honor_dibayar ? 'Sudah Dibayar' : 'Belum Dibayar',
                        $p->honor_dibayar ? ($p->dibayar_pada?->format('d/m/Y H:i') ?? '') : '',
                    ], ';', '"', '');
                }
                fclose($out);
            }, $namaFile . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // ================= PDF (memakai tampilan PDF penugasan milik admin) =================
        $statusLabel = ['lunas' => 'Sudah Dibayar', 'belum' => 'Belum Dibayar'][$request->input('status')] ?? 'Semua';
        $keterangan  = ['Koordinator: ' . (Auth::user()?->name ?? '-'), 'Status: ' . $statusLabel];

        if (trim((string) $request->input('q')) !== '') {
            $keterangan[] = 'Pencarian: "' . trim($request->input('q')) . '"';
        }
        if ($request->filled('wilayah')) {
            $keterangan[] = 'Wilayah: ' . $request->input('wilayah');
        }
        $tugas = array_filter((array) $request->input('tugas', []));
        if ($tugas) {
            $keterangan[] = 'Pekerjaan: ' . implode(', ', $tugas);
        }

        $html = view('admin.export-penugasan-pdf', [
            'daftar'     => $daftar,
            'keterangan' => $keterangan,
            'tanggal'    => now()->translatedFormat('d F Y H:i'),
            'totalHonor' => (int) $daftar->sum('jumlah'),
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $namaFile . '.pdf"',
        ]);
    }
}
