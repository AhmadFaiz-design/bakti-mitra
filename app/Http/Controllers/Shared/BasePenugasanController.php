<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use App\Models\KontrakPekerjaan;
use App\Models\Mitra;
use App\Models\Penugasan;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\BatasHonor;

abstract class BasePenugasanController extends Controller
{
    protected string $role = 'admin';
    protected string $layout = 'admin.main';
    protected string $indexRoute = 'admin.penugasan';
    protected string $routePrefix = 'admin';
    protected string $exportView = 'admin.export-penugasan';
    protected bool $hanyaTugasSendiri = false;
    protected bool $bisaPilihKoordinator = true;
    protected bool $bisaKelolaBayar = true;

    /** Daftar tugas (1 baris = 1 penugasan, tidak duplikat meski banyak mitra) */
    public function index(Request $request)
    {

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $maxQuery = DB::table('kontrak_pekerjaan as kp')
            ->join('penugasan as p', 'p.id', '=', 'kp.penugasan_id')
            ->when($this->hanyaTugasSendiri, fn($q) => $q->where('p.koordinator_id', Auth::id()))
            ->selectRaw('COALESCE(SUM(kp.jumlah), 0) as total')
            ->groupBy('kp.penugasan_id')
            ->orderByDesc('total');

        $maxTotalPerTugas = (int) ($maxQuery->value('total') ?? 0);
        $honorMaks = max(100000, (int) (ceil($maxTotalPerTugas / 100000) * 100000));

        $q = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['kosong', 'belum', 'lunas'], true)
            ? $request->query('status')
            : 'semua';

        $query = $this->filterQuery($request);

        $penugasanIdsSub = (clone $query)->select('penugasan.id');
        $ringkasanQuery = KontrakPekerjaan::whereIn('penugasan_id', $penugasanIdsSub);

        $ringkasan = [
            'total'   => (int) (clone $ringkasanQuery)->sum('jumlah'),
            'dibayar' => (int) (clone $ringkasanQuery)->where('honor_dibayar', true)->sum('jumlah'),
        ];
        $ringkasan['belum'] = $ringkasan['total'] - $ringkasan['dibayar'];

        $ringkasan = [
            'total'   => (int) (clone $ringkasanQuery)->sum('jumlah'),
            'dibayar' => (int) (clone $ringkasanQuery)->where('honor_dibayar', true)->sum('jumlah'),
        ];
        $ringkasan['belum'] = $ringkasan['total'] - $ringkasan['dibayar'];

        /* ---------- Tren bulan ini vs bulan lalu untuk badge kartu ---------- */
        $blnIni  = now('Asia/Makassar');
        $blnLalu = $blnIni->copy()->subMonthNoOverflow();

