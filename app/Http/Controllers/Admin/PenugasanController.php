<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use App\Models\KontrakPekerjaan;
use App\Models\Mitra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PenugasanController extends Controller
{
    /** Daftar semua tugas yang sudah diberikan */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $daftar = KontrakPekerjaan::query()
            ->with('kontrak.mitra.user', 'koordinator')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('uraian_pekerjaan', 'like', "%{$q}%")
                  ->orWhereHas('kontrak.mitra.user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            }))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.penugasan', compact('daftar', 'q'));
    }

    /** Form beri tugas */
    public function create()
    {
        $mitra = Mitra::with('user:id,name,username,is_active')
            ->whereHas('user', fn ($u) => $u->where('is_active', true))
            ->get()
            ->sortBy(fn ($m) => strtolower($m->user->name))
            ->values();

        $koordinator = User::where('role', 'koordinator')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return view('admin.tambah-penugasan', [
            'mitra'          => $mitra,
            'koordinator'    => $koordinator,
            'daftarKro'      => config('penugasan.kro'),
            'daftarKomponen' => config('penugasan.komponen'),
            'daftarRoPulsa'  => config('penugasan.ro_pulsa'),
            'daftarSatuan'   => config('penugasan.satuan'),
        ]);
    }

    /** Simpan: satu tugas diberikan ke banyak mitra sekaligus */
    public function store(Request $request)
    {
        // Rapikan isian: "2,5" -> 2.5 dan "1.500.000" -> 1500000
        $request->merge([
            'volume'       => str_replace(',', '.', (string) $request->input('volume')),
            'harga_satuan' => preg_replace('/\D/', '', (string) $request->input('harga_satuan')),
        ]);

        $data = $request->validate([
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
            'mitra_id'            => 'required|array|min:1',
            'mitra_id.*'          => 'integer|exists:mitra,id',
        ], [
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
            'mitra_id.required'               => 'Pilih minimal satu mitra.',
            'mitra_id.min'                    => 'Pilih minimal satu mitra.',
        ]);

        // Bulan kontrak mengikuti tanggal mulai
        $tanggal = Carbon::parse($data['tanggal_mulai']);
        $jumlah  = (int) round(((float) $data['volume']) * ((int) $data['harga_satuan']));
        $pulsa   = $request->boolean('paket_data_pulsa');

        // RO normal = KRO + Komponen
        $ro = (!empty($data['kro_ro']) && !empty($data['komponen']))
            ? $data['kro_ro'] . '.' . $data['komponen']
            : null;

        $koordinatorId = (int) $data['koordinator_id'];

        $dibuat   = 0;
        $dilewati = [];

        try {
            DB::transaction(function () use ($data, $tanggal, $jumlah, $pulsa, $ro, $koordinatorId, &$dibuat, &$dilewati) {
                $daftarMitra = Mitra::with('user')
                    ->whereIn('id', $data['mitra_id'])
                    ->whereHas('user', fn ($u) => $u->where('is_active', true))
                    ->get();

                foreach ($daftarMitra as $mitra) {
                    // Kontrak bulan itu dibuat otomatis jika belum ada
                    $kontrak = Kontrak::firstOrCreate(
                        [
                            'mitra_id'      => $mitra->id,
                            'periode_tahun' => $tanggal->year,
                            'periode_bulan' => $tanggal->month,
                        ],
                        ['dibuat_oleh' => auth()->id()]
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

    /** Hapus satu tugas milik satu mitra */
    public function destroy(KontrakPekerjaan $pekerjaan)
    {
        $pekerjaan->load('kontrak');

        // Kontrak yang sudah diverifikasi tidak boleh diubah isinya
        if ($pekerjaan->kontrak && ($pekerjaan->kontrak->cek_kontrak || $pekerjaan->kontrak->cek_spj)) {
            return back()->with(
                'gagal',
                'Tugas ini tidak bisa dihapus karena kontraknya sudah diverifikasi.'
            );
        }

        $nama = $pekerjaan->uraian_pekerjaan;
        $pekerjaan->delete();

        return redirect()
            ->route('admin.penugasan')
            ->with('sukses', 'Tugas "' . $nama . '" berhasil dihapus.');
    }
}
