<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use App\Models\KontrakPekerjaan;
use App\Models\Mitra;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class PenugasanController extends Controller
{
    /** Daftar semua tugas yang sudah diberikan */
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['belum', 'lunas'], true)
            ? $request->query('status')
            : 'semua'; // semua | belum | lunas

        $query = $this->filterQuery($request);

        // Ringkasan honor mengikuti pencarian/filter yang sedang aktif
        $ringkasan = [
            'total'   => (int) (clone $query)->sum('jumlah'),
            'dibayar' => (int) (clone $query)->where('honor_dibayar', true)->sum('jumlah'),
        ];
        $ringkasan['belum'] = $ringkasan['total'] - $ringkasan['dibayar'];

        $daftar = $query
            ->with('kontrak.mitra.user', 'koordinator')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftar->lastPage()]));
        }

        // Pilihan dropdown di panel filter
        $daftarWilayah = Mitra::whereNotNull('wilayah')
            ->distinct()->orderBy('wilayah')->pluck('wilayah');

        $daftarTugas = KontrakPekerjaan::whereNotNull('uraian_pekerjaan')
            ->distinct()->orderBy('uraian_pekerjaan')->pluck('uraian_pekerjaan');

        $daftarKoordinator = User::where('role', 'koordinator')
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.penugasan', compact(
            'daftar',
            'q',
            'status',
            'ringkasan',
            'daftarWilayah',
            'daftarTugas',
            'daftarKoordinator'
        ));
    }

    /** Admin menandai honor sudah / belum dibayar */
    public function ubahStatusBayar(Request $request, KontrakPekerjaan $pekerjaan)
    {
        $dibayar = $request->boolean('dibayar');

        $pekerjaan->update([
            'honor_dibayar' => $dibayar,
            'dibayar_pada'  => $dibayar ? now() : null,
            'dibayar_oleh'  => $dibayar ? Auth::id() : null,
        ]);

        return response()->json([
            'dibayar'      => $dibayar,
            'dibayar_pada' => $pekerjaan->dibayar_pada?->format('d/m/Y H:i'),
        ]);
    }

    /** Form beri tugas */
    public function create()
    {
        $mitra = Mitra::with('user:id,name,username,is_active')
            ->whereHas('user', fn($u) => $u->where('is_active', true))
            ->get()
            ->sortBy(fn($m) => strtolower($m->user->name))
            ->values();

        return view('admin.tambah-penugasan', [
            'mitra'          => $mitra,
            'koordinator'    => $this->daftarKoordinator(),
            'daftarKro'      => config('penugasan.kro'),
            'daftarKomponen' => config('penugasan.komponen'),
            'daftarRoPulsa'  => config('penugasan.ro_pulsa'),
            'daftarSatuan'   => config('penugasan.satuan'),
        ]);
    }

    /** Simpan: satu tugas diberikan ke banyak mitra sekaligus */
    public function store(Request $request)
    {
        $this->rapikanInput($request);

        $data = $request->validate(
            $this->aturan() + [
                'mitra_id'   => 'required|array|min:1',
                'mitra_id.*' => 'integer|exists:mitra,id',
            ],
            $this->pesan() + [
                'mitra_id.required' => 'Pilih minimal satu mitra.',
                'mitra_id.min'      => 'Pilih minimal satu mitra.',
            ]
        );

        // Bulan kontrak mengikuti tanggal mulai
        $tanggal = Carbon::parse($data['tanggal_mulai']);
        $jumlah  = (int) round(((float) $data['volume']) * ((int) $data['harga_satuan']));
        $pulsa   = $request->boolean('paket_data_pulsa');
        $ro      = $this->gabungRo($data);

        $koordinatorId = (int) $data['koordinator_id'];

        $dibuat   = 0;
        $dilewati = [];

        try {
            DB::transaction(function () use ($data, $tanggal, $jumlah, $pulsa, $ro, $koordinatorId, &$dibuat, &$dilewati) {
                $daftar = Mitra::with('user')
                    ->whereIn('id', $data['mitra_id'])
                    ->whereHas('user', fn($u) => $u->where('is_active', true))
                    ->get();

                foreach ($daftar as $mitra) {
                    // Kontrak bulan itu dibuat otomatis jika belum ada
                    $kontrak = Kontrak::firstOrCreate(
                        [
                            'mitra_id'      => $mitra->id,
                            'periode_tahun' => $tanggal->year,
                            'periode_bulan' => $tanggal->month,
                        ],
                        ['dibuat_oleh' => Auth::id()]
                    );

                    // Lewati jika mitra sudah punya tugas yang sama pada bulan itu
                    $sudahAda = $kontrak->pekerjaan()
                        ->where('uraian_pekerjaan', $data['uraian_pekerjaan'])
                        ->exists();

                    if ($sudahAda) {
                        $dilewati[] = $mitra->user->name;
                        continue;
                    }

                    $kontrak->pekerjaan()->create([
                        'koordinator_id'      => $koordinatorId,
                        'urutan'              => (int) $kontrak->pekerjaan()->max('urutan') + 1,
                        'uraian_pekerjaan'    => $data['uraian_pekerjaan'],
                        'ro'                  => $ro,
                        'kro_ro'              => $data['kro_ro'] ?? null,
                        'komponen'            => $data['komponen'] ?? null,
                        'volume'              => $data['volume'],
                        'satuan'              => $data['satuan'],
                        'harga_satuan'        => $data['harga_satuan'],
                        'jumlah'              => $jumlah,
                        'paket_data_pulsa'    => $pulsa,
                        'ro_paket_data_pulsa' => $pulsa ? ($data['ro_paket_data_pulsa'] ?? null) : null,
                        'tanggal_mulai'       => $data['tanggal_mulai'],
                        'tanggal_selesai'     => $data['tanggal_selesai'],
                    ]);

                    $dibuat++;
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('gagal', 'Gagal menyimpan penugasan: ' . $e->getMessage());
        }

        if ($dibuat === 0) {
            return back()->withInput()->with(
                'gagal',
                'Tidak ada tugas yang dibuat. Mitra yang dipilih sudah memiliki tugas "'
                    . $data['uraian_pekerjaan'] . '" pada bulan itu, atau akunnya tidak aktif.'
            );
        }

        $pesan = 'Tugas "' . $data['uraian_pekerjaan'] . '" berhasil diberikan kepada ' . $dibuat . ' mitra.';
        if ($dilewati) {
            $pesan .= ' ' . count($dilewati) . ' mitra dilewati karena sudah memiliki tugas yang sama.';
        }

        return redirect()->route('admin.penugasan')->with('sukses', $pesan);
    }

    /** Form edit satu tugas */
    public function edit(KontrakPekerjaan $pekerjaan)
    {
        $pekerjaan->load('kontrak.mitra.user');

        if ($alasan = $this->alasanTerkunci($pekerjaan, 'diubah')) {
            return redirect()->route('admin.penugasan')->with('gagal', $alasan);
        }

        return view('admin.edit-penugasan', [
            'pekerjaan'      => $pekerjaan,
            'user'           => $pekerjaan->kontrak?->mitra?->user,
            'koordinator'    => $this->daftarKoordinator(),
            'daftarKro'      => config('penugasan.kro'),
            'daftarKomponen' => config('penugasan.komponen'),
            'daftarRoPulsa'  => config('penugasan.ro_pulsa'),
            'daftarSatuan'   => config('penugasan.satuan'),
        ]);
    }

    /** Simpan perubahan satu tugas */
    public function update(Request $request, KontrakPekerjaan $pekerjaan)
    {
        $pekerjaan->load('kontrak.mitra.user');

        if ($alasan = $this->alasanTerkunci($pekerjaan, 'diubah')) {
            return redirect()->route('admin.penugasan')->with('gagal', $alasan);
        }

        $this->rapikanInput($request);
        $data = $request->validate($this->aturan(), $this->pesan());

        $tanggal = Carbon::parse($data['tanggal_mulai']);
        $jumlah  = (int) round(((float) $data['volume']) * ((int) $data['harga_satuan']));
        $pulsa   = $request->boolean('paket_data_pulsa');
        $ro      = $this->gabungRo($data);
        $nama    = $pekerjaan->kontrak?->mitra?->user?->name ?? 'mitra';

        try {
            DB::transaction(function () use ($pekerjaan, $data, $tanggal, $jumlah, $pulsa, $ro) {
                $kontrakLama = $pekerjaan->kontrak;

                // Bulan kontrak mengikuti tanggal mulai; jika bulannya berubah, tugas pindah kontrak
                $kontrak = Kontrak::firstOrCreate(
                    [
                        'mitra_id'      => $kontrakLama->mitra_id,
                        'periode_tahun' => $tanggal->year,
                        'periode_bulan' => $tanggal->month,
                    ],
                    ['dibuat_oleh' => Auth::id()]
                );

                $pindah = $kontrak->getKey() !== $kontrakLama->getKey();

                if ($pindah && ($kontrak->cek_kontrak || $kontrak->cek_spj)) {
                    throw ValidationException::withMessages([
                        'tanggal_mulai' => 'Kontrak mitra pada bulan '
                            . $tanggal->translatedFormat('F Y')
                            . ' sudah diverifikasi, tugas tidak bisa dipindahkan ke sana.',
                    ]);
                }

                $kembar = $kontrak->pekerjaan()
                    ->where('uraian_pekerjaan', $data['uraian_pekerjaan'])
                    ->where('id', '!=', $pekerjaan->id)
                    ->exists();

                if ($kembar) {
                    throw ValidationException::withMessages([
                        'uraian_pekerjaan' => 'Mitra ini sudah punya tugas dengan uraian yang sama pada bulan itu.',
                    ]);
                }

                if ($pindah) {
                    $pekerjaan->kontrak()->associate($kontrak);
                    $pekerjaan->urutan = (int) $kontrak->pekerjaan()->max('urutan') + 1;
                }

                $pekerjaan->forceFill([
                    'koordinator_id'      => (int) $data['koordinator_id'],
                    'uraian_pekerjaan'    => $data['uraian_pekerjaan'],
                    'ro'                  => $ro,
                    'kro_ro'              => $data['kro_ro'] ?? null,
                    'komponen'            => $data['komponen'] ?? null,
                    'volume'              => $data['volume'],
                    'satuan'              => $data['satuan'],
                    'harga_satuan'        => $data['harga_satuan'],
                    'jumlah'              => $jumlah,
                    'paket_data_pulsa'    => $pulsa,
                    'ro_paket_data_pulsa' => $pulsa ? ($data['ro_paket_data_pulsa'] ?? null) : null,
                    'tanggal_mulai'       => $data['tanggal_mulai'],
                    'tanggal_selesai'     => $data['tanggal_selesai'],
                ])->save();
            });
        } catch (ValidationException $e) {
            throw $e; // ditampilkan sebagai pesan kesalahan form
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('gagal', 'Gagal menyimpan perubahan: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.penugasan')
            ->with('sukses', 'Tugas "' . $data['uraian_pekerjaan'] . '" untuk ' . $nama . ' berhasil diperbarui.');
    }

    /** Hapus satu tugas milik satu mitra */
    public function destroy(KontrakPekerjaan $pekerjaan)
    {
        $pekerjaan->load('kontrak');

        if ($alasan = $this->alasanTerkunci($pekerjaan, 'dihapus')) {
            return back()->with('gagal', $alasan);
        }

        $nama = $pekerjaan->uraian_pekerjaan;
        $pekerjaan->delete();

        return redirect()
            ->route('admin.penugasan')
            ->with('sukses', 'Tugas "' . $nama . '" berhasil dihapus.');
    }

    /**
     * Query penugasan dengan pencarian, status, dan semua filter.
     * Dipakai oleh index(), export(), dan bayarMassal().
     */
    private function filterQuery(Request $request): Builder
    {
        $q      = trim((string) $request->input('q', ''));
        $status = $request->input('status');

        $query = KontrakPekerjaan::query()
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('uraian_pekerjaan', 'like', "%{$q}%")
                    ->orWhere('ro', 'like', "%{$q}%")
                    ->orWhereHas('kontrak.mitra.user', fn($u) => $u
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            }))
            ->when($status === 'lunas', fn($query) => $query->where('honor_dibayar', true))
            ->when($status === 'belum', fn($query) => $query->where('honor_dibayar', false));

        // Honor per tugas: nominal (sama dengan) atau rentang
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

        // Wilayah mitra
        if ($request->filled('wilayah')) {
            $query->whereHas('kontrak.mitra', fn($m) => $m->where('wilayah', $request->input('wilayah')));
        }

        // Koordinator pemberi tugas
        if ($request->filled('koordinator')) {
            $query->where('koordinator_id', (int) $request->input('koordinator'));
        }

        // Tugas (cocok dengan salah satu yang dipilih)
        $tugas = array_values(array_filter((array) $request->input('tugas', [])));
        if ($tugas) {
            $query->whereIn('uraian_pekerjaan', $tugas);
        }

        return $query;
    }

    /** Tandai banyak tugas sekaligus: sudah / belum dibayar */
    public function bayarMassal(Request $request)
    {
        $request->validate([
            'aksi'  => 'required|in:bayar,batal',
            'ids'   => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        $dibayar = $request->input('aksi') === 'bayar';

        // "Pilih semua hasil filter" mengambil seluruh halaman, bukan hanya yang tampil
        $ids = $request->boolean('semua_hasil')
            ? $this->filterQuery($request)->pluck('id')
            : collect((array) $request->input('ids', []))->filter()->values();

        if ($ids->isEmpty()) {
            return back()->with('gagal', 'Belum ada tugas yang dipilih.');
        }

        $target = KontrakPekerjaan::whereIn('id', $ids)
            ->where('honor_dibayar', ! $dibayar); // hanya yang statusnya memang berubah

        $jumlah = $target->update([
            'honor_dibayar' => $dibayar,
            'dibayar_pada'  => $dibayar ? now() : null,
            'dibayar_oleh'  => $dibayar ? Auth::id() : null,
        ]);

        if ($jumlah === 0) {
            return back()->with('gagal', 'Semua tugas yang dipilih sudah berstatus '
                . ($dibayar ? 'dibayar.' : 'belum dibayar.'));
        }

        return back()->with('sukses', $jumlah . ' tugas berhasil ditandai '
            . ($dibayar ? 'sudah dibayar.' : 'belum dibayar.'));
    }

    /** Export hasil filter ke CSV atau PDF */
    public function export(Request $request)
    {
        $format   = $request->input('format') === 'pdf' ? 'pdf' : 'csv';
        $daftar   = $this->filterQuery($request)
            ->with('kontrak.mitra.user', 'koordinator')
            ->latest('id')
            ->get();
        $namaFile = 'penugasan-mitra-' . now()->format('Ymd-His');

        $tgl = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '-';

        // ================= CSV =================
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($daftar, $tgl) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

                fputcsv($out, [
                    'No',
                    'Nama Mitra',
                    'Sobat ID',
                    'Uraian Pekerjaan',
                    'RO',
                    'Koordinator',
                    'Tanggal Mulai',
                    'Tanggal Selesai',
                    'Volume',
                    'Satuan',
                    'Honor',
                    'Status Pembayaran',
                    'Dibayar Pada',
                ], ';', '"', '');

                foreach ($daftar as $i => $p) {
                    $user = $p->kontrak?->mitra?->user;
                    fputcsv($out, [
                        $i + 1,
                        $user?->name,
                        '="' . $user?->username . '"', // cegah Excel mengubah angka panjang
                        $p->uraian_pekerjaan,
                        $p->ro,
                        $p->koordinator?->name,
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

        // ================= PDF =================
        $statusLabel = ['lunas' => 'Sudah Dibayar', 'belum' => 'Belum Dibayar'][$request->input('status')] ?? 'Semua';
        $keterangan  = ['Status: ' . $statusLabel];

        if (trim((string) $request->input('q')) !== '') {
            $keterangan[] = 'Pencarian: "' . trim($request->input('q')) . '"';
        }
        if ($request->filled('wilayah')) {
            $keterangan[] = 'Wilayah: ' . $request->input('wilayah');
        }
        if ($request->filled('koordinator')) {
            $keterangan[] = 'Koordinator: ' . (User::find($request->input('koordinator'))?->name ?? '-');
        }
        $tugas = array_filter((array) $request->input('tugas', []));
        if ($tugas) {
            $keterangan[] = 'Tugas: ' . implode(', ', $tugas);
        }

        $html = view('admin.export-penugasan', [
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

    /* ===================== Bagian bersama ===================== */

    private function daftarKoordinator()
    {
        return User::where('role', 'koordinator')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    /** Rapikan isian: "2,5" -> 2.5 dan "1.500.000" -> 1500000 */
    private function rapikanInput(Request $request): void
    {
        $request->merge([
            'volume'       => str_replace(',', '.', (string) $request->input('volume')),
            'harga_satuan' => preg_replace('/\D/', '', (string) $request->input('harga_satuan')),
        ]);
    }

    /** RO normal = KRO + Komponen */
    private function gabungRo(array $data): ?string
    {
        return (!empty($data['kro_ro']) && !empty($data['komponen']))
            ? $data['kro_ro'] . '.' . $data['komponen']
            : null;
    }

    /** Tugas yang honornya sudah dibayar atau kontraknya sudah diverifikasi dikunci */
    private function alasanTerkunci(KontrakPekerjaan $pekerjaan, string $kataKerja): ?string
    {
        if ($pekerjaan->honor_dibayar) {
            return 'Tugas ini tidak bisa ' . $kataKerja
                . ' karena honornya sudah ditandai dibayar. Batalkan centang pembayaran dulu.';
        }

        $kontrak = $pekerjaan->kontrak;
        if ($kontrak && ($kontrak->cek_kontrak || $kontrak->cek_spj)) {
            return 'Tugas ini tidak bisa ' . $kataKerja . ' karena kontraknya sudah diverifikasi.';
        }

        return null;
    }

    private function aturan(): array
    {
        return [
            'uraian_pekerjaan'    => 'required|string|max:255',
            'koordinator_id'      => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'koordinator')->where('is_active', true)],
            'kro_ro'              => ['nullable', 'required_with:komponen', Rule::in(config('penugasan.kro'))],
            'komponen'            => ['nullable', 'required_with:kro_ro', Rule::in(config('penugasan.komponen'))],
            'volume'              => 'required|numeric|gt:0|max:99999999',
            'satuan'              => ['required', Rule::in(config('penugasan.satuan'))],
            'harga_satuan'        => 'required|integer|min:1',
            'tanggal_mulai'       => 'required|date',
            'tanggal_selesai'     => 'required|date|after_or_equal:tanggal_mulai',
            'paket_data_pulsa'    => 'nullable|boolean',
            'ro_paket_data_pulsa' => ['nullable', 'required_if:paket_data_pulsa,1', Rule::in(config('penugasan.ro_pulsa'))],
        ];
    }

    private function pesan(): array
    {
        return [
            'uraian_pekerjaan.required'       => 'Uraian pekerjaan wajib diisi.',
            'koordinator_id.required'         => 'Pilih koordinator yang memberi tugas.',
            'koordinator_id.exists'           => 'Koordinator yang dipilih tidak valid atau tidak aktif.',
            'kro_ro.in'                       => 'Pilihan KRO tidak valid.',
            'kro_ro.required_with'            => 'KRO wajib dipilih jika Komponen diisi.',
            'komponen.in'                     => 'Pilihan komponen tidak valid.',
            'komponen.required_with'          => 'Komponen wajib dipilih jika KRO diisi.',
            'ro_paket_data_pulsa.in'          => 'Pilihan RO paket data / pulsa tidak valid.',
            'ro_paket_data_pulsa.required_if' => 'Pilih RO paket data / pulsa.',
            'volume.required'                 => 'Volume wajib diisi.',
            'volume.numeric'                  => 'Volume harus berupa angka.',
            'volume.gt'                       => 'Volume harus lebih dari 0.',
            'satuan.required'                 => 'Satuan wajib dipilih.',
            'satuan.in'                       => 'Pilihan satuan tidak valid.',
            'harga_satuan.required'           => 'Harga satuan wajib diisi.',
            'harga_satuan.min'                => 'Harga satuan harus lebih dari 0.',
            'tanggal_mulai.required'          => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required'        => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    /** Hapus banyak tugas sekaligus */
    public function hapusMassal(Request $request)
    {
        $request->validate([
            'ids'   => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        // "Pilih semua hasil filter" mengambil seluruh halaman, bukan hanya yang tampil
        $ids = $request->boolean('semua_hasil')
            ? $this->filterQuery($request)->pluck('id')
            : collect((array) $request->input('ids', []))->filter()->values();

        if ($ids->isEmpty()) {
            return back()->with('gagal', 'Belum ada tugas yang dipilih.');
        }

        $dihapus = 0;
        $dilewati = 0;

        KontrakPekerjaan::with('kontrak')
            ->whereIn('id', $ids)
            ->chunkById(200, function ($kelompok) use (&$dihapus, &$dilewati) {
                foreach ($kelompok as $pekerjaan) {
                    // Tugas yang sudah dibayar atau kontraknya terverifikasi dikunci
                    if ($this->alasanTerkunci($pekerjaan, 'dihapus')) {
                        $dilewati++;
                        continue;
                    }

                    $pekerjaan->delete();
                    $dihapus++;
                }
            });

        if ($dihapus === 0) {
            return back()->with(
                'gagal',
                'Tidak ada tugas yang dihapus. Semua tugas yang dipilih sudah dibayar atau kontraknya sudah diverifikasi.'
            );
        }

        $pesan = $dihapus . ' tugas berhasil dihapus.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' tugas dilewati karena honornya sudah dibayar atau kontraknya sudah diverifikasi.';
        }

        return back()->with('sukses', $pesan);
    }
}