        $ambilRingkasanBulan = function (Carbon $tgl) {
            $row = DB::table('kontrak_pekerjaan as kp')
                ->join('kontrak as k', 'k.id', '=', 'kp.kontrak_id')
                ->where('k.periode_tahun', $tgl->year)
                ->where('k.periode_bulan', $tgl->month)
                ->when($this->hanyaTugasSendiri, fn($q) => $q->where('kp.koordinator_id', Auth::id()))
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

        $dataBlnIni  = $ambilRingkasanBulan($blnIni);
        $dataBlnLalu = $ambilRingkasanBulan($blnLalu);

        $hitungTren = function (int $sekarang, int $lalu, bool $balikWarna = false): array {
            if ($lalu <= 0) {
                if ($sekarang <= 0) {
                    return ['teks' => 'Tetap 0%', 'kelas' => 'neutral'];
                }

                return ['teks' => 'Baru bulan ini', 'kelas' => $balikWarna ? 'warn' : 'lime'];
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
                'koordinator:id,name,username',
                'pekerjaan' => fn($p) => $p
                    ->with([
                        'kontrak:id,mitra_id,cek_kontrak,cek_spj',
                        'kontrak.mitra:id,user_id,wilayah',
                        'kontrak.mitra.user:id,name,username',
                    ])
                    ->orderBy('id'),
            ])
            ->withCount('pekerjaan')
            ->withCount(['pekerjaan as pekerjaan_dibayar_count' => fn($p) => $p->where('honor_dibayar', true)])
            ->withSum('pekerjaan as total_volume', 'volume')
            ->withSum('pekerjaan as total_honor', 'jumlah')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $daftar->lastPage()]));
        }

        $daftarWilayah = Mitra::whereNotNull('wilayah')
            ->distinct()->orderBy('wilayah')->pluck('wilayah');

        $daftarTugas = Penugasan::whereNotNull('uraian_pekerjaan')
            ->when($this->hanyaTugasSendiri, fn($q) => $q->where('koordinator_id', Auth::id()))
            ->distinct()->orderBy('uraian_pekerjaan')->pluck('uraian_pekerjaan');

        $daftarKoordinator = $this->bisaPilihKoordinator
            ? User::where('role', 'koordinator')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('shared.penugasan.index', $this->sharedData() + compact(
            'daftar',
            'q',
            'status',
            'ringkasan',
            'tren',
            'honorMaks',
            'daftarWilayah',
            'daftarTugas',
            'daftarKoordinator',
            'perPage'
        ));
    }

    /** Form buat tugas baru (tanpa input volume & tanpa pilih mitra) */
    public function create()
    {
        return view('shared.penugasan.create', $this->sharedData() + [
            'koordinator'    => $this->bisaPilihKoordinator ? $this->daftarKoordinator() : collect(),
            'daftarKro'      => config('penugasan.kro'),
            'daftarKomponen' => config('penugasan.komponen'),
            'daftarRoPulsa'  => config('penugasan.ro_pulsa'),
            'daftarSatuan'   => config('penugasan.satuan'),
        ]);
    }

    /** Simpan tugas induk baru */
    public function store(Request $request)
    {
        $this->rapikanHarga($request);
        $data = $request->validate($this->aturanTugas(), $this->pesanTugas());

        $koordinatorId = $this->bisaPilihKoordinator
            ? (int) $data['koordinator_id']
            : (int) Auth::id();

        $pulsa = $request->boolean('paket_data_pulsa');
        $ro    = $this->gabungRo($data);

        try {
            $penugasan = Penugasan::create([
                'koordinator_id'      => $koordinatorId,
                'uraian_pekerjaan'    => $data['uraian_pekerjaan'],
                'sebagai'             => $data['sebagai'],
                'ro'                  => $ro,
                'kro_ro'              => $data['kro_ro'] ?? null,
                'komponen'            => $data['komponen'] ?? null,
                'satuan'              => $data['satuan'],
                'harga_satuan'        => (int) $data['harga_satuan'],
                'paket_data_pulsa'    => $pulsa,
                'ro_paket_data_pulsa' => $pulsa ? ($data['ro_paket_data_pulsa'] ?? null) : null,
                'tanggal_mulai'       => $data['tanggal_mulai'],
                'tanggal_selesai'     => $data['tanggal_selesai'],
                'dibuat_oleh'         => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('gagal', 'Gagal menyimpan tugas: ' . $e->getMessage());
        }

        if ($request->boolean('lanjut_assign')) {
            return redirect()
                ->route($this->routeMap()['assign'], $penugasan)
                ->with('sukses', 'Tugas "' . $penugasan->uraian_pekerjaan . '" berhasil dibuat. Silakan assign petugas dan volumenya.');
        }

        return redirect()
            ->route($this->indexRoute)
            ->with('sukses', 'Tugas "' . $penugasan->uraian_pekerjaan . '" berhasil dibuat. Klik "Assign Petugas" untuk menambahkan mitra.');
    }

    /** Halaman Assign Petugas (pilih mitra + isi volume + cek sisa kuota honor bulanan) */
    public function assignForm(Penugasan $penugasan)
    {
        $this->otorisasiTugas($penugasan);

        $penugasan->load([
            'koordinator:id,name,username',
            'pekerjaan' => fn($q) => $q->with([
                'kontrak:id,mitra_id,cek_kontrak,cek_spj',
                'kontrak.mitra:id,user_id,wilayah',
                'kontrak.mitra.user:id,name,username',
            ])->orderBy('id'),
        ]);

        $sudahAssignMitraIds = $penugasan->pekerjaan
            ->pluck('kontrak.mitra_id')
            ->filter()
            ->values()
            ->all();

        $mitraTersedia = Mitra::with('user:id,name,username,is_active')
            ->whereHas('user', fn($u) => $u->where('is_active', true))
            ->when($sudahAssignMitraIds, fn($q) => $q->whereNotIn('id', $sudahAssignMitraIds))
            ->get()
            ->sortBy(fn($m) => strtolower($m->user->name))
            ->values();

        $tanggal = Carbon::parse($penugasan->tanggal_mulai);

        // Total honor setiap mitra pada bulan & tahun tugas ini
        $honorBulanMitra = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $tanggal->year)
            ->where('k.periode_bulan', $tanggal->month)
            ->groupBy('k.mitra_id')
            ->selectRaw('k.mitra_id, COALESCE(SUM(kp.jumlah), 0) as total')
            ->pluck('total', 'mitra_id')
            ->map(fn($v) => (int) $v)
            ->all();

        // Daftar peran (Pendata/Pengolah) yang sudah dimiliki setiap mitra di bulan tersebut
        $peranBulanMitra = DB::table('kontrak as k')
            ->join('kontrak_pekerjaan as kp', 'kp.kontrak_id', '=', 'k.id')
            ->where('k.periode_tahun', $tanggal->year)
            ->where('k.periode_bulan', $tanggal->month)
            ->whereNotNull('kp.sebagai')
            ->select('k.mitra_id', 'kp.sebagai')
            ->distinct()
            ->get()
            ->groupBy('mitra_id')
            ->map(fn($rows) => $rows->pluck('sebagai')->all())
            ->all();

        // Batas efektif untuk calon mitra (gabungan peran yang sudah ada + peran tugas ini, ambil nilai tertinggi)
        $batasBulanMitra = [];
        foreach ($mitraTersedia as $m) {
            $peranGabung = array_merge($peranBulanMitra[$m->id] ?? [], [$penugasan->sebagai]);
            $batasBulanMitra[$m->id] = BatasHonor::untukMitra($m->wilayah, $peranGabung);
        }

        // Batas efektif untuk mitra yang sudah ditugaskan
        $batasExistingMitra = [];
        foreach ($penugasan->pekerjaan as $p) {
            $m = $p->kontrak?->mitra;
            if ($m) {
                $batasExistingMitra[$m->id] = BatasHonor::untukMitra(
                    $m->wilayah,
                    $peranBulanMitra[$m->id] ?? [$penugasan->sebagai]
                );
            }
        }

        return view('shared.penugasan.assign', $this->sharedData() + compact(
            'penugasan',
            'mitraTersedia',
            'honorBulanMitra',
            'batasBulanMitra',
            'batasExistingMitra'
        ));
    }

    /** Simpan assign beberapa mitra beserta volume masing-masing */
    public function assignStore(Request $request, Penugasan $penugasan)
    {
        $this->otorisasiTugas($penugasan);

        $volumes = collect((array) $request->input('volumes', []))
            ->map(fn($v) => is_string($v) ? str_replace(',', '.', trim($v)) : $v)
            ->all();
        $request->merge(['volumes' => $volumes]);

        $data = $request->validate(
            [
                'mitra_id'   => 'required|array|min:1',
                'mitra_id.*' => 'integer|exists:mitra,id',
                'volumes'    => 'required|array',
            ],
            [
                'mitra_id.required' => 'Pilih minimal satu mitra yang akan ditugaskan.',
                'mitra_id.min'      => 'Pilih minimal satu mitra yang akan ditugaskan.',
            ]
        );

        $errors = [];
        foreach ($data['mitra_id'] as $mitraId) {
            $vol = $volumes[$mitraId] ?? null;
            if ($vol === null || $vol === '' || !is_numeric($vol) || (float) $vol <= 0 || (float) $vol > 99999999) {
                $errors["volumes.$mitraId"] = 'Volume untuk setiap mitra yang dipilih harus berupa angka lebih dari 0.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $tanggal     = Carbon::parse($penugasan->tanggal_mulai);
        $hargaSatuan = (int) $penugasan->harga_satuan;

        $dibuat       = 0;
        $dilewati     = [];
        $terkunci     = [];
        $lewatBatas   = [];

        try {
            DB::transaction(function () use (
                $penugasan,
                $data,
                $volumes,
                $tanggal,
                $hargaSatuan,
                &$dibuat,
                &$dilewati,
                &$terkunci,
                &$lewatBatas
            ) {
                $daftar = Mitra::with('user')
                    ->whereIn('id', $data['mitra_id'])
                    ->whereHas('user', fn($u) => $u->where('is_active', true))
                    ->get();

                foreach ($daftar as $mitra) {
                    $sudahDiTugasIni = $penugasan->pekerjaan()
                        ->whereHas('kontrak', fn($k) => $k->where('mitra_id', $mitra->id))
                        ->exists();

                    if ($sudahDiTugasIni) {
                        $dilewati[] = $mitra->user->name;
                        continue;
                    }

                    $kontrak = Kontrak::firstOrCreate(
                        [
                            'mitra_id'      => $mitra->id,
                            'periode_tahun' => $tanggal->year,
                            'periode_bulan' => $tanggal->month,
                        ],
                        ['dibuat_oleh' => Auth::id()]
                    );

                    if ($kontrak->cek_kontrak || $kontrak->cek_spj) {
                        $terkunci[] = $mitra->user->name;
                        continue;
                    }

                    $kembarPadaKontrak = $kontrak->pekerjaan()
                        ->where('uraian_pekerjaan', $penugasan->uraian_pekerjaan)
                        ->where('sebagai', $penugasan->sebagai)
                        ->exists();

                    if ($kembarPadaKontrak) {
                        $dilewati[] = $mitra->user->name;
                        continue;
                    }

                    $honorSaatIni = (int) $kontrak->pekerjaan()->sum('jumlah');
                    $peranAda     = $kontrak->pekerjaan()->whereNotNull('sebagai')->pluck('sebagai')->all();
                    $batasMitra   = BatasHonor::untukMitra($mitra->wilayah, array_merge($peranAda, [$penugasan->sebagai]));
                    $vol          = (float) $volumes[$mitra->id];
                    $jumlah       = (int) round($vol * $hargaSatuan);

                    if ($batasMitra > 0 && ($honorSaatIni + $jumlah) > $batasMitra) {
                        $lewatBatas[] = $mitra->user->name . ' (Maks Rp ' . number_format($batasMitra, 0, ',', '.') . ')';
                    }

                    $kontrak->pekerjaan()->create([
                        'penugasan_id'        => $penugasan->id,
                        'koordinator_id'      => $penugasan->koordinator_id,
                        'urutan'              => (int) $kontrak->pekerjaan()->max('urutan') + 1,
                        'uraian_pekerjaan'    => $penugasan->uraian_pekerjaan,
                        'sebagai'             => $penugasan->sebagai,
                        'ro'                  => $penugasan->ro,
                        'kro_ro'              => $penugasan->kro_ro,
                        'komponen'            => $penugasan->komponen,
                        'volume'              => $vol,
                        'satuan'              => $penugasan->satuan,
                        'harga_satuan'        => $hargaSatuan,
                        'jumlah'              => $jumlah,
                        'paket_data_pulsa'    => (bool) $penugasan->paket_data_pulsa,
                        'ro_paket_data_pulsa' => $penugasan->ro_paket_data_pulsa,
                        'tanggal_mulai'       => $penugasan->tanggal_mulai,
                        'tanggal_selesai'     => $penugasan->tanggal_selesai,
                    ]);

                    $dibuat++;
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('gagal', 'Gagal mengassign petugas: ' . $e->getMessage());
        }

        if ($dibuat === 0) {
            $detail = [];
            if ($dilewati) {
                $detail[] = count($dilewati) . ' mitra sudah punya tugas yang sama pada bulan tersebut';
            }
            if ($terkunci) {
                $detail[] = count($terkunci) . ' mitra kontraknya sudah diverifikasi';
            }

            return back()->withInput()->with(
                'gagal',
                'Tidak ada petugas yang berhasil diassign' . ($detail ? ' (' . implode('; ', $detail) . ').' : '.')
            );
        }

        $pesan = $dibuat . ' mitra berhasil ditugaskan.';
        if ($dilewati) {
            $pesan .= ' ' . count($dilewati) . ' mitra dilewati karena sudah memiliki tugas yang sama.';
        }
        if ($terkunci) {
            $pesan .= ' ' . count($terkunci) . ' mitra dilewati karena kontrak bulan tersebut sudah diverifikasi.';
        }
        if ($lewatBatas) {
            $pesan .= ' Perhatian: total honor bulan ini melebihi batas untuk ' . implode(', ', $lewatBatas) . '.';
        }

        return redirect()
            ->route($this->routeMap()['assign'], $penugasan)
            ->with('sukses', $pesan);
    }

    /** Ubah volume 1 mitra pada halaman Assign Petugas */
    public function updatePetugas(Request $request, Penugasan $penugasan, KontrakPekerjaan $pekerjaan)
    {
        $this->otorisasiTugas($penugasan);
        abort_if((int) $pekerjaan->penugasan_id !== (int) $penugasan->id, 404);

        $pekerjaan->load('kontrak.mitra.user');
        if ($alasan = $this->alasanTerkunci($pekerjaan, 'diubah')) {
            return back()->with('gagal', $alasan);
        }

        $request->merge([
            'volume' => str_replace(',', '.', trim((string) $request->input('volume'))),
        ]);

        $data = $request->validate([
            'volume' => 'required|numeric|gt:0|max:99999999',
        ], [
            'volume.required' => 'Volume wajib diisi.',
            'volume.numeric'  => 'Volume harus berupa angka.',
            'volume.gt'       => 'Volume harus lebih dari 0.',
        ]);

        $vol    = (float) $data['volume'];
        $jumlah = (int) round($vol * (int) $pekerjaan->harga_satuan);
        $nama   = $pekerjaan->kontrak?->mitra?->user?->name ?? 'mitra';

        $pekerjaan->update([
            'volume' => $vol,
            'jumlah' => $jumlah,
        ]);

        $kontrak    = $pekerjaan->kontrak;
        $peranAda   = $kontrak ? $kontrak->pekerjaan()->whereNotNull('sebagai')->pluck('sebagai')->all() : [];
        $batasMitra = BatasHonor::untukMitra($kontrak?->mitra?->wilayah, $peranAda ?: [$pekerjaan->sebagai]);
        $totalBulan = (int) ($kontrak?->pekerjaan()->sum('jumlah') ?? 0);
        $pesan      = 'Volume untuk ' . $nama . ' berhasil diperbarui.';

        if ($batasMitra > 0 && $totalBulan > $batasMitra) {
            $pesan .= ' Perhatian: total honor bulan ini untuk ' . $nama . ' melebihi batas (Maks Rp '
                . number_format($batasMitra, 0, ',', '.') . ').';
        }

        return redirect()
            ->route($this->routeMap()['assign'], $penugasan)
            ->with('sukses', $pesan);
    }

    /** Hapus / keluarkan 1 mitra dari tugas & bersihkan kontrak jika kosong */
    public function hapusPetugas(Penugasan $penugasan, KontrakPekerjaan $pekerjaan)
    {
        $this->otorisasiTugas($penugasan);
        abort_if((int) $pekerjaan->penugasan_id !== (int) $penugasan->id, 404);

        $pekerjaan->load('kontrak.mitra.user');
        if ($alasan = $this->alasanTerkunci($pekerjaan, 'dihapus')) {
            return back()->with('gagal', $alasan);
        }

        $nama    = $pekerjaan->kontrak?->mitra?->user?->name ?? 'mitra';
        $kontrak = $pekerjaan->kontrak;

        DB::transaction(function () use ($pekerjaan, $kontrak) {
            $pekerjaan->delete();
            $this->bersihkanKontrakKosong($kontrak);
        });

        return redirect()
            ->route($this->routeMap()['assign'], $penugasan)
            ->with('sukses', $nama . ' berhasil dikeluarkan dari tugas.');
    }

    /** Toggle bayar honor untuk 1 mitra (hanya jika $bisaKelolaBayar = true) */
    public function ubahStatusBayarPekerjaan(Request $request, KontrakPekerjaan $pekerjaan)
    {
        abort_unless($this->bisaKelolaBayar, 403);
        if ($this->hanyaTugasSendiri && (int) $pekerjaan->koordinator_id !== (int) Auth::id()) {
            abort(403);
        }

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

    /** Toggle bayar seluruh mitra pada 1 Penugasan (hanya jika $bisaKelolaBayar = true) */
    public function ubahStatusBayar(Request $request, Penugasan $penugasan)
    {
        abort_unless($this->bisaKelolaBayar, 403);
        $this->otorisasiTugas($penugasan);

        if ($penugasan->pekerjaan()->count() === 0) {
            return response()->json([
                'message' => 'Belum ada petugas yang diassign pada tugas ini.',
            ], 422);
        }

        $dibayar = $request->boolean('dibayar');

        $penugasan->pekerjaan()
            ->where('honor_dibayar', !$dibayar)
            ->update([
                'honor_dibayar' => $dibayar,
                'dibayar_pada'  => $dibayar ? now() : null,
                'dibayar_oleh'  => $dibayar ? Auth::id() : null,
            ]);

        return response()->json([
            'dibayar'      => $dibayar,
            'dibayar_pada' => $dibayar ? now()->format('d/m/Y H:i') : null,
        ]);
    }

    /** Form edit tugas induk */
    public function edit(Penugasan $penugasan)
    {
        $this->otorisasiTugas($penugasan);
        $penugasan->load('pekerjaan.kontrak');

        if ($alasan = $this->alasanTugasTerkunci($penugasan, 'diubah')) {
            return redirect()->route($this->indexRoute)->with('gagal', $alasan);
        }

        return view('shared.penugasan.edit', $this->sharedData() + [
            'penugasan'      => $penugasan,
            'koordinator'    => $this->bisaPilihKoordinator ? $this->daftarKoordinator() : collect(),
            'daftarKro'      => config('penugasan.kro'),
            'daftarKomponen' => config('penugasan.komponen'),
            'daftarRoPulsa'  => config('penugasan.ro_pulsa'),
            'daftarSatuan'   => config('penugasan.satuan'),
        ]);
    }

    /** Simpan perubahan tugas induk dan sinkronkan ke seluruh mitra */
    public function update(Request $request, Penugasan $penugasan)
    {
        $this->otorisasiTugas($penugasan);
        $penugasan->load('pekerjaan.kontrak.mitra.user');

        if ($alasan = $this->alasanTugasTerkunci($penugasan, 'diubah')) {
            return redirect()->route($this->indexRoute)->with('gagal', $alasan);
        }

        $this->rapikanHarga($request);
        $data = $request->validate($this->aturanTugas(), $this->pesanTugas());

        $koordinatorId = $this->bisaPilihKoordinator
            ? (int) $data['koordinator_id']
            : (int) $penugasan->koordinator_id;

        $tanggal     = Carbon::parse($data['tanggal_mulai']);
        $pulsa       = $request->boolean('paket_data_pulsa');
        $ro          = $this->gabungRo($data);
        $hargaSatuan = (int) $data['harga_satuan'];

        try {
            DB::transaction(function () use ($penugasan, $data, $koordinatorId, $tanggal, $pulsa, $ro, $hargaSatuan) {
                $penugasan->update([
                    'koordinator_id'      => $koordinatorId,
                    'uraian_pekerjaan'    => $data['uraian_pekerjaan'],
                    'sebagai'             => $data['sebagai'],
                    'ro'                  => $ro,
                    'kro_ro'              => $data['kro_ro'] ?? null,
                    'komponen'            => $data['komponen'] ?? null,
                    'satuan'              => $data['satuan'],
                    'harga_satuan'        => $hargaSatuan,
                    'paket_data_pulsa'    => $pulsa,
                    'ro_paket_data_pulsa' => $pulsa ? ($data['ro_paket_data_pulsa'] ?? null) : null,
                    'tanggal_mulai'       => $data['tanggal_mulai'],
                    'tanggal_selesai'     => $data['tanggal_selesai'],
                ]);

                foreach ($penugasan->pekerjaan as $pekerjaan) {
                    $kontrakLama = $pekerjaan->kontrak;

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
                        $namaMitra = $kontrakLama->mitra?->user?->name ?? 'mitra';
                        throw ValidationException::withMessages([
                            'tanggal_mulai' => 'Kontrak ' . $namaMitra . ' pada bulan '
                                . $tanggal->translatedFormat('F Y')
                                . ' sudah diverifikasi, tugas tidak bisa dipindahkan ke sana.',
                        ]);
                    }

                    $kembar = $kontrak->pekerjaan()
                        ->where('uraian_pekerjaan', $data['uraian_pekerjaan'])
                        ->where('sebagai', $data['sebagai'])
                        ->where('id', '!=', $pekerjaan->id)
                        ->exists();

                    if ($kembar) {
                        $namaMitra = $kontrakLama->mitra?->user?->name ?? 'mitra';
                        throw ValidationException::withMessages([
                            'uraian_pekerjaan' => $namaMitra . ' sudah memiliki tugas dengan uraian yang sama pada bulan tersebut.',
                        ]);
                    }

                    if ($pindah) {
                        $pekerjaan->kontrak()->associate($kontrak);
                        $pekerjaan->urutan = (int) $kontrak->pekerjaan()->max('urutan') + 1;
                    }

                    $pekerjaan->forceFill([
                        'koordinator_id'      => $koordinatorId,
                        'uraian_pekerjaan'    => $data['uraian_pekerjaan'],
                        'sebagai'             => $data['sebagai'],
                        'ro'                  => $ro,
                        'kro_ro'              => $data['kro_ro'] ?? null,
                        'komponen'            => $data['komponen'] ?? null,
                        'satuan'              => $data['satuan'],
                        'harga_satuan'        => $hargaSatuan,
                        'jumlah'              => (int) round(((float) $pekerjaan->volume) * $hargaSatuan),
                        'paket_data_pulsa'    => $pulsa,
                        'ro_paket_data_pulsa' => $pulsa ? ($data['ro_paket_data_pulsa'] ?? null) : null,
                        'tanggal_mulai'       => $data['tanggal_mulai'],
                        'tanggal_selesai'     => $data['tanggal_selesai'],
                    ])->save();
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('gagal', 'Gagal menyimpan perubahan: ' . $e->getMessage());
        }

        return redirect()
            ->route($this->indexRoute)
            ->with('sukses', 'Tugas "' . $data['uraian_pekerjaan'] . '" berhasil diperbarui.');
    }

    /** Hapus 1 tugas beserta seluruh penugasan mitranya */
    public function destroy(Penugasan $penugasan)
    {
        $this->otorisasiTugas($penugasan);
        $penugasan->load('pekerjaan.kontrak');

        if ($alasan = $this->alasanTugasTerkunci($penugasan, 'dihapus')) {
            return back()->with('gagal', $alasan);
        }

        $nama = $penugasan->uraian_pekerjaan;

        DB::transaction(function () use ($penugasan) {
            $kontraks = $penugasan->pekerjaan->pluck('kontrak')->filter()->unique('id');
            $penugasan->pekerjaan()->delete();
            $penugasan->delete();

            foreach ($kontraks as $kontrak) {
                $this->bersihkanKontrakKosong($kontrak);
            }
        });

        return redirect()
            ->route($this->indexRoute)
            ->with('sukses', 'Tugas "' . $nama . '" berhasil dihapus.');
    }

    /** Query penugasan induk dengan filter role, pencarian, status, dan panel filter */
    protected function filterQuery(Request $request): Builder
    {
        $q      = trim((string) $request->input('q', ''));
        $status = $request->input('status');

        $query = Penugasan::query()
            ->when($this->hanyaTugasSendiri, fn($query) => $query->where('koordinator_id', Auth::id()))
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('uraian_pekerjaan', 'like', "%{$q}%")
                    ->orWhere('sebagai', 'like', "%{$q}%")
                    ->orWhere('ro', 'like', "%{$q}%")
                    ->orWhereHas('koordinator', fn($k) => $k
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"))
                    ->orWhereHas('pekerjaan.kontrak.mitra.user', fn($u) => $u
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            }))
            ->when($status === 'kosong', fn($query) => $query->doesntHave('pekerjaan'))
            ->when($status === 'lunas', fn($query) => $query
                ->has('pekerjaan')
                ->whereDoesntHave('pekerjaan', fn($p) => $p->where('honor_dibayar', false)))
            ->when($status === 'belum', fn($query) => $query
                ->whereHas('pekerjaan', fn($p) => $p->where('honor_dibayar', false)));

        $parse = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
        $min = $max = null;

        if ($request->input('honor_mode') === 'rentang') {
            if ($request->filled('honor_dari'))   $min = $parse($request->input('honor_dari'));
            if ($request->filled('honor_sampai')) $max = $parse($request->input('honor_sampai'));
        } elseif ($request->filled('honor_jumlah')) {
            $min = $max = $parse($request->input('honor_jumlah'));
        }

        if ($min !== null) {
            $query->whereRaw(
                '(SELECT COALESCE(SUM(jumlah), 0) FROM kontrak_pekerjaan WHERE kontrak_pekerjaan.penugasan_id = penugasan.id) >= ?',
                [$min]
            );
        }
        if ($max !== null) {
            $query->whereRaw(
                '(SELECT COALESCE(SUM(jumlah), 0) FROM kontrak_pekerjaan WHERE kontrak_pekerjaan.penugasan_id = penugasan.id) <= ?',
                [$max]
            );
        }

        if ($request->filled('wilayah')) {
            $query->whereHas('pekerjaan.kontrak.mitra', fn($m) => $m->where('wilayah', $request->input('wilayah')));
        }

        if ($this->bisaPilihKoordinator && $request->filled('koordinator')) {
            $query->where('koordinator_id', (int) $request->input('koordinator'));
        }

        $tugas = array_values(array_filter((array) $request->input('tugas', [])));
        if ($tugas) {
            $query->whereIn('uraian_pekerjaan', $tugas);
        }

        return $query;
    }

    /** Bayar massal (hanya jika $bisaKelolaBayar = true) */
    public function bayarMassal(Request $request)
    {
        abort_unless($this->bisaKelolaBayar, 403);

        $request->validate([
            'aksi'  => 'required|in:bayar,batal',
            'ids'   => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        $dibayar = $request->input('aksi') === 'bayar';

        $penugasanIds = $request->boolean('semua_hasil')
            ? $this->filterQuery($request)->pluck('penugasan.id')
            : Penugasan::query()
            ->when($this->hanyaTugasSendiri, fn($q) => $q->where('koordinator_id', Auth::id()))
            ->whereIn('id', collect((array) $request->input('ids', []))->filter()->values())
            ->pluck('id');

        if ($penugasanIds->isEmpty()) {
            return back()->with('gagal', 'Belum ada tugas yang dipilih.');
        }

        $jumlah = KontrakPekerjaan::whereIn('penugasan_id', $penugasanIds)
            ->where('honor_dibayar', !$dibayar)
            ->update([
                'honor_dibayar' => $dibayar,
                'dibayar_pada'  => $dibayar ? now() : null,
                'dibayar_oleh'  => $dibayar ? Auth::id() : null,
            ]);

        if ($jumlah === 0) {
            return back()->with('gagal', 'Tidak ada data honor petugas yang berubah (belum ada petugas atau semua sudah berstatus '
                . ($dibayar ? 'dibayar).' : 'belum dibayar).'));
        }

        return back()->with('sukses', $jumlah . ' penugasan mitra berhasil ditandai '
            . ($dibayar ? 'sudah dibayar.' : 'belum dibayar.'));
    }

    /** Hapus banyak tugas sekaligus */
    public function hapusMassal(Request $request)
    {
        $request->validate([
            'ids'   => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->boolean('semua_hasil')
            ? $this->filterQuery($request)->pluck('penugasan.id')
            : Penugasan::query()
            ->when($this->hanyaTugasSendiri, fn($q) => $q->where('koordinator_id', Auth::id()))
            ->whereIn('id', collect((array) $request->input('ids', []))->filter()->values())
            ->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('gagal', 'Belum ada tugas yang dipilih.');
        }

        $dihapus  = 0;
        $dilewati = 0;

        Penugasan::with('pekerjaan.kontrak')
            ->whereIn('id', $ids)
            ->chunkById(100, function ($kelompok) use (&$dihapus, &$dilewati) {
                foreach ($kelompok as $penugasan) {
                    if ($this->alasanTugasTerkunci($penugasan, 'dihapus')) {
                        $dilewati++;
                        continue;
                    }

                    DB::transaction(function () use ($penugasan) {
                        $kontraks = $penugasan->pekerjaan->pluck('kontrak')->filter()->unique('id');
                        $penugasan->pekerjaan()->delete();
                        $penugasan->delete();

                        foreach ($kontraks as $kontrak) {
                            $this->bersihkanKontrakKosong($kontrak);
                        }
                    });
                    $dihapus++;
                }
            });

        if ($dihapus === 0) {
            return back()->with(
                'gagal',
                'Tidak ada tugas yang dihapus. Semua tugas yang dipilih memiliki honor sudah dibayar atau kontrak sudah diverifikasi.'
            );
        }

        $pesan = $dihapus . ' tugas berhasil dihapus.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' tugas dilewati karena memiliki petugas yang honornya sudah dibayar atau kontraknya sudah diverifikasi.';
        }

        return back()->with('sukses', $pesan);
    }

    /** Export hasil filter ke CSV atau PDF */
    public function export(Request $request)
    {
        $format       = $request->input('format') === 'pdf' ? 'pdf' : 'csv';
        $penugasanIds = $this->filterQuery($request)->pluck('penugasan.id');

        $daftar = KontrakPekerjaan::whereIn('penugasan_id', $penugasanIds)
            ->with('kontrak.mitra.user', 'koordinator')
            ->latest('id')
            ->get();

        $namaFile = 'penugasan-mitra-' . now()->format('Ymd-His');
        $tgl = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '-';

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($daftar, $tgl) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");

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
                        '="' . $user?->username . '"',
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

        $statusLabel = [
            'kosong' => 'Belum Ada Petugas',
            'lunas'  => 'Sudah Dibayar',
            'belum'  => 'Belum Dibayar',
        ][$request->input('status')] ?? 'Semua';

        $keterangan = ['Status: ' . $statusLabel];

        if (trim((string) $request->input('q')) !== '') {
            $keterangan[] = 'Pencarian: "' . trim($request->input('q')) . '"';
        }
        if ($request->filled('wilayah')) {
            $keterangan[] = 'Wilayah: ' . $request->input('wilayah');
        }
        if ($this->bisaPilihKoordinator && $request->filled('koordinator')) {
            $keterangan[] = 'Koordinator: ' . (User::find($request->input('koordinator'))?->name ?? '-');
        }
        $tugas = array_filter((array) $request->input('tugas', []));
        if ($tugas) {
            $keterangan[] = 'Tugas: ' . implode(', ', $tugas);
        }

        $html = view($this->exportView, [
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

    /* ===================== Shared Context & Helper ===================== */

    protected function bersihkanKontrakKosong(?Kontrak $kontrak): void
    {
        if (!$kontrak) {
            return;
        }

        $kosong        = $kontrak->pekerjaan()->doesntExist();
        $belumBernomor = empty($kontrak->nomor_kontrak) && empty($kontrak->nomor_urut_kontrak);

        if ($kosong && $belumBernomor && !$kontrak->cek_kontrak && !$kontrak->cek_spj) {
            $kontrak->delete();
        }
    }

    protected function routeMap(): array
    {
        return [
            'index'          => $this->indexRoute,
            'buat'           => $this->routePrefix . '.penugasan.buat',
            'simpan'         => $this->routePrefix . '.penugasan.simpan',
            'edit'           => $this->routePrefix . '.penugasan.edit',
            'update'         => $this->routePrefix . '.penugasan.update',
            'hapus'          => $this->routePrefix . '.penugasan.hapus',
            'bayar'          => $this->routePrefix . '.penugasan.bayar',
            'assign'         => $this->routePrefix . '.penugasan.assign',
            'assignSimpan'   => $this->routePrefix . '.penugasan.assign.simpan',
            'petugasUpdate'  => $this->routePrefix . '.penugasan.petugas.update',
            'petugasHapus'   => $this->routePrefix . '.penugasan.petugas.hapus',
            'pekerjaanBayar' => $this->routePrefix . '.penugasan.pekerjaan.bayar',
            'export'         => $this->routePrefix . '.penugasan.export',
            'bayarMassal'    => $this->routePrefix . '.penugasan.bayar-massal',
            'hapusMassal'    => $this->routePrefix . '.penugasan.hapus-massal',
        ];
    }

    protected function sharedData(): array
    {
        return [
            'role'                 => $this->role,
            'layout'               => $this->layout,
            'routes'               => $this->routeMap(),
            'bisaPilihKoordinator' => $this->bisaPilihKoordinator,
            'bisaKelolaBayar'      => $this->bisaKelolaBayar,
        ];
    }

    protected function otorisasiTugas(Penugasan $penugasan): void
    {
        if ($this->hanyaTugasSendiri && (int) $penugasan->koordinator_id !== (int) Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke tugas ini.');
        }
    }

    private function daftarKoordinator()
    {
        return User::where('role', 'koordinator')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    private function rapikanHarga(Request $request): void
    {
        $request->merge([
            'harga_satuan' => preg_replace('/\D/', '', (string) $request->input('harga_satuan')),
        ]);
    }

    private function gabungRo(array $data): ?string
    {
        return (!empty($data['kro_ro']) && !empty($data['komponen']))
            ? $data['kro_ro'] . '.' . $data['komponen']
            : null;
    }

    private function alasanTerkunci(KontrakPekerjaan $pekerjaan, string $kataKerja): ?string
    {
        if ($pekerjaan->honor_dibayar) {
            return 'Data ini tidak bisa ' . $kataKerja
                . ' karena honornya sudah ditandai dibayar.';
        }

        $kontrak = $pekerjaan->kontrak;
        if ($kontrak && ($kontrak->cek_kontrak || $kontrak->cek_spj)) {
            return 'Data ini tidak bisa ' . $kataKerja . ' karena kontraknya sudah diverifikasi.';
        }

        return null;
    }

    private function alasanTugasTerkunci(Penugasan $penugasan, string $kataKerja): ?string
    {
        foreach ($penugasan->pekerjaan as $p) {
            if ($p->honor_dibayar) {
                return 'Tugas ini tidak bisa ' . $kataKerja
                    . ' karena sudah ada petugas yang honornya dibayar.';
            }

            $kontrak = $p->kontrak;
            if ($kontrak && ($kontrak->cek_kontrak || $kontrak->cek_spj)) {
                return 'Tugas ini tidak bisa ' . $kataKerja
                    . ' karena kontrak salah satu petugas pada tugas ini sudah diverifikasi.';
            }
        }

        return null;
    }

    private function aturanTugas(): array
    {
        $aturan = [
            'uraian_pekerjaan'    => 'required|string|max:255',
            'sebagai'             => ['required', Rule::in(['Pengolah', 'Pendata'])],
            'kro_ro'              => ['nullable', 'required_with:komponen', Rule::in(config('penugasan.kro'))],
            'komponen'            => ['nullable', 'required_with:kro_ro', Rule::in(config('penugasan.komponen'))],
            'satuan'              => ['required', Rule::in(config('penugasan.satuan'))],
            'harga_satuan'        => 'required|integer|min:1',
            'tanggal_mulai'       => 'required|date',
            'tanggal_selesai'     => 'required|date|after_or_equal:tanggal_mulai',
            'paket_data_pulsa'    => 'nullable|boolean',
            'ro_paket_data_pulsa' => ['nullable', 'required_if:paket_data_pulsa,1', Rule::in(config('penugasan.ro_pulsa'))],
        ];

        if ($this->bisaPilihKoordinator) {
            $aturan['koordinator_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'koordinator')->where('is_active', true),
            ];
        }

        return $aturan;
    }

    private function pesanTugas(): array
    {
        return [
            'uraian_pekerjaan.required'       => 'Uraian pekerjaan wajib diisi.',
            'sebagai.required'                => 'Pilih peran tugas (Pengolah atau Pendata).',
            'sebagai.in'                      => 'Pilihan peran tidak valid.',
            'koordinator_id.required'         => 'Pilih koordinator yang memegang tugas.',
            'koordinator_id.exists'           => 'Koordinator yang dipilih tidak valid atau tidak aktif.',
            'kro_ro.in'                       => 'Pilihan KRO tidak valid.',
            'kro_ro.required_with'            => 'KRO wajib dipilih jika Komponen diisi.',
            'komponen.in'                     => 'Pilihan komponen tidak valid.',
            'komponen.required_with'          => 'Komponen wajib dipilih jika KRO diisi.',
            'ro_paket_data_pulsa.in'          => 'Pilihan RO paket data / pulsa tidak valid.',
            'ro_paket_data_pulsa.required_if' => 'Pilih RO paket data / pulsa.',
            'satuan.required'                 => 'Satuan wajib dipilih.',
            'satuan.in'                       => 'Pilihan satuan tidak valid.',
            'harga_satuan.required'           => 'Harga satuan wajib diisi.',
            'harga_satuan.min'                => 'Harga satuan harus lebih dari 0.',
            'tanggal_mulai.required'          => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required'        => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
