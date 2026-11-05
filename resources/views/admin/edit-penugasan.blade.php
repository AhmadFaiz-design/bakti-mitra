@php
    $p = $pekerjaan;
    $fmtTanggal = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('Y-m-d') : '';
    $volumeAwal = rtrim(rtrim(number_format((float) $p->volume, 2, ',', ''), '0'), ',');
    $pulsaAwal = old('_token') ? old('paket_data_pulsa') : $p->paket_data_pulsa;
@endphp

<x-admin.main class="create-page">
    <style>
        .form-group[hidden] {
            display: none;
        }

        /* Checkbox: kembalikan ke ukuran normal (menimpa style input global) */
        .form-group label.cek input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            flex: none;
            padding: 0;
            margin: 0;
            border: 0;
            box-shadow: none;
            background: none;
            accent-color: #0d6efd;
            cursor: pointer;
        }

        .form-group label.cek {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .info-mitra {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 1px solid #d5e0da;
            border-radius: 10px;
            background: #f7faf8;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .info-mitra .inisial {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8e4fc;
            color: #5b45d6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex: none;
        }

        .info-mitra small {
            display: block;
            color: #5f7068;
        }

        .catatan {
            font-size: 13px;
            color: #5f7068;
            margin: 12px 0 0;
        }
    </style>

    {{-- HEADER HALAMAN --}}
    <header class="page-header">
        <div class="page-title">
            <h1>Edit Tugas</h1>
            <p>Ubah detail tugas untuk satu mitra</p>
        </div>

        <div class="page-actions">
            <span class="username">{{ auth()->user()->name }}</span>
            <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    <form id="editPenugasanForm" action="{{ route('admin.penugasan.update', $p) }}" method="POST">
        @csrf
        @method('PUT')

        @if (session('gagal'))
            <div role="alert"
                style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px">
                {{ session('gagal') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert"
                style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px">
                <b>Perubahan belum bisa disimpan:</b>
                <ul style="margin:6px 0 0 18px;padding:0">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ======================== --}}
        {{-- DETAIL PEKERJAAN         --}}
        {{-- ======================== --}}
        <section class="form-card">
            <h3 style="margin:0 0 16px;font-size:16px">Detail Pekerjaan</h3>

            <div class="info-mitra">
                <div class="inisial">{{ strtoupper(mb_substr($user?->name ?? '?', 0, 1)) }}</div>
                <div>
                    <b>{{ $user?->name ?? '-' }}</b>
                    <small>{{ $user?->username }} · Mitra tidak dapat diganti. Untuk mitra lain, beri tugas baru.</small>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="uraian_pekerjaan">Uraian Pekerjaan</label>
                    <input type="text" id="uraian_pekerjaan" name="uraian_pekerjaan"
                        value="{{ old('uraian_pekerjaan', $p->uraian_pekerjaan) }}"
                        placeholder="Contoh: Pengolahan Dokumen Pencacahan Susenas September" required>
                </div>

                <div class="form-group full-width">
                    <label for="koordinator_id">Koordinator</label>
                    <select id="koordinator_id" name="koordinator_id" required>
                        <option value="" disabled>Pilih Koordinator</option>
                        @foreach ($koordinator as $k)
                            <option value="{{ $k->id }}" @selected((string) old('koordinator_id', $p->koordinator_id) === (string) $k->id)>
                                {{ $k->name }} ({{ $k->username }})
                            </option>
                        @endforeach
                    </select>
                    @if ($koordinator->isEmpty())
                        <small style="color:#b3261e">Belum ada akun koordinator yang aktif.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label for="volume">Volume</label>
                    <input type="text" id="volume" name="volume" value="{{ old('volume', $volumeAwal) }}"
                        inputmode="decimal" placeholder="Contoh: 25" required>
                </div>

                <div class="form-group">
                    <label for="satuan">Satuan</label>
                    <select id="satuan" name="satuan" required>
                        <option value="" disabled>Pilih Satuan</option>
                        @foreach ($daftarSatuan as $opsi)
                            <option value="{{ $opsi }}" @selected(old('satuan', $p->satuan) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="harga_satuan">Harga Satuan (Rp)</label>
                    <input type="text" id="harga_satuan" name="harga_satuan"
                        value="{{ old('harga_satuan', (int) $p->harga_satuan) }}" inputmode="numeric"
                        placeholder="Contoh: 10.000" required>
                </div>

                <div class="form-group">
                    <label for="previewHonor">Honor per Mitra</label>
                    <input type="text" id="previewHonor" value="Rp 0" readonly tabindex="-1">
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                        value="{{ old('tanggal_mulai', $fmtTanggal($p->tanggal_mulai)) }}" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                        value="{{ old('tanggal_selesai', $fmtTanggal($p->tanggal_selesai)) }}" required>
                </div>
            </div>

            <p class="catatan">
                Bulan kontrak mengikuti tanggal mulai. Jika bulannya diubah, tugas ini dipindahkan ke kontrak
                mitra pada bulan yang baru (dibuat otomatis jika belum ada).
            </p>
        </section>

        {{-- ======================== --}}
        {{-- INFORMASI ANGGARAN       --}}
        {{-- ======================== --}}
        <section class="form-card" style="margin-top:16px">
            <h3 style="margin:0 0 16px;font-size:16px">Informasi Anggaran (opsional)</h3>

            <div class="form-grid">
                <div class="form-group">
                    <label for="kro_ro">KRO</label>
                    <select id="kro_ro" name="kro_ro">
                        <option value="">Pilih KRO</option>
                        @foreach ($daftarKro as $opsi)
                            <option value="{{ $opsi }}" @selected(old('kro_ro', $p->kro_ro) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="komponen">Komponen</label>
                    <select id="komponen" name="komponen">
                        <option value="">Pilih Komponen</option>
                        @foreach ($daftarKomponen as $opsi)
                            <option value="{{ $opsi }}" @selected(old('komponen', $p->komponen) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="previewRo">RO (gabungan KRO + Komponen)</label>
                    <input type="text" id="previewRo" value="" readonly tabindex="-1"
                        placeholder="Terisi otomatis">
                </div>

                <div class="form-group full-width">
                    <label class="cek">
                        <input type="checkbox" id="paket_data_pulsa" name="paket_data_pulsa" value="1"
                            @checked($pulsaAwal)>
                        Termasuk paket data / pulsa
                    </label>
                </div>

                <div class="form-group full-width" id="grupRoPulsa" hidden>
                    <label for="ro_paket_data_pulsa">RO Paket Data / Pulsa</label>
                    <select id="ro_paket_data_pulsa" name="ro_paket_data_pulsa">
                        <option value="">Pilih RO Pulsa</option>
                        @foreach ($daftarRoPulsa as $opsi)
                            <option value="{{ $opsi }}" @selected(old('ro_paket_data_pulsa', $p->ro_paket_data_pulsa) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-navigation">
                <a href="{{ route('admin.penugasan') }}" class="btn-secondary"
                    style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">
                    Batal
                </a>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </section>
    </form>

    @push('scripts')
        <script>
            (function() {
                /* Honor per mitra = volume x harga satuan */
                const volume = document.getElementById('volume');
                const harga = document.getElementById('harga_satuan');
                const preview = document.getElementById('previewHonor');
                const formatRp = v => v.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                function hitung() {
                    const v = parseFloat((volume.value || '0').replace(',', '.')) || 0;
                    const h = parseInt(harga.value.replace(/\D/g, ''), 10) || 0;
                    preview.value = 'Rp ' + formatRp(String(Math.round(v * h)));
                }

                harga.addEventListener('input', () => {
                    harga.value = formatRp(harga.value);
                    hitung();
                });
                volume.addEventListener('input', hitung);
                harga.value = formatRp(harga.value);
                hitung();

                /* Kolom RO paket data muncul jika dicentang */
                const pulsa = document.getElementById('paket_data_pulsa');
                const grupPulsa = document.getElementById('grupRoPulsa');
                const aturPulsa = () => {
                    grupPulsa.hidden = !pulsa.checked;
                };
                pulsa.addEventListener('change', aturPulsa);
                aturPulsa();

                /* RO = KRO + Komponen */
                const kro = document.getElementById('kro_ro');
                const komp = document.getElementById('komponen');
                const previewRo = document.getElementById('previewRo');
                const aturRo = () => {
                    previewRo.value = (kro.value && komp.value) ? kro.value + '.' + komp.value : '';
                };
                kro.addEventListener('change', aturRo);
                komp.addEventListener('change', aturRo);
                aturRo();
            })();
        </script>
    @endpush
</x-admin.main>
