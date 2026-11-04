        <!-- KONTEN -->

        <x-admin.main>
            {{-- HEADER HALAMAN --}}
            <header class="page-heading">
                <div>
                    <h1>Mitra BPS Kota Kendari</h1>
                    <p>Manajemen Data Mitra BPS Kota Kendari</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="header-icon" aria-label="Kalender" title="Kalender">
                        <svg>
                            <use href="#i-cal" />
                        </svg>
                    </button>

                    <button type="button" class="header-icon" aria-label="Notifikasi" title="Notifikasi">
                        <svg>
                            <use href="#i-bell" />
                        </svg>
                    </button>
                    <div class="header-user">
                        <span>{{ auth()->user()->name }}</span>
                        <div class="avatar" style="width:48px;height:48px">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            {{-- KARTU STATISTIK --}}
            <section class="stats-mitra">
                <div class="card hero">
                    <h2>
                        <svg>
                            <use href="#i-ring" />
                        </svg>
                        Progres Tugas Mitra
                    </h2>
                    <p class="num">32%</p>
                </div>

                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-user" />
                        </svg>
                        Total Mitra
                    </h2>
                    <p class="num">{{ $total }}</p> {{-- di kartu Total Mitra --}}
                </div>

                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-user" />
                        </svg>
                        Mitra Aktif
                    </h2>
                    <p class="num">{{ $aktif }}</p> {{-- di kartu Mitra Aktif --}}
                </div>

                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-cal" />
                        </svg>
                        Mitra Non Aktif
                    </h2>
                    <p class="num">{{ $nonaktif }}</p> {{-- di kartu Mitra Non Aktif --}}
                </div>
            </section>

            {{-- PENCARIAN, EXPORT, STATUS, FILTER --}}
            <section class="mitra-toolbar">
                {{-- Pencarian (dikirim ke server saat menekan Enter) --}}
                <form method="GET" action="{{ route('admin.datamitra') }}" style="display: contents">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    @foreach (['honor_mode', 'honor_jumlah', 'honor_dari', 'honor_sampai', 'wilayah'] as $f)
                        @if (request()->filled($f))
                            <input type="hidden" name="{{ $f }}" value="{{ request($f) }}">
                        @endif
                    @endforeach

                    @foreach (array_filter((array) request('tugas', [])) as $t)
                        <input type="hidden" name="tugas[]" value="{{ $t }}">
                    @endforeach

                    <label class="mitra-search">
                        <svg>
                            <use href="#i-search" />
                        </svg>
                        <input id="searchMitra" type="search" name="q" value="{{ $q }}"
                            placeholder="Cari nama, Sobat ID, atau NIK..." autocomplete="off">
                    </label>
                </form>

                <button type="button" class="outline-btn" id="exportMitra">
                    <svg>
                        <use href="#i-download" />
                    </svg>
                    Export
                </button>

                <div class="status-switch" aria-label="Filter status mitra">
                    <button type="button" class="{{ $status === 'active' ? 'active' : '' }}" data-status="active"
                        aria-pressed="{{ $status === 'active' ? 'true' : 'false' }}"
                        onclick="window.location='{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => null]) }}'">
                        Active
                    </button>

                    <button type="button" class="{{ $status === 'inactive' ? 'active' : '' }}" data-status="inactive"
                        aria-pressed="{{ $status === 'inactive' ? 'true' : 'false' }}"
                        onclick="window.location='{{ request()->fullUrlWithQuery(['status' => 'inactive', 'page' => null]) }}'">
                        Inactive
                    </button>
                </div>

                @php
                    $tugasDipilih = array_values(array_filter((array) request('tugas', [])));
                    $honorMode = request('honor_mode', 'nominal');
                    $adaHonor =
                        $honorMode === 'nominal'
                            ? request()->filled('honor_jumlah')
                            : request()->filled('honor_dari') || request()->filled('honor_sampai');
                    $jumlahFilter = (int) $adaHonor + (int) request()->filled('wilayah') + count($tugasDipilih);
                @endphp

                <button type="button" class="outline-btn" id="filterToggle" aria-expanded="false"
                    aria-controls="filterPanel">
                    <svg>
                        <use href="#i-filter" />
                    </svg>
                    Filter
                    @if ($jumlahFilter > 0)
                        <span class="filter-count">{{ $jumlahFilter }}</span>
                    @endif
                </button>
            </section>

            {{-- TABEL MITRA --}}
            <section class="table-card">
                <div class="table-heading">
                    <h2>
                        <svg>
                            <use href="#i-user" />
                        </svg>
                        List Mitra
                    </h2>

                    <a href="{{ route('admin.tambahmitra') }}" class="primary-btn" style="text-decoration:none">
                        <svg>
                            <use href="#i-plus" />
                        </svg>
                        Add Mitra
                    </a>
                </div>

                <div class="table-scroll">
                    <table class="mitra-table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" class="check-mitra" id="selectAll"
                                        aria-label="Pilih semua mitra">
                                </th>
                                <th>Profile</th>
                                <th>Nama</th>
                                <th>Wilayah</th>
                                <th>Sobat ID</th>
                                <th>NIK</th>
                                <th>Total Honor</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="mitraTableBody">
                            @forelse ($daftarMitra as $mitra)
                                <tr data-status="{{ $mitra->user->is_active ? 'active' : 'inactive' }}"
                                    data-name="{{ strtolower($mitra->user->name) }}"
                                    data-honor="{{ $mitra->pekerjaan_sum_jumlah ?? 0 }}">

                                    <td>
                                        <input type="checkbox" class="check-mitra row-check"
                                            aria-label="Pilih {{ $mitra->user->name }}">
                                    </td>

                                    <td>
                                        <div class="profile-avatar">
                                            {{ strtoupper(substr($mitra->user->name, 0, 1)) }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="profile-name">{{ $mitra->user->name }}</span>
                                    </td>

                                    <td><span class="profile-name">{{ $mitra->wilayah }}</span></td>

                                    <td class="id-cell">{{ $mitra->user->username }}</td>

                                    <td class="id-cell">{{ $mitra->nik }}</td>

                                    <td class="honor-cell">
                                        Rp {{ number_format($mitra->pekerjaan_sum_jumlah ?? 0, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        @if ($mitra->user->is_active)
                                            <span class="status-badge active">Aktif</span>
                                        @else
                                            <span class="status-badge inactive">Tidak Aktif</span>
                                        @endif
                                    </td>

                                    @php
                                        // Sesuaikan nama kolom 'nama' dengan kolom nama tugas di tabel pekerjaan Anda
                                        // $tugas = $mitra->pekerjaan?->pluck('nama')->values()->all() ?? [];
                                        $tugas = $tugasPerMitra[$mitra->id] ?? [];
                                        $urlEdit = Route::has('admin.mitra.edit')
                                            ? route('admin.mitra.edit', $mitra->id)
                                            : '';
                                        $urlHapus = Route::has('admin.mitra.destroy')
                                            ? route('admin.mitra.destroy', $mitra->id)
                                            : '';
                                    @endphp

                                    <td class="action-cell">
                                        <button type="button" class="action-btn"
                                            aria-label="Aksi {{ $mitra->user->name }}" aria-haspopup="true"
                                            aria-expanded="false" data-nama="{{ $mitra->user->name }}"
                                            data-sobat="{{ $mitra->user->username }}"
                                            data-honor="Rp {{ number_format($mitra->pekerjaan_sum_jumlah ?? 3500000, 0, ',', '.') }}"
                                            data-tugas="{{ json_encode($tugas) }}"
                                            data-wilayah="{{ $mitra->wilayah }}" data-edit-url="{{ $urlEdit }}"
                                            data-delete-url="{{ $urlHapus }}">
                                            &#8942;
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:24px">
                                        @if ($q !== '')
                                            Tidak ada mitra yang cocok dengan "{{ $q }}".
                                        @else
                                            Belum ada data mitra pada status ini.
                                            <a href="{{ route('admin.mitra.import') }}">Import dari Excel</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span>
                        @if ($daftarMitra->total() > 0)
                            Menampilkan {{ $daftarMitra->firstItem() }}–{{ $daftarMitra->lastItem() }}
                            dari {{ $daftarMitra->total() }} mitra
                        @else
                            Tidak ada data
                        @endif
                    </span>

                    @if ($daftarMitra->hasPages())
                        <span style="display:flex;gap:8px;align-items:center">
                            @if ($daftarMitra->onFirstPage())
                                <button type="button" class="outline-btn" disabled>&lt;
                                </button>
                            @else
                                <a class="outline-btn" style="text-decoration:none"
                                    href="{{ $daftarMitra->previousPageUrl() }}">
                                    < </a>
                            @endif

                            <span>Halaman {{ $daftarMitra->currentPage() }} dari {{ $daftarMitra->lastPage() }}</span>

                            @if ($daftarMitra->hasMorePages())
                                <a class="outline-btn" style="text-decoration:none"
                                    href="{{ $daftarMitra->nextPageUrl() }}">></a>
                            @else
                                <button type="button" class="outline-btn" disabled>></button>
                            @endif
                        </span>
                    @endif
                </div>
            </section>
            {{-- POPOVER AKSI MITRA (satu untuk semua baris) --}}
            <div class="mitra-popover" id="mitraPopover" role="dialog" aria-label="Detail mitra">
                <div class="pop-field">
                    <span class="pop-label">Name</span>
                    <strong class="pop-name" id="popNama"></strong>
                </div>

                <div class="pop-row">
                    <div class="pop-field">
                        <span class="pop-label">Sobat ID</span>
                        <strong class="pop-value" id="popSobat"></strong>
                    </div>
                    <div class="pop-field">
                        <span class="pop-label">Total Honor</span>
                        <strong class="pop-value" id="popHonor"></strong>
                    </div>
                </div>

                <div class="pop-field">
                    <span class="pop-label">Wilayah</span>
                    <div class="pop-value" id="popWilayah"></div>
                </div>

                <div class="pop-field">
                    <span class="pop-label">Tugas</span>
                    <div class="pop-chips" id="popTugas"></div>
                </div>


                <div class="pop-field">
                    <span class="pop-label">Aksi</span>
                    <div class="pop-actions">
                        <button type="button" class="pop-btn pop-btn-del" id="popHapus">
                            Hapus Akun Mitra
                            <svg viewBox="0 0 24 24">
                                <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                            </svg>
                        </button>
                        <button type="button" class="pop-btn pop-btn-edit" id="popEdit">
                            Edit Akun Mitra
                            <svg viewBox="0 0 24 24">
                                <path d="M4 20h4L19 9l-4-4L4 16v4zM13.5 6.5l4 4" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="filter-overlay" id="filterOverlay"></div>

            <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Data Mitra"
                aria-hidden="true">
                <form method="GET" action="{{ route('admin.datamitra') }}" id="filterForm" class="filter-form">
                    {{-- pertahankan pencarian, status, dan urutan --}}
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    <div class="filter-body">
                        <h2 class="filter-title">Filter Data Mitra</h2>

                        {{-- HONOR --}}
                        <div class="filter-section">
                            <span class="filter-label">Honor</span>

                            <div class="honor-box">
                                <div class="honor-mode">
                                    <label><input type="radio" name="honor_mode" value="nominal"
                                            {{ $honorMode === 'nominal' ? 'checked' : '' }}> Nominal</label>
                                    <label><input type="radio" name="honor_mode" value="rentang"
                                            {{ $honorMode === 'rentang' ? 'checked' : '' }}> Rentang</label>
                                </div>

                                {{-- Mode nominal: satu kolom --}}
                                <div class="honor-fields" id="honorNominal">
                                    <span class="filter-sub">Jumlah</span>
                                    <div class="rupiah-input">
                                        <span>Rp</span>
                                        <input type="text" inputmode="numeric" name="honor_jumlah" data-rupiah
                                            value="{{ request('honor_jumlah') }}" placeholder="100.000">
                                    </div>
                                </div>

                                {{-- Mode rentang: dua kolom --}}
                                <div class="honor-fields" id="honorRentang">
                                    <div class="honor-two">
                                        <div>
                                            <span class="filter-sub">Dari</span>
                                            <div class="rupiah-input">
                                                <span>Rp</span>
                                                <input type="text" inputmode="numeric" name="honor_dari"
                                                    data-rupiah value="{{ request('honor_dari') }}"
                                                    placeholder="100.000">
                                            </div>
                                        </div>
                                        <div>
                                            <span class="filter-sub">Sampai</span>
                                            <div class="rupiah-input">
                                                <span>Rp</span>
                                                <input type="text" inputmode="numeric" name="honor_sampai"
                                                    data-rupiah value="{{ request('honor_sampai') }}"
                                                    placeholder="100.000">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- WILAYAH --}}
                        <div class="filter-section">
                            <label class="filter-label" for="filterWilayah">Wilayah</label>
                            <div class="select-wrap">
                                <select name="wilayah" id="filterWilayah">
                                    <option value="">Pilih Wilayah</option>
                                    @foreach ($daftarWilayah ?? ['Kota Kendari', 'Kab. Konawe Kepulauan'] as $w)
                                        <option value="{{ $w }}"
                                            {{ request('wilayah') === $w ? 'selected' : '' }}>
                                            {{ $w }}</option>
                                    @endforeach
                                </select>
                                <svg>
                                    <use href="#i-down" />
                                </svg>
                            </div>
                        </div>

                        {{-- TUGAS --}}
                        <div class="filter-section">
                            <label class="filter-label" for="filterTugas">Tugas</label>
                            <div class="select-wrap">
                                <select id="filterTugas">
                                    <option value="">Pilih Tugas</option>
                                    @foreach ($daftarTugas ?? ['Susenas', 'Seruti'] as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                                <svg>
                                    <use href="#i-down" />
                                </svg>
                            </div>

                            <div class="filter-chips" id="tugasChips" data-selected='@json($tugasDipilih)'>
                            </div>
                        </div>
                    </div>

                    <div class="filter-footer">
                        <button type="button" class="filter-reset" id="filterReset">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5" />
                            </svg>
                            Reset
                        </button>
                        <button type="button" class="btn-cancel" id="filterCancel">Cancel</button>
                        <button type="submit" class="btn-apply">Download</button>
                    </div>
                </form>
            </aside>

            <div class="modal-overlay" id="exportOverlay"></div>

            <div class="export-modal" id="exportModal" role="dialog" aria-modal="true"
                aria-labelledby="exportTitle" aria-hidden="true">
                <form method="GET" action="{{ route('admin.mitra.export') }}" id="exportForm">
                    {{-- bawa semua pencarian dan filter yang sedang aktif --}}
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @foreach (['honor_mode', 'honor_jumlah', 'honor_dari', 'honor_sampai', 'wilayah'] as $f)
                        @if (request()->filled($f))
                            <input type="hidden" name="{{ $f }}" value="{{ request($f) }}">
                        @endif
                    @endforeach
                    @foreach (array_filter((array) request('tugas', [])) as $t)
                        <input type="hidden" name="tugas[]" value="{{ $t }}">
                    @endforeach

                    <h2 id="exportTitle" class="export-title">Export Data Mitra</h2>
                    <p class="export-sub">Data yang diexport berdasarkan hasil filter</p>

                    <div class="export-options">
                        <label class="export-option">
                            <input type="radio" name="format" value="csv" checked>
                            <svg class="export-icon" viewBox="0 0 32 32">
                                <path d="M8 3h11l7 7v19H8zM19 3v7h7" />
                                <text x="16" y="25" text-anchor="middle" font-size="7" font-weight="600"
                                    fill="currentColor" stroke="none">CSV</text>
                            </svg>
                            <span class="export-text">
                                <strong>CSV</strong>
                                <small>Dokumen diekspor dengan format CSV</small>
                            </span>
                            <span class="export-radio"></span>
                        </label>

                        <label class="export-option">
                            <input type="radio" name="format" value="pdf">
                            <svg class="export-icon" viewBox="0 0 32 32">
                                <path d="M8 3h11l7 7v19H8zM19 3v7h7" />
                                <text x="16" y="25" text-anchor="middle" font-size="7" font-weight="600"
                                    fill="currentColor" stroke="none">PDF</text>
                            </svg>
                            <span class="export-text">
                                <strong>PDF</strong>
                                <small>Dokumen diekspor dengan format PDF</small>
                            </span>
                            <span class="export-radio"></span>
                        </label>
                    </div>

                    <div class="export-footer">
                        <button type="button" class="btn-cancel" id="exportCancel">Cancel</button>
                        <button type="submit" class="btn-apply">Download</button>
                    </div>
                </form>
            </div>

            <form id="hapusMitraForm" method="POST" style="display:none">
                @csrf
                @method('DELETE')
            </form>
            @push('scripts')
                <script>
                    /* Pilih semua baris pada halaman ini */
                    const pilihSemua = document.getElementById('selectAll');
                    if (pilihSemua) {
                        pilihSemua.addEventListener('change', e => {
                            document.querySelectorAll('#mitraTableBody .row-check')
                                .forEach(cb => cb.checked = e.target.checked);
                        });
                    }

                    /* Popover aksi mitra */
                    (function() {
                        const pop = document.getElementById('mitraPopover');
                        const tbody = document.getElementById('mitraTableBody');
                        const hapusForm = document.getElementById('hapusMitraForm');
                        let aktif = null;

                        function tutup() {
                            pop.classList.remove('open');
                            if (aktif) aktif.setAttribute('aria-expanded', 'false');
                            aktif = null;
                        }

                        function posisi(btn) {
                            const r = btn.getBoundingClientRect();
                            const gap = 8,
                                margin = 8;
                            const w = pop.offsetWidth,
                                h = pop.offsetHeight;

                            // Utamakan di kiri tombol (kolom aksi ada di sisi kanan tabel)
                            let left = r.left - w - gap;
                            if (left < margin) left = r.right + gap;
                            left = Math.min(left, window.innerWidth - w - margin);
                            left = Math.max(left, margin);

                            // Sejajar atas tombol, jaga agar tidak keluar layar
                            let top = r.top;
                            top = Math.min(top, window.innerHeight - h - margin);
                            top = Math.max(top, margin);

                            pop.style.left = left + 'px';
                            pop.style.top = top + 'px';
                        }

                        function buka(btn) {

                            if (aktif && aktif !== btn) {
                                aktif.setAttribute('aria-expanded', 'false');
                            }

                            document.getElementById('popNama').textContent = btn.dataset.nama;
                            document.getElementById('popWilayah').textContent = btn.dataset.wilayah;
                            document.getElementById('popSobat').textContent = btn.dataset.sobat;
                            document.getElementById('popHonor').textContent = btn.dataset.honor;

                            const wadah = document.getElementById('popTugas');
                            wadah.innerHTML = '';
                            let tugas = [];
                            try {
                                tugas = JSON.parse(btn.dataset.tugas || '[]');
                            } catch (e) {}
                            if (tugas.length === 0) {
                                const kosong = document.createElement('span');
                                kosong.className = 'pop-empty';
                                kosong.textContent = 'Belum ada tugas';
                                wadah.appendChild(kosong);
                            } else {
                                tugas.forEach(t => {
                                    const chip = document.createElement('span');
                                    chip.className = 'pop-chip';
                                    chip.textContent = t;
                                    wadah.appendChild(chip);
                                });
                            }

                            aktif = btn;
                            btn.setAttribute('aria-expanded', 'true');
                            pop.classList.add('open');
                            posisi(btn);
                        }

                        // Klik titik tiga (delegasi event)
                        tbody.addEventListener('click', e => {
                            const btn = e.target.closest('.action-btn');
                            if (!btn) return;
                            e.stopPropagation();
                            (aktif === btn) ? tutup(): buka(btn);
                        });

                        // Klik di luar popover = tutup
                        document.addEventListener('click', e => {
                            if (aktif && !pop.contains(e.target)) tutup();
                        });
                        document.addEventListener('keydown', e => {
                            if (e.key === 'Escape') tutup();
                        });
                        window.addEventListener('resize', tutup);
                        window.addEventListener('scroll', e => {
                            if (aktif && !pop.contains(e.target)) tutup();
                        }, true);

                        // Tombol edit & hapus
                        document.getElementById('popEdit').addEventListener('click', () => {
                            const url = aktif?.dataset.editUrl;
                            if (url) {
                                window.location = url;
                                return;
                            }
                            tutup();
                            popup({
                                jenis: 'info',
                                judul: 'Belum tersedia',
                                pesan: 'Halaman edit mitra belum dibuat.'
                            });
                        });

                        document.getElementById('popHapus').addEventListener('click', async () => {
                            const url = aktif?.dataset.deleteUrl;
                            const nama = aktif?.dataset.nama;

                            tutup(); // tutup popover aksi lebih dulu

                            if (!url) {
                                popup({
                                    jenis: 'info',
                                    judul: 'Belum tersedia',
                                    pesan: 'Fitur hapus mitra belum dibuat.'
                                });
                                return;
                            }

                            const lanjut = await popup({
                                jenis: 'hapus',
                                judul: 'Hapus akun mitra?',
                                pesan: 'Akun "' + nama + '" akan dihapus permanen dan tidak bisa dikembalikan.',
                                tombolOk: 'Ya, hapus',
                                tombolBatal: 'Batal'
                            });

                            if (lanjut) {
                                hapusForm.action = url;
                                hapusForm.submit();
                            }
                        });
                    })();
                    /* Filter data mitra */
                    (function() {
                        const panel = document.getElementById('filterPanel');
                        const overlay = document.getElementById('filterOverlay');
                        const toggle = document.getElementById('filterToggle');
                        const form = document.getElementById('filterForm');
                        const chips = document.getElementById('tugasChips');
                        const selTugas = document.getElementById('filterTugas');

                        /* Buka / tutup panel */
                        function buka() {
                            panel.classList.add('open');
                            overlay.classList.add('open');
                            panel.setAttribute('aria-hidden', 'false');
                            toggle.setAttribute('aria-expanded', 'true');
                        }

                        function tutup() {
                            panel.classList.remove('open');
                            overlay.classList.remove('open');
                            panel.setAttribute('aria-hidden', 'true');
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                        toggle.addEventListener('click', buka);
                        overlay.addEventListener('click', tutup);
                        document.getElementById('filterCancel').addEventListener('click', tutup);
                        document.addEventListener('keydown', e => {
                            if (e.key === 'Escape') tutup();
                        });

                        /* Mode honor: nominal <-> rentang */
                        const boxNominal = document.getElementById('honorNominal');
                        const boxRentang = document.getElementById('honorRentang');

                        function aturMode() {
                            const mode = form.querySelector('input[name="honor_mode"]:checked').value;
                            const nominal = mode === 'nominal';
                            boxNominal.hidden = !nominal;
                            boxRentang.hidden = nominal;
                            // input yang tersembunyi dinonaktifkan agar tidak ikut terkirim
                            boxNominal.querySelectorAll('input').forEach(i => i.disabled = !nominal);
                            boxRentang.querySelectorAll('input').forEach(i => i.disabled = nominal);
                        }
                        form.querySelectorAll('input[name="honor_mode"]').forEach(r => r.addEventListener('change', aturMode));
                        aturMode();

                        /* Format rupiah: 1500000 -> 1.500.000 */
                        const formatRp = v => v.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        form.querySelectorAll('[data-rupiah]').forEach(input => {
                            input.value = formatRp(input.value);
                            input.addEventListener('input', () => input.value = formatRp(input.value));
                        });
                        // buang titik sebelum dikirim ke server
                        form.addEventListener('submit', () => {
                            form.querySelectorAll('[data-rupiah]').forEach(i => i.value = i.value.replace(/\./g, ''));
                        });

                        /* Tugas: pilih dari dropdown -> muncul sebagai chip */
                        function tambahChip(nama) {
                            if (!nama || chips.querySelector('[data-nama="' + CSS.escape(nama) + '"]')) return;

                            const chip = document.createElement('span');
                            chip.className = 'filter-chip';
                            chip.dataset.nama = nama;

                            const teks = document.createElement('span');
                            teks.textContent = nama;

                            const hapus = document.createElement('button');
                            hapus.type = 'button';
                            hapus.setAttribute('aria-label', 'Hapus ' + nama);
                            hapus.textContent = '×';
                            hapus.addEventListener('click', () => chip.remove());

                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'tugas[]';
                            hidden.value = nama;

                            chip.append(teks, hapus, hidden);
                            chips.appendChild(chip);
                        }

                        selTugas.addEventListener('change', () => {
                            tambahChip(selTugas.value);
                            selTugas.value = '';
                        });

                        // pulihkan chip dari filter yang sedang aktif
                        try {
                            JSON.parse(chips.dataset.selected || '[]').forEach(tambahChip);
                        } catch (e) {}

                        /* Reset: kosongkan semua pilihan lalu terapkan */
                        document.getElementById('filterReset').addEventListener('click', () => {
                            form.querySelectorAll('[data-rupiah]').forEach(i => i.value = '');
                            form.querySelector('input[name="honor_mode"][value="nominal"]').checked = true;
                            document.getElementById('filterWilayah').value = '';
                            chips.innerHTML = '';
                            selTugas.value = '';
                            aturMode();
                            form.submit();
                        });
                    })();
                    /* Export data mitra */
                        (function() {
                            const modal = document.getElementById('exportModal');
                            const overlay = document.getElementById('exportOverlay');
                            const tombol = document.getElementById('exportMitra');
                            const form = document.getElementById('exportForm');

                            function buka() {
                                modal.classList.add('open');
                                overlay.classList.add('open');
                                modal.setAttribute('aria-hidden', 'false');
                            }

                            function tutup() {
                                modal.classList.remove('open');
                                overlay.classList.remove('open');
                                modal.setAttribute('aria-hidden', 'true');
                            }

                            tombol.addEventListener('click', buka);
                            overlay.addEventListener('click', tutup);
                            document.getElementById('exportCancel').addEventListener('click', tutup);
                            document.addEventListener('keydown', e => {
                                if (e.key === 'Escape') tutup();
                            });

                            // File diunduh oleh browser, halaman tetap di tempat. Tutup modal setelahnya.
                            form.addEventListener('submit', () => setTimeout(tutup, 400));
                        })();
                </script>
            @endpush

            @if (session('gagal'))
                <div id="popupGagal" role="alertdialog" aria-modal="true" aria-labelledby="popupGagalJudul"
                    style="position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;padding:16px">
                    <div
                        style="background:#fff;color:#17261f;border-radius:16px;max-width:400px;width:100%;padding:28px 24px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.25)">
                        <div
                            style="width:56px;height:56px;margin:0 auto 14px;border-radius:50%;background:#fdecea;color:#b3261e;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700">
                            !</div>
                        <h3 id="popupGagalJudul" style="margin:0 0 8px;font-size:18px">Tidak berhasil</h3>
                        <p style="margin:0 0 20px;font-size:14px;color:#5f7068">{{ session('gagal') }}</p>
                        <button type="button" id="popupGagalTutup"
                            style="padding:10px 28px;font-size:15px;font-weight:600;color:#fff;background:#b3261e;border:0;border-radius:10px;cursor:pointer">
                            OK
                        </button>
                    </div>
                </div>

                @push('scripts')
                    <script>
                        (function() {
                            const popup = document.getElementById('popupGagal');
                            const tutup = () => popup && popup.remove();
                            document.getElementById('popupGagalTutup').addEventListener('click', tutup);
                            popup.addEventListener('click', e => {
                                if (e.target === popup) tutup();
                            });
                            document.addEventListener('keydown', e => {
                                if (e.key === 'Escape') tutup();
                            });
                        })();
                    </script>
                @endpush
            @endif
        </x-admin.main>
