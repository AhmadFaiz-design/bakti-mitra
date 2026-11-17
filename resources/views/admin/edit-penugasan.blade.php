<x-admin.main class="create-page">
    <style>
        .form-group[hidden] {
            display: none;
        }

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

        .catatan {
            font-size: 13px;
            color: #5f7068;
            margin: 12px 0 0;
        }
    </style>

    <header class="page-header">
        <div class="page-title">
            <h1>Edit Tugas</h1>
            <p>Perubahan detail tugas akan disinkronkan ke semua mitra yang sudah ditugaskan</p>
        </div>

        <div class="page-actions">
            <span class="username">{{ auth()->user()->name }}</span>
            <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    <form action="{{ route('admin.penugasan.update', $penugasan) }}" method="POST">
        @csrf
        @method('PUT')

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

        <section class="form-card">
            <h3 style="margin:0 0 16px;font-size:16px">Detail Pekerjaan</h3>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="uraian_pekerjaan">Uraian Pekerjaan</label>
                    <input type="text" id="uraian_pekerjaan" name="uraian_pekerjaan"
                        value="{{ old('uraian_pekerjaan', $penugasan->uraian_pekerjaan) }}" required>
                </div>

                <div class="form-group full-width">
                    <label for="koordinator_id">Koordinator</label>
                    <select id="koordinator_id" name="koordinator_id" required>
                        <option value="" disabled>Pilih Koordinator</option>
                        @foreach ($koordinator as $k)
                            <option value="{{ $k->id }}" @selected((string) old('koordinator_id', $penugasan->koordinator_id) === (string) $k->id)>
                                {{ $k->name }} ({{ $k->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="satuan">Satuan</label>
                    <select id="satuan" name="satuan" required>
                        @foreach ($daftarSatuan as $opsi)
                            <option value="{{ $opsi }}" @selected(old('satuan', $penugasan->satuan) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="harga_satuan">Harga Satuan (Rp)</label>
                    <input type="text" id="harga_satuan" name="harga_satuan"
                        value="{{ old('harga_satuan', $penugasan->harga_satuan) }}" inputmode="numeric" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                        value="{{ old('tanggal_mulai', $penugasan->tanggal_mulai?->format('Y-m-d')) }}" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                        value="{{ old('tanggal_selesai', $penugasan->tanggal_selesai?->format('Y-m-d')) }}" required>
                </div>
            </div>

            <p class="catatan">
                Untuk menambah mitra atau mengubah volume masing-masing mitra, gunakan menu
                <a href="{{ route('admin.penugasan.assign', $penugasan) }}">Assign Petugas</a>.
            </p>
        </section>

        <section class="form-card" style="margin-top:16px">
            <h3 style="margin:0 0 16px;font-size:16px">Informasi Anggaran (opsional)</h3>

            <div class="form-grid">
                <div class="form-group">
                    <label for="kro_ro">KRO</label>
                    <select id="kro_ro" name="kro_ro">
                        <option value="">Pilih KRO</option>
                        @foreach ($daftarKro as $opsi)
                            <option value="{{ $opsi }}" @selected(old('kro_ro', $penugasan->kro_ro) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="komponen">Komponen</label>
                    <select id="komponen" name="komponen">
                        <option value="">Pilih Komponen</option>
                        @foreach ($daftarKomponen as $opsi)
                            <option value="{{ $opsi }}" @selected(old('komponen', $penugasan->komponen) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="previewRo">RO (gabungan KRO + Komponen)</label>
                    <input type="text" id="previewRo" value="" readonly tabindex="-1">
                </div>

                <div class="form-group full-width">
                    <label class="cek">
                        <input type="checkbox" id="paket_data_pulsa" name="paket_data_pulsa" value="1"
                            @checked(old('paket_data_pulsa', $penugasan->paket_data_pulsa))>
                        Termasuk paket data / pulsa
                    </label>
                </div>

                <div class="form-group full-width" id="grupRoPulsa" hidden>
                    <label for="ro_paket_data_pulsa">RO Paket Data / Pulsa</label>
                    <select id="ro_paket_data_pulsa" name="ro_paket_data_pulsa">
                        <option value="">Pilih RO Pulsa</option>
                        @foreach ($daftarRoPulsa as $opsi)
                            <option value="{{ $opsi }}" @selected(old('ro_paket_data_pulsa', $penugasan->ro_paket_data_pulsa) === $opsi)>{{ $opsi }}</option>
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
                const harga = document.getElementById('harga_satuan');
                const formatRp = v => v.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                harga.addEventListener('input', () => {
                    harga.value = formatRp(harga.value);
                });
                harga.value = formatRp(harga.value);

                const pulsa = document.getElementById('paket_data_pulsa');
                const grupPulsa = document.getElementById('grupRoPulsa');
                const aturPulsa = () => {
                    grupPulsa.hidden = !pulsa.checked;
                };
                pulsa.addEventListener('change', aturPulsa);
                aturPulsa();

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
