<x-dynamic-component :component="$layout">
    <x-slot:titlex>Tambah Tugas</x-slot:titlex>

    <div class="create-page">
        {{-- HEADER HALAMAN (tanpa subjudul) --}}
        <x-header judul="Tambah Tugas" />

        <form id="penugasanForm" action="{{ route($routes['simpan']) }}" method="POST">
            @csrf

            @if ($errors->any())
                <div role="alert" class="form-alert-error">
                    <b>Tugas belum bisa disimpan:</b>
                    <ul>
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- DETAIL PEKERJAAN --}}
            <section class="form-card">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <rect x="6" y="4" width="12" height="17" rx="2.5" />
                        <path d="M9.5 4V3a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v1" />
                        <path d="M9 10h6M9 14h4" />
                    </svg>
                    Detail Pekerjaan
                </h2>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="uraian_pekerjaan">Uraian Pekerjaan</label>
                        <input type="text" id="uraian_pekerjaan" name="uraian_pekerjaan"
                            value="{{ old('uraian_pekerjaan') }}"
                            placeholder="Contoh: Pengolahan Dokumen Pencacahan Susenas September" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="sebagai">Sebagai</label>
                        <select id="sebagai" name="sebagai" required>
                            <option value="" selected disabled>Pilih Sebagai (Pengolah / Pendata)</option>
                            <option value="Pengolah" @selected(old('sebagai') === 'Pengolah')>Pengolah</option>
                            <option value="Pendata" @selected(old('sebagai') === 'Pendata')>Pendata</option>
                        </select>
                    </div>

                    @if ($bisaPilihKoordinator)
                        <div class="form-group full-width">
                            <label for="koordinator_id">Koordinator</label>
                            <select id="koordinator_id" name="koordinator_id" required>
                                <option value="" selected disabled>Pilih Koordinator</option>
                                @foreach ($koordinator as $k)
                                    <option value="{{ $k->id }}" @selected((string) old('koordinator_id') === (string) $k->id)>
                                        {{ $k->name }} ({{ $k->username }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($koordinator->isEmpty())
                                <small class="field-error">Belum ada akun koordinator yang aktif.</small>
                            @endif
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="satuan">Satuan</label>
                        <select id="satuan" name="satuan" required>
                            <option value="" selected disabled>Pilih Satuan</option>
                            @foreach ($daftarSatuan as $opsi)
                                <option value="{{ $opsi }}" @selected(old('satuan') === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="harga_satuan">Harga Satuan (Rp)</label>
                        <input type="text" id="harga_satuan" name="harga_satuan" value="{{ old('harga_satuan') }}"
                            inputmode="numeric" placeholder="Contoh: 10.000" required>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                            value="{{ old('tanggal_selesai') }}" required>
                        <small id="errTanggalSelesai" class="field-error" hidden></small>
                    </div>
                </div>

                <p class="catatan">
                    Volume dan pemilihan mitra diisi pada halaman <b>Assign Petugas</b> agar masing-masing mitra dapat
                    memiliki volume yang berbeda. Bulan kontrak mitra akan mengikuti tanggal mulai.
                </p>
            </section>

            {{-- INFORMASI ANGGARAN --}}
            <section class="form-card">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="6" width="18" height="12" rx="2.5" />
                        <path d="M3 10h18M7 14h4" />
                    </svg>
                    Informasi Anggaran (opsional)
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="kro_ro">KRO</label>
                        <select id="kro_ro" name="kro_ro">
                            <option value="">Pilih KRO</option>
                            @foreach ($daftarKro as $opsi)
                                <option value="{{ $opsi }}" @selected(old('kro_ro') === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="komponen">Komponen</label>
                        <select id="komponen" name="komponen">
                            <option value="">Pilih Komponen</option>
                            @foreach ($daftarKomponen as $opsi)
                                <option value="{{ $opsi }}" @selected(old('komponen') === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="previewRo">RO (gabungan KRO + Komponen)</label>
                        <input type="text" id="previewRo" value="" readonly tabindex="-1"
                            placeholder="Otomatis terisi dari KRO + Komponen">
                    </div>

                    <div class="form-group full-width">
                        <label class="cek">
                            <input type="checkbox" id="paket_data_pulsa" name="paket_data_pulsa" value="1"
                                @checked(old('paket_data_pulsa'))>
                            Termasuk paket data / pulsa
                        </label>
                    </div>

                    <div class="form-group full-width" id="grupRoPulsa" hidden>
                        <label for="ro_paket_data_pulsa">RO Paket Data / Pulsa</label>
                        <select id="ro_paket_data_pulsa" name="ro_paket_data_pulsa">
                            <option value="">Pilih RO Pulsa</option>
                            @foreach ($daftarRoPulsa as $opsi)
                                <option value="{{ $opsi }}" @selected(old('ro_paket_data_pulsa') === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-navigation">
                    <a href="{{ route($routes['index']) }}" class="outline-btn btn-link">
                        <svg viewBox="0 0 24 24">
                            <path d="M15 6l-6 6 6 6" />
                        </svg>
                        <span>Batal</span>
                    </a>

                    <button type="submit" class="outline-btn">
                        <span>Simpan Tugas</span>
                    </button>

                    <button type="submit" name="lanjut_assign" value="1" class="dark-pill-btn">
                        <span>Simpan &amp; Assign Petugas</span>
                        <svg viewBox="0 0 24 24">
                            <path d="M9 6l6 6-6 6" />
                        </svg>
                    </button>
                </div>
            </section>
        </form>
    </div>

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
</x-dynamic-component>
