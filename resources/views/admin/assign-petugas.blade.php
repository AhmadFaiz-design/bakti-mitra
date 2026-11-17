@php
    $hargaSatuan = (int) $penugasan->harga_satuan;
    $mulai = $penugasan->tanggal_mulai ? \Carbon\Carbon::parse($penugasan->tanggal_mulai)->format('d/m/Y') : '-';
    $selesai = $penugasan->tanggal_selesai ? \Carbon\Carbon::parse($penugasan->tanggal_selesai)->format('d/m/Y') : '-';
    $totalPetugas = $penugasan->pekerjaan->count();
    $totalVolume = (float) $penugasan->pekerjaan->sum('volume');
    $totalHonor = (int) $penugasan->pekerjaan->sum('jumlah');
    $totalVolText = rtrim(rtrim(number_format($totalVolume, 2, ',', '.'), '0'), ',') ?: '0';
@endphp

<x-admin.main class="create-page">
    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px 20px;
        }

        .summary-item span {
            display: block;
            font-size: 12px;
            color: #5f7068;
            margin-bottom: 4px;
        }

        .summary-item strong {
            font-size: 14px;
            color: #1d2d25;
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
            background: #fbfdfb;
        }

        .picker-bar input[type="search"] {
            flex: 1;
            min-width: 200px;
            padding: 8px 10px;
            border: 1px solid #d5e0da;
            border-radius: 8px;
            font: inherit;
        }

        .mass-vol-box {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .mass-vol-box input {
            width: 105px;
            padding: 7px 10px;
            border: 1px solid #d5e0da;
            border-radius: 8px;
            font: inherit;
            font-size: 13px;
        }

        .mass-vol-btn {
            padding: 7px 10px;
            border-radius: 8px;
            border: 1px solid #d5e0da;
            background: #fff;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }

        .mass-vol-btn:hover {
            background: #eef5f0;
        }

        .picker-bar label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            margin: 0;
            cursor: pointer;
        }

        .picker-list {
            max-height: 360px;
            overflow: auto;
        }

        .assign-row {
            display: grid;
            grid-template-columns: 24px 1fr 165px 140px;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-bottom: 1px solid #eef3f0;
            font-size: 14px;
        }

        .assign-row[hidden] {
            display: none;
        }

        .assign-row.selected {
            background: #f5fbf7;
        }

        .assign-row.row-invalid {
            background: #fff4f3;
        }

        .assign-mitra-label {
            display: flex;
            flex-direction: column;
            gap: 2px;
            cursor: pointer;
            margin: 0;
        }

        .assign-mitra-label small {
            color: #5f7068;
            font-size: 12px;
        }

        .assign-vol-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .assign-vol-input {
            width: 95px;
            padding: 7px 9px;
            border: 1px solid #d5e0da;
            border-radius: 8px;
            font: inherit;
            font-size: 13px;
            text-align: right;
            background: #fff;
        }

        .assign-row.row-invalid .assign-vol-input {
            border-color: #b3261e;
        }

        .assign-satuan {
            font-size: 12px;
            color: #5f7068;
            white-space: nowrap;
        }

        .assign-honor {
            text-align: right;
            font-weight: 600;
            font-size: 13px;
            color: #1d2d25;
            white-space: nowrap;
        }

        .picker input[type="checkbox"],
        .inline-bayar input[type="checkbox"] {
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

        .terpilih {
            padding: 10px 12px;
            border-bottom: 1px solid #d5e0da;
            background: #f7faf8;
        }

        .terpilih[hidden] {
            display: none;
        }

        .terpilih-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #5f7068;
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

        .inline-vol-form {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .inline-vol-form input {
            width: 82px;
            padding: 6px 8px;
            border: 1px solid #d5e0da;
            border-radius: 7px;
            font: inherit;
            font-size: 13px;
            text-align: right;
        }

        .mini-btn {
            padding: 6px 10px;
            border-radius: 7px;
            border: 1px solid #d5e0da;
            background: #fff;
            font: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .mini-btn.save {
            background: #f4faf6;
            border-color: #b7d7c2;
            color: #166534;
        }

        .mini-btn.del {
            background: #fff5f5;
            border-color: #f3c4c0;
            color: #b3261e;
        }

        .mini-btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .inline-bayar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
        }

        @media (max-width: 720px) {
            .assign-row {
                grid-template-columns: 24px 1fr;
            }
            .assign-vol-wrap,
            .assign-honor {
                grid-column: 2;
                justify-self: start;
            }
        }
    </style>

    {{-- HEADER HALAMAN --}}
    <header class="page-header">
        <div class="page-title">
            <h1>Assign Petugas</h1>
            <p>{{ $penugasan->uraian_pekerjaan }}</p>
        </div>

        <div class="page-actions" style="gap:12px">
            <a href="{{ route('admin.penugasan') }}" class="btn-secondary"
                style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                &larr; Kembali ke Penugasan
            </a>
            <span class="username">{{ auth()->user()->name }}</span>
            <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    @if ($errors->any())
        <div role="alert"
            style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px">
            <b>Penugasan petugas belum bisa disimpan:</b>
            <ul style="margin:6px 0 0 18px;padding:0">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- RINGKASAN TUGAS --}}
    <section class="form-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px">
            <h3 style="margin:0;font-size:16px">Detail Tugas</h3>
            @if ($penugasan->ro)
                <span class="status-badge active">RO: {{ $penugasan->ro }}</span>
            @endif
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <span>Uraian Pekerjaan</span>
                <strong>{{ $penugasan->uraian_pekerjaan }}</strong>
            </div>
            <div class="summary-item">
                <span>Koordinator</span>
                <strong>{{ $penugasan->koordinator?->name ?? '-' }}</strong>
            </div>
            <div class="summary-item">
                <span>Periode</span>
                <strong>{{ $mulai }} s.d. {{ $selesai }}</strong>
            </div>
            <div class="summary-item">
                <span>Tarif / Satuan</span>
                <strong>Rp {{ number_format($hargaSatuan, 0, ',', '.') }} / {{ $penugasan->satuan }}</strong>
            </div>
            <div class="summary-item">
                <span>Petugas Ditugaskan</span>
                <strong>{{ $totalPetugas }} Mitra</strong>
            </div>
            <div class="summary-item">
                <span>Total Volume &amp; Honor</span>
                <strong>{{ $totalVolText }} {{ $penugasan->satuan }} &middot; Rp {{ number_format($totalHonor, 0, ',', '.') }}</strong>
            </div>
        </div>
    </section>

    {{-- FORM ASSIGN PETUGAS BARU --}}
    <section class="form-card" style="margin-top:16px">
        <h3 style="margin:0 0 6px;font-size:16px">Tambah / Assign Petugas Baru</h3>
        <p style="margin:0 0 14px;font-size:13px;color:#5f7068">
            Centang mitra yang ditugaskan lalu masukkan volume pekerjaan masing-masing.
        </p>

        @if ($mitraTersedia->isEmpty())
            <div class="picker">
                <p style="padding:18px;text-align:center;color:#5f7068;margin:0;font-size:14px">
                    @if ($totalPetugas > 0)
                        Semua mitra aktif sudah ditugaskan ke tugas ini.
                    @else
                        Belum ada mitra aktif.
                        <a href="{{ route('admin.tambahmitra') }}">Tambah mitra</a> atau
                        <a href="{{ route('admin.mitra.import') }}">import dari Excel</a>.
                    @endif
                </p>
            </div>
        @else
            <form id="assignForm" action="{{ route('admin.penugasan.assign.simpan', $penugasan) }}" method="POST">
                @csrf

                <div class="picker">
                    <div class="picker-bar">
                        <input type="search" id="cariMitra" placeholder="Cari nama, Sobat ID, atau wilayah..."
                            autocomplete="off">

                        <div class="mass-vol-box" title="Opsional: isi volume sama ke semua baris yang dicentang">
                            <input type="text" id="volumeMassal" inputmode="decimal" placeholder="Vol. default">
                            <button type="button" class="mass-vol-btn" id="terapkanMassal">Terapkan ke dipilih</button>
                        </div>

                        <label>
                            <input type="checkbox" id="pilihSemuaMitra"> Pilih semua tampil
                        </label>
                        <strong id="jumlahDipilih" style="font-size:14px">0 dipilih</strong>
                    </div>

                    <div class="terpilih" id="boxTerpilih" hidden>
                        <div class="terpilih-head">
                            <span>Mitra terpilih (klik &times; untuk membatalkan)</span>
                            <strong id="totalHonorBaru" style="color:#166534">Total tambahan honor: Rp 0</strong>
                        </div>
                        <div class="terpilih-daftar" id="daftarTerpilih"></div>
                    </div>

                    <div class="picker-list" id="assignList">
                        @foreach ($mitraTersedia as $m)
                            @php
                                $checkedOld = in_array($m->id, (array) old('mitra_id', []));
                                $volOld = old('volumes.' . $m->id, '');
                            @endphp
                            <div class="assign-row {{ $checkedOld ? 'selected' : '' }}"
                                data-search="{{ strtolower($m->user->name . ' ' . $m->user->username . ' ' . ($m->wilayah ?? '')) }}"
                                data-nama="{{ $m->user->name }}">
                                <input type="checkbox" id="mitra_{{ $m->id }}" class="assign-check"
                                    name="mitra_id[]" value="{{ $m->id }}" @checked($checkedOld)>

                                <label for="mitra_{{ $m->id }}" class="assign-mitra-label">
                                    <span>{{ $m->user->name }}</span>
                                    <small>{{ $m->user->username }}@if ($m->wilayah) &middot; {{ $m->wilayah }}@endif</small>
                                </label>

                                <div class="assign-vol-wrap">
                                    <input type="text" name="volumes[{{ $m->id }}]" class="assign-vol-input"
                                        inputmode="decimal" placeholder="Volume" value="{{ $volOld }}"
                                        aria-label="Volume untuk {{ $m->user->name }}">
                                    <span class="assign-satuan">{{ $penugasan->satuan }}</span>
                                </div>

                                <div class="assign-honor" data-row-honor>Rp 0</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="assignErrorClient" role="alert" hidden
                    style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:8px;padding:10px 14px;margin-top:12px;font-size:13px">
                </div>

                <div class="form-navigation" style="margin-top:16px">
                    <a href="{{ route('admin.penugasan') }}" class="btn-secondary"
                        style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">
                        Kembali
                    </a>
                    <button type="submit" class="btn-primary">Assign Petugas</button>
                </div>
            </form>
        @endif
    </section>

    {{-- DAFTAR PETUGAS YANG SUDAH DITUGASKAN --}}
    <section class="form-card" style="margin-top:16px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px">
            <h3 style="margin:0;font-size:16px">Petugas yang Sudah Ditugaskan ({{ $totalPetugas }})</h3>
        </div>

        <div class="table-scroll">
            <table class="mitra-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Mitra</th>
                        <th>Volume ({{ $penugasan->satuan }})</th>
                        <th>Honor</th>
                        <th>Pembayaran</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($penugasan->pekerjaan as $i => $p)
                        @php
                            $mitra = $p->kontrak?->mitra;
                            $user = $mitra?->user;
                            $nama = $user?->name ?? '-';
                            $dibayar = (bool) $p->honor_dibayar;
                            $diverifikasi = (bool) ($p->kontrak?->cek_kontrak || $p->kontrak?->cek_spj);
                            $terkunci = $dibayar || $diverifikasi;
                            $volVal = rtrim(rtrim(number_format((float) $p->volume, 2, '.', ''), '0'), '.');
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <span class="profile-name">{{ $nama }}</span>
                                <span class="cell-sub">
                                    {{ $user?->username ?? '-' }}@if ($mitra?->wilayah) &middot; {{ $mitra->wilayah }}@endif
                                </span>
                                @if ($diverifikasi)
                                    <span class="cell-sub" style="color:#b3261e">Kontrak sudah diverifikasi</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST"
                                    action="{{ route('admin.penugasan.petugas.update', [$penugasan, $p]) }}"
                                    class="inline-vol-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="volume" value="{{ $volVal }}"
                                        inputmode="decimal" class="existing-vol-input"
                                        data-target="honor_{{ $p->id }}"
                                        @disabled($terkunci)
                                        aria-label="Volume {{ $nama }}">
                                    <button type="submit" class="mini-btn save" @disabled($terkunci)>
                                        Simpan
                                    </button>
                                </form>
                            </td>
                            <td class="honor-cell" id="honor_{{ $p->id }}">
                                Rp {{ number_format($p->jumlah ?? 0, 0, ',', '.') }}
                            </td>
                            <td>
                                <label class="inline-bayar">
                                    <input type="checkbox" class="bayar-petugas-check"
                                        data-url="{{ route('admin.penugasan.pekerjaan.bayar', $p) }}"
                                        @checked($dibayar)>
                                    @if ($dibayar)
                                        <span class="status-badge active">Sudah Dibayar</span>
                                    @else
                                        <span class="status-badge pending">Belum Dibayar</span>
                                    @endif
                                </label>
                                @if ($dibayar && $p->dibayar_pada)
                                    <span class="cell-sub">{{ $p->dibayar_pada->format('d/m/Y H:i') }}</span>
                                @endif
                            </td>
                            <td style="text-align:right">
                                <form method="POST"
                                    action="{{ route('admin.penugasan.petugas.hapus', [$penugasan, $p]) }}"
                                    class="hapus-petugas-form" data-nama="{{ $nama }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mini-btn del" @disabled($terkunci)
                                        title="{{ $terkunci ? 'Tidak bisa dihapus karena honor sudah dibayar atau kontrak sudah diverifikasi' : 'Keluarkan mitra dari tugas ini' }}">
                                        Keluarkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:#5f7068">
                                Belum ada petugas yang ditugaskan. Silakan pilih mitra dan isi volumenya pada form di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @push('scripts')
        <script>
            (function() {
                const HARGA_SATUAN = @json($hargaSatuan);
                const SATUAN = @json($penugasan->satuan);
                const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

                const formatRp = n => 'Rp ' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                const parseNum = v => parseFloat(String(v || '0').replace(',', '.')) || 0;

                /* ===== Bagian Picker Assign Baru ===== */
                const assignForm = document.getElementById('assignForm');
                if (assignForm) {
                    const cari = document.getElementById('cariMitra');
                    const semua = document.getElementById('pilihSemuaMitra');
                    const rows = Array.from(document.querySelectorAll('#assignList .assign-row'));
                    const jumlahDipilih = document.getElementById('jumlahDipilih');
                    const boxTerpilih = document.getElementById('boxTerpilih');
                    const daftarTerpilih = document.getElementById('daftarTerpilih');
                    const totalHonorBaru = document.getElementById('totalHonorBaru');
                    const volMassal = document.getElementById('volumeMassal');
                    const btnMassal = document.getElementById('terapkanMassal');
                    const errClient = document.getElementById('assignErrorClient');

                    function updateRowHonor(row) {
                        const cb = row.querySelector('.assign-check');
                        const volInput = row.querySelector('.assign-vol-input');
                        const honorEl = row.querySelector('[data-row-honor]');
                        const vol = parseNum(volInput.value);
                        const honor = vol > 0 ? Math.round(vol * HARGA_SATUAN) : 0;

                        honorEl.textContent = formatRp(honor);
                        row.classList.toggle('selected', cb.checked);
                        if (cb.checked && vol > 0) {
                            row.classList.remove('row-invalid');
                        }
                    }

                    function segarkanTerpilih() {
                        const dipilih = rows.filter(r => r.querySelector('.assign-check').checked);
                        jumlahDipilih.textContent = dipilih.length + ' dipilih';

                        daftarTerpilih.innerHTML = '';
                        let totalHonor = 0;

                        dipilih.forEach(row => {
                            const cb = row.querySelector('.assign-check');
                            const volInput = row.querySelector('.assign-vol-input');
                            const nama = row.dataset.nama;
                            const vol = parseNum(volInput.value);
                            const honor = vol > 0 ? Math.round(vol * HARGA_SATUAN) : 0;
                            totalHonor += honor;

                            const chip = document.createElement('span');
                            chip.className = 'chip';

                            const teks = document.createElement('span');
                            teks.textContent = nama + (vol > 0 ? ' (' + vol + ' ' + SATUAN + ' · ' + formatRp(honor) + ')' : ' (vol. belum diisi)');

                            const hapus = document.createElement('button');
                            hapus.type = 'button';
                            hapus.setAttribute('aria-label', 'Batalkan ' + nama);
                            hapus.innerHTML = '&times;';
                            hapus.addEventListener('click', () => {
                                cb.checked = false;
                                row.classList.remove('row-invalid');
                                updateRowHonor(row);
                                segarkanTerpilih();
                            });

                            chip.append(teks, hapus);
                            daftarTerpilih.appendChild(chip);
                        });

                        totalHonorBaru.textContent = 'Total tambahan honor: ' + formatRp(totalHonor);
                        boxTerpilih.hidden = dipilih.length === 0;
                    }

                    rows.forEach(row => {
                        const cb = row.querySelector('.assign-check');
                        const volInput = row.querySelector('.assign-vol-input');

                        cb.addEventListener('change', () => {
                            if (cb.checked && volInput.value.trim() === '') {
                                volInput.focus();
                            }
                            if (!cb.checked) {
                                row.classList.remove('row-invalid');
                            }
                            updateRowHonor(row);
                            segarkanTerpilih();
                        });

                        volInput.addEventListener('input', () => {
                            const vol = parseNum(volInput.value);
                            if (vol > 0 && !cb.checked) {
                                cb.checked = true;
                            }
                            updateRowHonor(row);
                            segarkanTerpilih();
                        });

                        updateRowHonor(row);
                    });

                    cari.addEventListener('input', () => {
                        const kata = cari.value.trim().toLowerCase();
                        rows.forEach(r => {
                            r.hidden = kata !== '' && !r.dataset.search.includes(kata);
                        });
                    });

                    semua.addEventListener('change', () => {
                        rows.forEach(r => {
                            if (!r.hidden) {
                                r.querySelector('.assign-check').checked = semua.checked;
                                if (!semua.checked) r.classList.remove('row-invalid');
                                updateRowHonor(r);
                            }
                        });
                        segarkanTerpilih();
                    });

                    btnMassal.addEventListener('click', () => {
                        const val = volMassal.value.trim();
                        if (parseNum(val) <= 0) {
                            volMassal.focus();
                            return;
                        }
                        rows.forEach(r => {
                            if (r.querySelector('.assign-check').checked) {
                                r.querySelector('.assign-vol-input').value = val;
                                updateRowHonor(r);
                            }
                        });
                        segarkanTerpilih();
                    });

                    assignForm.addEventListener('submit', e => {
                        errClient.hidden = true;
                        const dipilih = rows.filter(r => r.querySelector('.assign-check').checked);

                        if (dipilih.length === 0) {
                            e.preventDefault();
                            errClient.textContent = 'Pilih minimal satu mitra terlebih dahulu.';
                            errClient.hidden = false;
                            return;
                        }

                        const kurang = [];
                        rows.forEach(r => r.classList.remove('row-invalid'));

                        dipilih.forEach(r => {
                            const vol = parseNum(r.querySelector('.assign-vol-input').value);
                            if (vol <= 0) {
                                r.classList.add('row-invalid');
                                kurang.push(r.dataset.nama);
                            }
                        });

                        if (kurang.length > 0) {
                            e.preventDefault();
                            errClient.textContent = 'Masukkan volume lebih dari 0 untuk: ' + kurang.join(', ');
                            errClient.hidden = false;
                            rows.find(r => r.classList.contains('row-invalid'))?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            return;
                        }

                        // Nonaktifkan input volume pada baris tidak terpilih agar request tetap ringan
                        rows.forEach(r => {
                            if (!r.querySelector('.assign-check').checked) {
                                r.querySelector('.assign-vol-input').disabled = true;
                            }
                        });
                    });

                    segarkanTerpilih();
                }

                /* ===== Preview honor live saat edit volume petugas terdaftar ===== */
                document.querySelectorAll('.existing-vol-input').forEach(input => {
                    input.addEventListener('input', () => {
                        const target = document.getElementById(input.dataset.target);
                        if (!target) return;
                        const vol = parseNum(input.value);
                        target.textContent = formatRp(vol > 0 ? vol * HARGA_SATUAN : 0);
                    });
                });

                /* ===== Toggle pembayaran per mitra ===== */
                document.querySelectorAll('.bayar-petugas-check').forEach(cb => {
                    cb.addEventListener('change', async () => {
                        const dibayar = cb.checked;
                        cb.disabled = true;

                        try {
                            const res = await fetch(cb.dataset.url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    dibayar
                                })
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            window.location.reload();
                        } catch (e) {
                            cb.checked = !dibayar;
                            cb.disabled = false;
                            if (typeof popup === 'function') {
                                popup({
                                    jenis: 'gagal',
                                    judul: 'Tidak berhasil',
                                    pesan: 'Status pembayaran gagal diperbarui.'
                                });
                            } else {
                                alert('Status pembayaran gagal diperbarui.');
                            }
                        }
                    });
                });

                /* ===== Konfirmasi keluarkan mitra ===== */
                document.querySelectorAll('.hapus-petugas-form').forEach(form => {
                    form.addEventListener('submit', async e => {
                        e.preventDefault();
                        const teks = 'Keluarkan ' + form.dataset.nama + ' dari tugas ini?';
                        const lanjut = typeof popup === 'function' ?
                            await popup({
                                jenis: 'hapus',
                                judul: 'Keluarkan petugas?',
                                pesan: teks,
                                tombolOk: 'Ya, keluarkan',
                                tombolBatal: 'Batal'
                            }) :
                            confirm(teks);

                        if (lanjut) form.submit();
                    });
                });
            })();
        </script>
    @endpush
</x-admin.main>
