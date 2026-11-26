<x-dynamic-component :component="$layout">
    <x-slot:titlex>Edit Tugas</x-slot:titlex>

    <div class="create-page">
        {{-- HEADER HALAMAN (tanpa subjudul) --}}
        <x-header judul="Edit Tugas" />

        <form action="{{ route($routes['update'], $penugasan) }}" method="POST">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div role="alert" class="form-alert-error">
                    <b>Perubahan belum bisa disimpan:</b>
                    <ul>
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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
                            value="{{ old('uraian_pekerjaan', $penugasan->uraian_pekerjaan) }}"
                            placeholder="Masukkan uraian pekerjaan" required>
                    </div>

                    @if ($bisaPilihKoordinator)
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
                    @endif

                    <div class="form-group">
                        <label for="satuan">Satuan</label>
                        <select id="satuan" name="satuan" required>
                            @foreach ($daftarSatuan as $opsi)
                                <option value="{{ $opsi }}" @selected(old('satuan', $penugasan->satuan) === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="harga_satuan">Harga Satuan (Rp)</label>
                        <input type="text" id="harga_satuan" name="harga_satuan"
                            value="{{ old('harga_satuan', $penugasan->harga_satuan) }}" placeholder="0"
                            inputmode="numeric" required>
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
                    <a href="{{ route($routes['assign'], $penugasan) }}">Assign Petugas</a>.
                </p>
            </section>

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
                                <option value="{{ $opsi }}" @selected(old('kro_ro', $penugasan->kro_ro) === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="komponen">Komponen</label>
                        <select id="komponen" name="komponen">
                            <option value="">Pilih Komponen</option>
                            @foreach ($daftarKomponen as $opsi)
                                <option value="{{ $opsi }}" @selected(old('komponen', $penugasan->komponen) === $opsi)>{{ $opsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="previewRo">RO (gabungan KRO + Komponen)</label>
                        <input type="text" id="previewRo" value="" placeholder="Otomatis terisi dari KRO + Komponen"
                            readonly tabindex="-1">
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
                                <option value="{{ $opsi }}" @selected(old('ro_paket_data_pulsa', $penugasan->ro_paket_data_pulsa) === $opsi)>{{ $opsi }}
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

                    <button type="submit" class="dark-pill-btn">
                        <span>Simpan Perubahan</span>
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" />
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
