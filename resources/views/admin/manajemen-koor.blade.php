@php
    $bulan = $bulan ?? '';
    $daftarBulan = $daftarBulan ?? collect();
    $tugasDipilih = array_values(array_filter((array) request('tugas', [])));
    $adaPekerjaan = request()->filled('pekerjaan_min') || request()->filled('pekerjaan_max');
    $adaMitra = request()->filled('mitra_min') || request()->filled('mitra_max');
    $jumlahFilter = (int) ($bulan !== '') + (int) $adaPekerjaan + (int) $adaMitra + count($tugasDipilih);

    // Input tersembunyi untuk meneruskan filter ke form pencarian & export
    $kunciFilter = ['bulan', 'pekerjaan_min', 'pekerjaan_max', 'mitra_min', 'mitra_max'];
    $hiddenFilter = collect($kunciFilter)
        ->filter(fn($k) => request()->filled($k))
        ->values()
        ->map(fn($k) => '<input type="hidden" name="' . $k . '" value="' . e(request($k)) . '">')
        ->merge(collect($tugasDipilih)->map(fn($t) => '<input type="hidden" name="tugas[]" value="' . e($t) . '">'))
        ->implode('');
@endphp

<x-admin.main>
    {{-- HEADER HALAMAN (tanpa subjudul) --}}
    <x-header judul="Data Koordinator" />

    {{-- KARTU STATISTIK --}}
    <section class="stats-mitra stats-mitra-4">
        <div class="card hero">
            <h2>
                <svg>
                    <use href="#i-ring" />
                </svg>
                Presentasi Koordinator Aktif
            </h2>
            <p class="num">{{ $persenAktif }}%</p>
            <div class="progress"><span style="width: {{ $persenAktif }}%"></span></div>
        </div>

        <div class="card stat">
            <h2>
                <svg>
                    <use href="#i-koor" />
                </svg>
                Total Koordinator
            </h2>
            <p class="num">{{ $total }}</p>
            <span class="badge">{{ $aktif }} Aktif</span>
        </div>

        <div class="card stat">
            <h2>
                <svg>
                    <use href="#i-user" />
                </svg>
                Partisipan Mitra
            </h2>
            <p class="num">{{ $partisipanMitra }}</p>
            <span class="badge">{{ $mitraTersedia }} Tersedia</span>
        </div>

        <div class="card stat">
            <h2>
                <svg>
                    <use href="#i-briefcase" />
                </svg>
                Jumlah Pekerjaan
            </h2>
            <p class="num">{{ $jumlahPekerjaan }}</p>
        </div>
    </section>

    {{-- PENCARIAN, STATUS, FILTER --}}
    <section class="mitra-toolbar">
        @if (request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        @endif

        <form id="formKoor" method="GET" action="{{ route('admin.datakoor') }}" style="display: contents">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            {!! $hiddenFilter !!}
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input id="searchKoor" type="search" name="q" value="{{ $q }}"
                    placeholder="Cari nama atau NIP koordinator..." autocomplete="off">
            </label>
        </form>

        <x-admin.status-dropdown :options="['semua' => 'Semua', 'active' => 'Active', 'inactive' => 'Inactive']" :current="$status" label="Status" />

        <button type="button" class="outline-btn" id="filterToggle" aria-expanded="false" aria-controls="filterPanel">
            <svg>
                <use href="#i-filter" />
            </svg>
            Filter
            @if ($jumlahFilter > 0)
                <span class="filter-count">{{ $jumlahFilter }}</span>
            @endif
        </button>
    </section>

    {{-- TABEL KOORDINATOR --}}
    <section class="table-card">
        <div class="table-heading">
            <h2>
                <svg>
                    <use href="#i-table" />
                </svg>
                Tabel Koordinator
            </h2>

            <div class="table-heading-actions">
                <button type="button" class="outline-btn" id="exportKoor">
                    <span>Export</span>
                    <svg>
                        <use href="#i-export" />
                    </svg>
                </button>

                <a href="{{ route('admin.tambahkoor') }}" class="dark-pill-btn">
                    <span>Tambah Koordinator</span>
                    <svg>
                        <use href="#i-plus-circle" />
                    </svg>
                </a>
            </div>
        </div>

        <x-admin.bulk-delete :action="route('admin.koordinator.hapus-massal')" body="koorTableBody" noun="koordinator"
            catatan="Koordinator yang sudah pernah memberi tugas tidak akan dihapus." />

        <div class="table-scroll">
            <table class="mitra-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="check-mitra" id="selectAll"
                                aria-label="Pilih semua koordinator">
                        </th>
                        <th>Profile</th>
                        <th>Nama</th>
                        <th>NIP / ID</th>
                        <th>Jumlah Pekerjaan</th>
                        <th>Jumlah Mitra</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody id="koorTableBody">
                    @forelse ($daftarKoordinator as $k)
                        @php
                            $jumlahPekerjaanBaris = (int) $k->jumlah_pekerjaan;
                            $jumlahMitraBaris = (int) $k->jumlah_mitra;
                            $tugas = $pekerjaanPerKoordinator[$k->id] ?? [];
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="check-mitra row-check" value="{{ $k->id }}"
                                    aria-label="Pilih {{ $k->name }}">
                            </td>

                            <td>
                                <div class="profile-avatar">
                                    {{ strtoupper(substr($k->name, 0, 1)) }}
                                </div>
                            </td>

                            <td>
                                <span class="profile-name">{{ $k->name }}</span>
                            </td>

                            <td class="id-cell">{{ $k->username }}</td>

                            <td>
                                <span class="profile-name">{{ $jumlahPekerjaanBaris }} pekerjaan</span>
                            </td>

                            <td>
                                <span class="profile-name">{{ $jumlahMitraBaris }} mitra</span>
                            </td>

                            <td>
                                @if ($k->is_active)
                                    <span class="status-badge active">Aktif</span>
                                @else
                                    <span class="status-badge inactive">Tidak Aktif</span>
                                @endif
                            </td>

                            <td class="action-cell">
                                <button type="button" class="action-btn" aria-label="Detail {{ $k->name }}"
                                    aria-haspopup="true" aria-expanded="false" data-nama="{{ $k->name }}"
                                    data-username="{{ $k->username }}" data-pekerjaan="{{ $jumlahPekerjaanBaris }}"
                                    data-mitra="{{ $jumlahMitraBaris }}" data-tugas="{{ json_encode($tugas) }}"
                                    data-edit-url="{{ Route::has('admin.koordinator.edit') ? route('admin.koordinator.edit', $k->id) : '' }}"
                                    data-delete-url="{{ route('admin.koordinator.destroy', $k->id) }}">
                                    &#8942;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:24px">
                                @if ($q !== '')
                                    Tidak ada koordinator yang cocok dengan "{{ $q }}".
                                @else
                                    Belum ada koordinator pada status ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="table-footer-left">
                @if ($daftarKoordinator->total() > 0)
                    <span>Menampilkan</span>
                    <x-admin.per-page :current="$perPage" />
                    <span>dari {{ $daftarKoordinator->total() }} Koordinator</span>
                @else
                    <span>Tidak ada data</span>
                @endif
            </div>

            @if ($daftarKoordinator->hasPages())
                @php
                    $cur = $daftarKoordinator->currentPage();
                    $last = $daftarKoordinator->lastPage();
                    $pages = collect([1, $cur - 1, $cur, $cur + 1, $last])
                        ->filter(fn($p) => $p >= 1 && $p <= $last)
                        ->unique()
                        ->sort()
                        ->values();
                    $prev = null;
                @endphp

                <nav class="page-nav" aria-label="Navigasi halaman">
                    @if ($daftarKoordinator->onFirstPage())
                        <span class="page-btn disabled" aria-disabled="true">&lsaquo;</span>
                    @else
                        <a class="page-btn" href="{{ $daftarKoordinator->previousPageUrl() }}"
                            aria-label="Halaman sebelumnya">&lsaquo;</a>
                    @endif

                    @foreach ($pages as $p)
                        @if ($prev !== null && $p - $prev > 1)
                            <span class="page-dots">...</span>
                        @endif

                        @if ($p === $cur)
                            <span class="page-btn active" aria-current="page">{{ $p }}</span>
                        @else
                            <a class="page-btn" href="{{ $daftarKoordinator->url($p) }}">{{ $p }}</a>
                        @endif

                        @php $prev = $p; @endphp
                    @endforeach

                    @if ($daftarKoordinator->hasMorePages())
                        <a class="page-btn" href="{{ $daftarKoordinator->nextPageUrl() }}"
                            aria-label="Halaman berikutnya">&rsaquo;</a>
                    @else
                        <span class="page-btn disabled" aria-disabled="true">&rsaquo;</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>

    {{-- POPOVER DETAIL KOORDINATOR --}}
    <div class="mitra-popover" id="koorPopover" role="dialog" aria-label="Detail koordinator">
        <div class="pop-field">
            <span class="pop-label">Name</span>
            <strong class="pop-name" id="popNama"></strong>
        </div>

        <div class="pop-row">
            <div class="pop-field">
                <span class="pop-label">NIP / ID</span>
                <strong class="pop-value" id="popUsername"></strong>
            </div>
            <div class="pop-field">
                <span class="pop-label">Jumlah Mitra</span>
                <strong class="pop-value" id="popMitra"></strong>
            </div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Jumlah Pekerjaan</span>
            <strong class="pop-value" id="popPekerjaan"></strong>
        </div>

        <div class="pop-field">
            <span class="pop-label">Pekerjaan</span>
            <div class="pop-chips" id="popTugas"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Aksi</span>
            <div class="pop-actions">
                <button type="button" class="pop-btn pop-btn-del" id="popHapus">
                    Hapus Akun
                    <svg viewBox="0 0 24 24">
                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                    </svg>
                </button>
                <button type="button" class="pop-btn pop-btn-edit" id="popEdit">
                    Edit Akun
                    <svg viewBox="0 0 24 24">
                        <path d="M4 20h4L19 9l-4-4L4 16v4zM13.5 6.5l4 4" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- PANEL FILTER --}}
    <div class="filter-overlay" id="filterOverlay"></div>

    <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Koordinator" aria-hidden="true">
        @php
            $maksPekerjaan = max(
                (int) ($maksPekerjaan ?? 10),
                (int) request('pekerjaan_min'),
                (int) request('pekerjaan_max'),
            );
            $maksMitra = max((int) ($maksMitra ?? 50), (int) request('mitra_min'), (int) request('mitra_max'));

            $pekerjaanDari = (int) request('pekerjaan_min', 0);
            $pekerjaanSampai = request()->filled('pekerjaan_max') ? (int) request('pekerjaan_max') : $maksPekerjaan;
            $mitraDari = (int) request('mitra_min', 0);
            $mitraSampai = request()->filled('mitra_max') ? (int) request('mitra_max') : $maksMitra;
        @endphp

        <form method="GET" action="{{ route('admin.datakoor') }}" id="filterForm" class="filter-form">
            @if (request()->filled('per_page'))
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            @endif
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="sort" value="{{ $sort }}">

            <div class="filter-body">
                <h2 class="filter-title">Filter Koordinator</h2>

                <x-admin.filter-tugas judul="Bulan" placeholder="Cari Bulan / Tahun" nama="bulan"
                    :banyak="false" :opsi="$daftarBulan" :dipilih="$bulan !== '' ? [$bulan] : []" />

                <x-admin.filter-tugas judul="Tugas" placeholder="Cari Tugas" :opsi="$daftarTugas" :dipilih="$tugasDipilih" />

                <x-admin.filter-range judul="Rentang Pekerjaan" :max="$maksPekerjaan" :dari="$pekerjaanDari" :sampai="$pekerjaanSampai"
                    nama-dari="pekerjaan_min" nama-sampai="pekerjaan_max" />

                <x-admin.filter-range judul="Rentang Mitra" :max="$maksMitra" :dari="$mitraDari" :sampai="$mitraSampai"
                    nama-dari="mitra_min" nama-sampai="mitra_max" />
            </div>

            <div class="filter-footer">
                <button type="button" class="filter-reset" id="filterReset">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5" />
                    </svg>
                    Reset
                </button>
                <button type="button" class="btn-cancel" id="filterCancel">Cancel</button>
                <button type="submit" class="btn-apply">Terapkan</button>
            </div>
        </form>
    </aside>

    <x-admin.filter-js />
    <x-admin.export-js button="exportKoor" />

    <div class="modal-overlay" id="exportOverlay"></div>

    <div class="export-modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportTitle"
        aria-hidden="true">
        <form method="GET" action="{{ route('admin.koordinator.export') }}" id="exportForm">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            {!! $hiddenFilter !!}

            <h2 id="exportTitle" class="export-title">Export Data Koordinator</h2>
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

    <form id="hapusKoorForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
        <script>
            /* Popover detail koordinator */
            (function() {
                const pop = document.getElementById('koorPopover');
                const tbody = document.getElementById('koorTableBody');
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

                    let left = r.left - w - gap;
                    if (left < margin) left = r.right + gap;
                    left = Math.min(left, window.innerWidth - w - margin);
                    left = Math.max(left, margin);

                    let top = r.top;
                    top = Math.min(top, window.innerHeight - h - margin);
                    top = Math.max(top, margin);

                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }

                function buka(btn) {
                    if (aktif && aktif !== btn) aktif.setAttribute('aria-expanded', 'false');

                    document.getElementById('popNama').textContent = btn.dataset.nama;
                    document.getElementById('popUsername').textContent = btn.dataset.username;
                    document.getElementById('popMitra').textContent = btn.dataset.mitra + ' mitra';
                    document.getElementById('popPekerjaan').textContent = btn.dataset.pekerjaan + ' pekerjaan';

                    const wadah = document.getElementById('popTugas');
                    wadah.innerHTML = '';
                    let tugas = [];
                    try {
                        tugas = JSON.parse(btn.dataset.tugas || '[]');
                    } catch (e) {}

                    if (tugas.length === 0) {
                        const kosong = document.createElement('span');
                        kosong.className = 'pop-empty';
                        kosong.textContent = 'Belum ada pekerjaan';
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

                tbody.addEventListener('click', e => {
                    const btn = e.target.closest('.action-btn');
                    if (!btn) return;
                    e.stopPropagation();
                    (aktif === btn) ? tutup(): buka(btn);
                });

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

                /* Tombol edit & hapus */
                const hapusForm = document.getElementById('hapusKoorForm');

                document.getElementById('popEdit').addEventListener('click', () => {
                    const url = aktif?.dataset.editUrl;
                    if (url) window.location = url;
                });

                document.getElementById('popHapus').addEventListener('click', async () => {
                    const url = aktif?.dataset.deleteUrl;
                    const nama = aktif?.dataset.nama;

                    tutup();
                    if (!url) return;

                    const lanjut = await popup({
                        jenis: 'hapus',
                        judul: 'Hapus akun koordinator?',
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
        </script>
    @endpush
</x-admin.main>
