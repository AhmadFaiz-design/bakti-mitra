<x-admin.main class="create-page">
    <style>
        .form-group[hidden] {
            display: none;
        }

        .picker {
            border: 1px solid #d5e0da;
            border-radius: 10px;
            overflow: hidden;
        }

        .picker-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid #d5e0da;
        }

        .picker-bar input[type="search"] {
            flex: 1;
            min-width: 180px;
            padding: 8px 10px;
            border: 1px solid #d5e0da;
            border-radius: 8px;
            font: inherit;
        }

        .picker-bar label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            margin: 0;
        }

        .picker-list {
            max-height: 320px;
            overflow: auto;
        }

        .picker-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-bottom: 1px solid #eef3f0;
            cursor: pointer;
            font-size: 14px;
            margin: 0;
        }

        .picker-item[hidden] {
            display: none;
        }

        .picker-item small {
            margin-left: auto;
            color: #5f7068;
        }

        .picker-info {
            padding: 16px;
            text-align: center;
            color: #5f7068;
            font-size: 14px;
        }

        /* Checkbox: kembalikan ke ukuran normal (menimpa style input global) */
        .form-group label.cek input[type="checkbox"],
        .picker input[type="checkbox"] {
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

        .terpilih {
            padding: 10px 12px;
            border-bottom: 1px solid #d5e0da;
            background: #f7faf8;
        }

        .terpilih[hidden] {
            display: none;
        }

        .terpilih-judul {
            font-size: 13px;
            color: #5f7068;
            margin: 0 0 8px;
        }

        .terpilih-daftar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            max-height: 120px;
            overflow: auto;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 6px 4px 10px;
            border: 1px solid #d5e0da;
            border-radius: 999px;
            background: #fff;
            font-size: 13px;
        }

        .chip button {
            border: 0;
            background: #eef3f0;
            color: #5f7068;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }

        .chip button:hover {
            background: #fdecea;
            color: #b3261e;
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
            <h1>Beri Tugas ke Mitra</h1>
            <p>Satu tugas dapat diberikan ke beberapa mitra sekaligus</p>
        </div>

        <div class="page-actions">
            <span class="username">{{ auth()->user()->name }}</span>
            <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    <form id="penugasanForm" action="{{ route('admin.penugasan.simpan') }}" method="POST">
        @csrf

        @if ($errors->any())
            <div role="alert"
                style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px">
                <b>Tugas belum bisa disimpan:</b>
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

            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="uraian_pekerjaan">Uraian Pekerjaan</label>
                    <input type="text" id="uraian_pekerjaan" name="uraian_pekerjaan"
                        value="{{ old('uraian_pekerjaan') }}"
                        placeholder="Contoh: Pengolahan Dokumen Pencacahan Susenas September" required>
                </div>

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
                        <small style="color:#b3261e">Belum ada akun koordinator yang aktif.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label for="volume">Volume</label>
                    <input type="text" id="volume" name="volume" value="{{ old('volume') }}"
                        inputmode="decimal" placeholder="Contoh: 25" required>
                </div>

                <div class="form-group">
                    <label for="satuan">Satuan</label>
                    <select id="satuan" name="satuan" required>
                        <option value="" selected disabled>Pilih Satuan</option>
                        @foreach ($daftarSatuan as $opsi)
                            <option value="{{ $opsi }}" @selected(old('satuan') === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="harga_satuan">Harga Satuan (Rp)</label>
                    <input type="text" id="harga_satuan" name="harga_satuan" value="{{ old('harga_satuan') }}"
                        inputmode="numeric" placeholder="Contoh: 10.000" required>
                </div>

                <div class="form-group">
                    <label for="previewHonor">Honor per Mitra</label>
                    <input type="text" id="previewHonor" value="Rp 0" readonly tabindex="-1">
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
                </div>
            </div>

            <p class="catatan">
                Bulan kontrak mengikuti tanggal mulai. Jika mitra belum punya kontrak pada bulan itu,
                kontrak dibuat otomatis (nomor kontrak dapat diisi kemudian).
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
                            <option value="{{ $opsi }}" @selected(old('kro_ro') === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="komponen">Komponen</label>
                    <select id="komponen" name="komponen">
                        <option value="">Pilih Komponen</option>
                        @foreach ($daftarKomponen as $opsi)
                            <option value="{{ $opsi }}" @selected(old('komponen') === $opsi)>{{ $opsi }}</option>
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
                            @checked(old('paket_data_pulsa'))>
                        Termasuk paket data / pulsa
                    </label>
                </div>

                <div class="form-group full-width" id="grupRoPulsa" hidden>
                    <label for="ro_paket_data_pulsa">RO Paket Data / Pulsa</label>
                    <select id="ro_paket_data_pulsa" name="ro_paket_data_pulsa">
                        <option value="">Pilih RO Pulsa</option>
                        @foreach ($daftarRoPulsa as $opsi)
                            <option value="{{ $opsi }}" @selected(old('ro_paket_data_pulsa') === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        {{-- ======================== --}}
        {{-- PILIH MITRA              --}}
        {{-- ======================== --}}
        <section class="form-card" style="margin-top:16px">
            <h3 style="margin:0 0 16px;font-size:16px">Pilih Mitra</h3>

            @if ($mitra->isEmpty())
                <div class="picker">
                    <p class="picker-info">
                        Belum ada mitra aktif.
                        <a href="{{ route('admin.tambahmitra') }}">Tambah mitra</a> atau
                        <a href="{{ route('admin.mitra.import') }}">import dari Excel</a>.
                    </p>
                </div>
            @else
                <div class="picker">
                    <div class="picker-bar">
                        <input type="search" id="cariMitra" placeholder="Cari nama, Sobat ID, atau wilayah..."
                            autocomplete="off">
                        <label>
                            <input type="checkbox" id="pilihSemuaMitra"> Pilih semua yang tampil
                        </label>
                        <strong id="jumlahDipilih" style="font-size:14px">0 dipilih</strong>
                    </div>

                    <div class="terpilih" id="boxTerpilih" hidden>
                        <p class="terpilih-judul">Mitra terpilih (klik × untuk membatalkan)</p>
                        <div class="terpilih-daftar" id="daftarTerpilih"></div>
                    </div>

                    <div class="picker-list">
                        @foreach ($mitra as $m)
                            <label class="picker-item">
                                <input type="checkbox" name="mitra_id[]" value="{{ $m->id }}"
                                    @checked(in_array($m->id, (array) old('mitra_id', [])))>
                                <span>{{ $m->user->name }}</span>
                                <small>{{ $m->user->username }}@if ($m->wilayah) · {{ $m->wilayah }}@endif</small>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="form-navigation">
                <a href="{{ route('admin.penugasan') }}" class="btn-secondary"
                    style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">
                    Batal
                </a>
                <button type="submit" class="btn-primary">Beri Tugas</button>
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

                /* Pilih mitra */
                const cari = document.getElementById('cariMitra');
                if (!cari) return;

                const items = Array.from(document.querySelectorAll('.picker-item'));
                const jumlah = document.getElementById('jumlahDipilih');
                const semua = document.getElementById('pilihSemuaMitra');

                const boxTerpilih = document.getElementById('boxTerpilih');
                const daftarTerpilih = document.getElementById('daftarTerpilih');

                function hitungDipilih() {
                    const dipilih = items.filter(i => i.querySelector('input').checked);
                    jumlah.textContent = dipilih.length + ' dipilih';

                    /* Preview nama mitra yang dipilih */
                    daftarTerpilih.innerHTML = '';
                    dipilih.forEach(item => {
                        const chip = document.createElement('span');
                        chip.className = 'chip';

                        const nama = document.createElement('span');
                        nama.textContent = item.querySelector('span').textContent.trim();

                        const hapus = document.createElement('button');
                        hapus.type = 'button';
                        hapus.setAttribute('aria-label', 'Batalkan ' + nama.textContent);
                        hapus.textContent = '×';
                        hapus.addEventListener('click', () => {
                            item.querySelector('input').checked = false;
                            hitungDipilih();
                        });

                        chip.append(nama, hapus);
                        daftarTerpilih.appendChild(chip);
                    });
                    boxTerpilih.hidden = dipilih.length === 0;
                }

                cari.addEventListener('input', () => {
                    const kata = cari.value.trim().toLowerCase();
                    items.forEach(i => {
                        i.hidden = kata !== '' && !i.textContent.toLowerCase().includes(kata);
                    });
                });

                semua.addEventListener('change', () => {
                    items.forEach(i => {
                        if (!i.hidden) i.querySelector('input').checked = semua.checked;
                    });
                    hitungDipilih();
                });

                items.forEach(i => i.querySelector('input').addEventListener('change', hitungDipilih));
                hitungDipilih();
            })();
        </script>
    @endpush
</x-admin.main>
