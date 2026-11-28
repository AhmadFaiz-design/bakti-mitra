@php
    $hargaSatuan = (int) $penugasan->harga_satuan;
    $honorBulanMitra = $honorBulanMitra ?? [];
    $batasBulanMitra = $batasBulanMitra ?? [];
    $batasExistingMitra = $batasExistingMitra ?? [];

    $mulai = $penugasan->tanggal_mulai ? \Carbon\Carbon::parse($penugasan->tanggal_mulai)->format('d/m/Y') : '-';
    $selesai = $penugasan->tanggal_selesai ? \Carbon\Carbon::parse($penugasan->tanggal_selesai)->format('d/m/Y') : '-';
    $periodeKontrak = $penugasan->tanggal_mulai
        ? \Carbon\Carbon::parse($penugasan->tanggal_mulai)->locale('id')->translatedFormat('F Y')
        : '-';

    $totalPetugas = $penugasan->pekerjaan->count();
    $totalVolume = (float) $penugasan->pekerjaan->sum('volume');
    $totalHonor = (int) $penugasan->pekerjaan->sum('jumlah');
    $totalVolText = rtrim(rtrim(number_format($totalVolume, 2, ',', '.'), '0'), ',') ?: '0';
@endphp

<x-dynamic-component :component="$layout">
    <x-slot:titlex>Assign Petugas</x-slot:titlex>

    <div class="create-page assign-page">
        {{-- HEADER HALAMAN (tanpa subjudul) --}}
        <x-header judul="Assign Petugas" />

        @if ($errors->any())
            <div role="alert" class="form-alert-error">
                <b>Penugasan petugas belum bisa disimpan:</b>
                <ul>
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- RINGKASAN TUGAS --}}
        <section class="form-card">
            <div class="form-card-head">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <rect x="6" y="4" width="12" height="17" rx="2.5" />
                        <path d="M9.5 4V3a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v1" />
                        <path d="M9 10h6M9 14h4" />
                    </svg>
                    Detail Tugas
                </h2>
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
                    <span>Sebagai</span>
                    <strong>{{ $penugasan->sebagai ?: '-' }}</strong>
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
                    <span>Kontrak &amp; Peran Tugas</span>
                    <strong>{{ $periodeKontrak }} &middot; {{ $penugasan->sebagai ?: 'Umum' }}</strong>
                </div>
                <div class="summary-item">
                    <span>Petugas &amp; Total Honor Tugas</span>
                    <strong>{{ $totalPetugas }} Mitra ({{ $totalVolText }} {{ $penugasan->satuan }}) &middot; Rp
                        {{ number_format($totalHonor, 0, ',', '.') }}</strong>
                </div>
            </div>
        </section>

        {{-- FORM ASSIGN PETUGAS BARU --}}
        <section class="form-card">
            <h2 class="form-card-heading" style="margin-bottom:6px">
                <svg viewBox="0 0 24 24">
                    <circle cx="10" cy="8" r="3.5" />
                    <path d="M3 20c.8-3.5 3.4-5.2 7-5.2 1.6 0 3 .4 4.1 1" />
                    <path d="M17.5 13v6M14.5 16h6" />
                </svg>
                Tambah / Assign Petugas Baru
            </h2>
            <p class="form-card-sub">
                Centang mitra yang ditugaskan lalu masukkan volume pekerjaan masing-masing. Sisa kuota dihitung dari
                total honor mitra di bulan {{ $periodeKontrak }}.
            </p>

            @if ($mitraTersedia->isEmpty())
                <div class="picker">
                    <p class="picker-empty" style="margin:0">
                        @if ($totalPetugas > 0)
                            Semua mitra aktif sudah ditugaskan ke tugas ini.
                        @elseif ($role === 'admin')
                            Belum ada mitra aktif.
                            <a href="{{ route('admin.tambahmitra') }}">Tambah mitra</a> atau
                            <a href="{{ route('admin.mitra.import') }}">import dari Excel</a>.
                        @else
                            Belum ada mitra aktif yang tersedia untuk ditugaskan.
                        @endif
                    </p>
                </div>
                <div class="form-navigation">
                    <a href="{{ route($routes['index']) }}" class="outline-btn btn-link">
                        <svg viewBox="0 0 24 24">
                            <path d="M15 6l-6 6 6 6" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            @else
                <form id="assignForm" action="{{ route($routes['assignSimpan'], $penugasan) }}" method="POST">
                    @csrf

                    <div class="picker">
                        <div class="picker-bar">
                            <input type="search" id="cariMitra" placeholder="Cari nama, Sobat ID, atau wilayah..."
                                autocomplete="off">

                            <div class="mass-vol-box" title="Opsional: isi volume sama ke semua baris yang dicentang">
                                <input type="text" id="volumeMassal" inputmode="decimal" placeholder="Vol. default">
                                <button type="button" class="mass-vol-btn" id="terapkanMassal">Terapkan ke
                                    dipilih</button>
                            </div>

                            <label>
                                <input type="checkbox" id="pilihSemuaMitra"> Pilih semua tampil
                            </label>
                            <span id="jumlahDipilih" class="picker-count">0 dipilih</span>
                        </div>

                        <div class="terpilih" id="boxTerpilih" hidden>
                            <div class="terpilih-head">
                                <span>Mitra terpilih (klik &times; untuk membatalkan)</span>
                                <strong id="totalHonorBaru" class="terpilih-total">Total tambahan honor: Rp 0</strong>
                            </div>
                            <div class="terpilih-daftar" id="daftarTerpilih"></div>
                        </div>

                        <div class="picker-list" id="assignList">
                            @foreach ($mitraTersedia as $m)
                                @php
                                    $checkedOld = in_array($m->id, (array) old('mitra_id', []));
                                    $volOld = old('volumes.' . $m->id, '');
                                    $honorSaatIni = (int) ($honorBulanMitra[$m->id] ?? 0);
                                    $batasMitra =
                                        (int) ($batasBulanMitra[$m->id] ??
                                            \App\Support\BatasHonor::untukPeran($m->wilayah, $penugasan->sebagai));
                                    $sisaKuota = max(0, $batasMitra - $honorSaatIni);
                                @endphp
                                <div class="assign-row {{ $checkedOld ? 'selected' : '' }}"
                                    data-search="{{ strtolower($m->user->name . ' ' . $m->user->username . ' ' . ($m->wilayah ?? '')) }}"
                                    data-nama="{{ $m->user->name }}" data-honor-bulan="{{ $honorSaatIni }}"
                                    data-batas="{{ $batasMitra }}">
                                    <input type="checkbox" id="mitra_{{ $m->id }}" class="assign-check"
                                        name="mitra_id[]" value="{{ $m->id }}" @checked($checkedOld)>

                                    <label for="mitra_{{ $m->id }}" class="assign-mitra-label">
                                        <span>{{ $m->user->name }}</span>
                                        <small>{{ $m->user->username }}@if ($m->wilayah)
                                                &middot; {{ $m->wilayah }}
                                            @endif
                                        </small>
                                        @if ($batasMitra > 0 && $honorSaatIni >= $batasMitra)
                                            <span class="kuota-badge penuh">
                                                Kuota Penuh (Rp {{ number_format($honorSaatIni, 0, ',', '.') }} / Maks
                                                Rp {{ number_format($batasMitra, 0, ',', '.') }})
                                            </span>
                                        @elseif ($honorSaatIni > 0)
                                            <span class="kuota-badge sisa">
                                                Terpakai Rp {{ number_format($honorSaatIni, 0, ',', '.') }} &middot;
                                                Sisa Rp {{ number_format($sisaKuota, 0, ',', '.') }} (Maks Rp
                                                {{ number_format($batasMitra, 0, ',', '.') }})
                                            </span>
                                        @else
                                            <span class="kuota-badge aman">
                                                Maks Batas: Rp {{ number_format($batasMitra, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </label>

                                    <div class="assign-vol-wrap">
                                        <input type="text" name="volumes[{{ $m->id }}]"
                                            class="assign-vol-input" inputmode="decimal" placeholder="Volume"
                                            value="{{ $volOld }}"
                                            aria-label="Volume untuk {{ $m->user->name }}">
                                        <span class="assign-satuan">{{ $penugasan->satuan }}</span>
                                    </div>

                                    <div class="assign-honor-wrap">
                                        <div class="assign-honor" data-row-honor>Rp 0</div>
                                        <small class="kuota-warn" data-row-warn hidden></small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="assignQuotaWarn" role="status" class="form-alert-warn" hidden></div>
                    <div id="assignErrorClient" role="alert" class="form-alert-error form-alert-inline" hidden></div>

                    <div class="form-navigation">
                        <a href="{{ route($routes['index']) }}" class="outline-btn btn-link">
                            <svg viewBox="0 0 24 24">
                                <path d="M15 6l-6 6 6 6" />
                            </svg>
                            <span>Kembali</span>
                        </a>

                        <button type="submit" class="dark-pill-btn">
                            <span>Assign Petugas</span>
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8.5v7M8.5 12h7" />
                            </svg>
                        </button>
                    </div>
                </form>
            @endif
        </section>

        {{-- DAFTAR PETUGAS YANG SUDAH DITUGASKAN --}}
        <section class="form-card">
            <div class="form-card-head">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <circle cx="9" cy="8" r="3.5" />
                        <path d="M2 20c.6-3.5 3.2-5.5 7-5.5s6.4 2 7 5.5" />
                        <path d="M15.5 4.8a3.5 3.5 0 010 6.4M18 14.8c2 .7 3.6 2.4 4 5.2" />
                    </svg>
                    Petugas yang Sudah Ditugaskan ({{ $totalPetugas }})
                </h2>
            </div>

            <div class="table-scroll">
                <table class="mitra-table assign-petugas-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Mitra</th>
                            <th>Volume ({{ $penugasan->satuan }})</th>
                            <th>Honor</th>
                            <th>Pembayaran</th>
                            <th>Aksi</th>
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
                                $totalBulanIni = $mitra
                                    ? (int) ($honorBulanMitra[$mitra->id] ?? $p->jumlah)
                                    : (int) $p->jumlah;
                                $batasMitraExisting = $mitra
                                    ? (int) ($batasExistingMitra[$mitra->id] ??
                                        \App\Support\BatasHonor::untukPeran($mitra->wilayah, $penugasan->sebagai))
                                    : \App\Support\BatasHonor::PENDATAAN_KENDARI;
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="profile-name">{{ $nama }}</span>
                                    <span class="cell-sub">
                                        {{ $user?->username ?? '-' }}@if ($mitra?->wilayah)
                                            &middot; {{ $mitra->wilayah }}
                                        @endif
                                    </span>
                                    @if ($batasMitraExisting > 0 && $totalBulanIni > $batasMitraExisting)
                                        <span class="cell-sub lock-note">
                                            Total bulan ini: Rp {{ number_format($totalBulanIni, 0, ',', '.') }}
                                            (Maks Rp {{ number_format($batasMitraExisting, 0, ',', '.') }})
                                        </span>
                                    @endif
                                    @if ($diverifikasi)
                                        <span class="cell-sub lock-note">Kontrak sudah diverifikasi</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST"
                                        action="{{ route($routes['petugasUpdate'], [$penugasan, $p]) }}"
                                        id="volForm_{{ $p->id }}" class="inline-vol-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="volume" value="{{ $volVal }}"
                                            inputmode="decimal" class="existing-vol-input"
                                            data-target="honor_{{ $p->id }}" @disabled($terkunci)
                                            aria-label="Volume {{ $nama }}">
                                    </form>
                                </td>
                                <td class="honor-cell" id="honor_{{ $p->id }}">
                                    Rp {{ number_format($p->jumlah ?? 0, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($bisaKelolaBayar)
                                        <label class="inline-bayar">
                                            <input type="checkbox" class="bayar-petugas-check"
                                                data-url="{{ route($routes['pekerjaanBayar'], $p) }}"
                                                @checked($dibayar)>
                                            @if ($dibayar)
                                                <span class="status-badge active">Sudah Dibayar</span>
                                            @else
                                                <span class="status-badge pending">Belum Dibayar</span>
                                            @endif
                                        </label>
                                    @else
                                        @if ($dibayar)
                                            <span class="status-badge active">Sudah Dibayar</span>
                                        @else
                                            <span class="status-badge pending">Belum Dibayar</span>
                                        @endif
                                    @endif

                                    @if ($dibayar && $p->dibayar_pada)
                                        <span class="cell-sub">{{ $p->dibayar_pada->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    <div class="aksi-wrap">
                                        <button type="submit" form="volForm_{{ $p->id }}"
                                            class="mini-btn save" @disabled($terkunci)>
                                            Simpan
                                        </button>

                                        <form method="POST"
                                            action="{{ route($routes['petugasHapus'], [$penugasan, $p]) }}"
                                            class="hapus-petugas-form" data-nama="{{ $nama }}"
                                            style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="mini-btn del" @disabled($terkunci)
                                                title="{{ $terkunci ? 'Tidak bisa dihapus karena honor sudah dibayar atau kontrak sudah diverifikasi' : 'Keluarkan mitra dari tugas ini' }}">
                                                Keluarkan
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="picker-empty">
                                        Belum ada petugas yang ditugaskan. Silakan pilih mitra dan isi volumenya pada form
                                        di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @push('scripts')
            <script>
                (function() {
                    const HARGA_SATUAN = @json($hargaSatuan);
                    const SATUAN = @json($penugasan->satuan);
                    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

                    const formatRp = n => 'Rp ' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const parseNum = v => parseFloat(String(v || '0').replace(',', '.')) || 0;

                    /* ===== Picker Assign Baru ===== */
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
                        const warnQuota = document.getElementById('assignQuotaWarn');
                        const errClient = document.getElementById('assignErrorClient');

                        function updateRowHonor(row) {
                            const cb = row.querySelector('.assign-check');
                            const volInput = row.querySelector('.assign-vol-input');
                            const honorEl = row.querySelector('[data-row-honor]');
                            const warnEl = row.querySelector('[data-row-warn]');
                            const honorBulan = parseInt(row.dataset.honorBulan || '0', 10);
                            const batasMitra = parseInt(row.dataset.batas || '0', 10);
                            const vol = parseNum(volInput.value);
                            const honor = vol > 0 ? Math.round(vol * HARGA_SATUAN) : 0;
                            const totalBaru = honorBulan + honor;
                            const over = batasMitra > 0 && cb.checked && honor > 0 && totalBaru > batasMitra;

                            honorEl.textContent = formatRp(honor);
                            row.classList.toggle('selected', cb.checked);
                            row.classList.toggle('row-over-quota', over);

                            if (over) {
                                warnEl.textContent = 'Total jadi ' + formatRp(totalBaru) + ' (maks ' + formatRp(batasMitra) +
                                    ')';
                                warnEl.hidden = false;
                            } else {
                                warnEl.textContent = '';
                                warnEl.hidden = true;
                            }

                            if (cb.checked && vol > 0) {
                                row.classList.remove('row-invalid');
                            }
                        }

                        function segarkanTerpilih() {
                            const dipilih = rows.filter(r => r.querySelector('.assign-check').checked);
                            jumlahDipilih.textContent = dipilih.length + ' dipilih';

                            daftarTerpilih.innerHTML = '';
                            let totalHonor = 0;
                            const overList = [];

                            dipilih.forEach(row => {
                                const cb = row.querySelector('.assign-check');
                                const volInput = row.querySelector('.assign-vol-input');
                                const nama = row.dataset.nama;
                                const honorBulan = parseInt(row.dataset.honorBulan || '0', 10);
                                const batasMitra = parseInt(row.dataset.batas || '0', 10);
                                const vol = parseNum(volInput.value);
                                const honor = vol > 0 ? Math.round(vol * HARGA_SATUAN) : 0;
                                totalHonor += honor;

                                if (batasMitra > 0 && honor > 0 && (honorBulan + honor) > batasMitra) {
                                    overList.push(nama + ' (' + formatRp(honorBulan + honor) + ' / Maks ' + formatRp(
                                        batasMitra) + ')');
                                }

                                const chip = document.createElement('span');
                                chip.className = 'chip';

                                const teks = document.createElement('span');
                                teks.textContent = nama + (vol > 0 ? ' (' + vol + ' ' + SATUAN + ' · ' + formatRp(
                                    honor) + ')' : ' (vol. belum diisi)');

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

                            if (overList.length > 0) {
                                warnQuota.innerHTML = '<b>Perhatian batas honor:</b> ' +
                                    overList.join(', ') + ' akan melebihi batas honor bulanan.';
                                warnQuota.hidden = false;
                            } else {
                                warnQuota.hidden = true;
                            }
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
    </x-dynamic-component>
