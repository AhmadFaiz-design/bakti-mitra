@php
    $persen = $ringkasan['total'] > 0 ? (int) round(($ringkasan['dibayar'] / $ringkasan['total']) * 100) : 0;

    $tugasDipilih = array_values(array_filter((array) request('tugas', [])));
    $honorMode = request('honor_mode', 'nominal');
    $adaHonor =
        $honorMode === 'nominal'
            ? request()->filled('honor_jumlah')
            : request()->filled('honor_dari') || request()->filled('honor_sampai');
    $jumlahFilter =
        (int) $adaHonor +
        (int) request()->filled('wilayah') +
        (int) request()->filled('koordinator') +
        count($tugasDipilih);

    // Input tersembunyi untuk meneruskan filter ke form pencarian, export, dan pembayaran massal
    $kunciFilter = ['honor_mode', 'honor_jumlah', 'honor_dari', 'honor_sampai', 'wilayah', 'koordinator'];
    $hiddenFilter = collect($kunciFilter)
        ->filter(fn($k) => request()->filled($k))
        ->values()
        ->map(fn($k) => '<input type="hidden" name="' . $k . '" value="' . e(request($k)) . '">')
        ->merge(collect($tugasDipilih)->map(fn($t) => '<input type="hidden" name="tugas[]" value="' . e($t) . '">'))
        ->implode('');
@endphp

<x-admin.main>
    <x-slot:titlex>Penugasan Mitra</x-slot:titlex>

    {{-- HEADER HALAMAN --}}
    <header class="page-heading">
        <div>
            <h1>Penugasan Mitra</h1>
            <p>Daftar tugas yang sudah diberikan dan status pembayaran honornya</p>
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
            <h2><svg>
                    <use href="#i-ring" />
                </svg> Progres Pembayaran</h2>
            <p class="num hijau">{{ $persen }}%</p>
            {{-- <div class="progress"><span style="width: {{ $persen }}%"></span></div> --}}
        </div>

        <div class="card stat">
            <h2><svg>
                    <use href="#i-cash" />
                </svg> Total Honor</h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['total'], 0, ',', '.') }}</p>
        </div>

        <div class="card stat">
            <h2><svg>
                    <use href="#i-cash" />
                </svg> Sudah Dibayar</h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['dibayar'], 0, ',', '.') }}</p>
        </div>

        <div class="card stat">
            <h2><svg>
                    <use href="#i-cash" />
                </svg> Belum Dibayar</h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['belum'], 0, ',', '.') }}</p>
        </div>
    </section>

    {{-- PENCARIAN, EXPORT, STATUS, FILTER --}}
    <section class="mitra-toolbar">
        <form method="GET" action="{{ route('admin.penugasan') }}" style="display: contents">
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenFilter !!}
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input id="searchPenugasan" type="search" name="q" value="{{ $q }}"
                    placeholder="Cari nama mitra, Sobat ID, atau uraian pekerjaan..." autocomplete="off">
            </label>
        </form>

        <button type="button" class="outline-btn" id="exportPenugasan">
            <svg>
                <use href="#i-download" />
            </svg>
            Export
        </button>

        <div class="status-switch" aria-label="Filter status pembayaran">
            @foreach (['semua' => 'Semua', 'belum' => 'Belum Dibayar', 'lunas' => 'Sudah Dibayar'] as $kode => $label)
                <button type="button" class="{{ $status === $kode ? 'active' : '' }}"
                    aria-pressed="{{ $status === $kode ? 'true' : 'false' }}"
                    onclick="window.location='{{ request()->fullUrlWithQuery(['status' => $kode, 'page' => null]) }}'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

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

    {{-- TABEL PENUGASAN --}}
    <section class="table-card">
        <div class="table-heading">
            <h2><svg>
                    <use href="#i-list" />
                </svg> List Penugasan</h2>

            @if (Route::has('admin.penugasan.buat'))
                <a href="{{ route('admin.penugasan.buat') }}" class="primary-btn" style="text-decoration:none">
                    <svg>
                        <use href="#i-plus" />
                    </svg>
                    Beri Tugas
                </a>
            @endif
        </div>

        {{-- Bar aksi massal: muncul saat ada baris yang dipilih --}}
        <div class="bulk-bar" id="bulkBar" hidden>
            <div class="bulk-info">
                <strong><span id="bulkCount">0</span> tugas dipilih</strong>
                <span id="bulkHint" hidden>
                    Semua tugas di halaman ini dipilih.
                    <button type="button" class="bulk-link" id="bulkPilihHasil">
                        Pilih semua {{ $daftar->total() }} tugas hasil filter
                    </button>
                </span>
                <span id="bulkHasilOn" hidden>
                    Seluruh {{ $daftar->total() }} tugas hasil filter dipilih.
                    <button type="button" class="bulk-link" id="bulkBatalHasil">Batalkan</button>
                </span>
            </div>
            <div class="bulk-actions">
                <button type="button" class="primary-btn" data-aksi="bayar">Tandai Dibayar</button>
                <button type="button" class="outline-btn" data-aksi="batal">Tandai Belum Dibayar</button>
                <button type="button" class="outline-btn" id="bulkReset">Batal pilih</button>
            </div>
        </div>

        <div class="table-scroll">
            <table class="mitra-table penugasan-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="check-mitra" id="selectAll"
                                aria-label="Pilih semua tugas di halaman ini">
                        </th>
                        <th>Profile</th>
                        <th>Nama</th>
                        <th>Uraian Pekerjaan</th>
                        <th>Koordinator</th>
                        <th>Periode</th>
                        <th>Volume</th>
                        <th>Honor</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody id="penugasanBody">
                    @forelse ($daftar as $p)
                        @php
                            $user = $p->kontrak?->mitra?->user;
                            $nama = $user?->name ?? '-';
                            $dibayar = (bool) $p->honor_dibayar;
                            $mulai = $p->tanggal_mulai
                                ? \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/Y')
                                : '-';
                            $selesai = $p->tanggal_selesai
                                ? \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/Y')
                                : '-';
                            $volume = rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',');
                            $diverifikasi = (bool) ($p->kontrak?->cek_kontrak || $p->kontrak?->cek_spj);

                            $info = [
                                'nama' => $nama,
                                'sobat' => $user?->username ?? '-',
                                'uraian' => $p->uraian_pekerjaan ?? '-',
                                'koordinator' => $p->koordinator?->name ?? '-',
                                'periode' => $mulai . ' s.d. ' . $selesai,
                                'honor' => 'Rp ' . number_format($p->jumlah ?? 0, 0, ',', '.'),
                                'dibayar' => $dibayar,
                                'waktu' => $dibayar ? $p->dibayar_pada?->format('d/m/Y H:i') ?? '' : '',
                                'bayarUrl' => route('admin.penugasan.bayar', $p),
                                'editUrl' => Route::has('admin.penugasan.edit')
                                    ? route('admin.penugasan.edit', $p)
                                    : '',
                                'hapusUrl' => Route::has('admin.penugasan.hapus')
                                    ? route('admin.penugasan.hapus', $p)
                                    : '',
                                'terkunci' => $dibayar || $diverifikasi,
                                'alasan' => $dibayar
                                    ? 'Tidak bisa diubah atau dihapus karena honor sudah dibayar. Batalkan pembayaran dulu.'
                                    : ($diverifikasi
                                        ? 'Tidak bisa diubah atau dihapus karena kontrak sudah diverifikasi.'
                                        : ''),
                            ];
                        @endphp

                        <tr>
                            <td>
                                <input type="checkbox" class="check-mitra row-check" value="{{ $p->id }}"
                                    aria-label="Pilih tugas {{ $nama }}">
                            </td>

                            <td>
                                <div class="profile-avatar">{{ strtoupper(mb_substr($nama, 0, 1)) }}</div>
                            </td>

                            <td>
                                <span class="profile-name">{{ $nama }}</span>
                                @if ($user)
                                    <span class="cell-sub">{{ $user->username }}</span>
                                @endif
                            </td>

                            <td class="cell-wrap">
                                {{ $p->uraian_pekerjaan }}
                                @if ($p->ro)
                                    <span class="cell-sub">RO: {{ $p->ro }}</span>
                                @endif
                            </td>

                            <td>{{ $p->koordinator?->name ?? '-' }}</td>

                            <td class="id-cell">
                                {{ $mulai }}
                                <span class="cell-sub">s.d. {{ $selesai }}</span>
                            </td>

                            <td class="id-cell">{{ $volume }} {{ $p->satuan }}</td>

                            <td class="honor-cell">Rp {{ number_format($p->jumlah ?? 0, 0, ',', '.') }}</td>

                            <td>
                                @if ($dibayar)
                                    <span class="status-badge active">Sudah Dibayar</span>
                                @else
                                    <span class="status-badge pending">Belum Dibayar</span>
                                @endif
                            </td>

                            <td class="action-cell">
                                <button type="button" class="action-btn" aria-label="Aksi {{ $nama }}"
                                    aria-haspopup="true" aria-expanded="false" data-info="{{ json_encode($info) }}">
                                    &#8942;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center;padding:24px">
                                @if ($q !== '' || $status !== 'semua' || $jumlahFilter > 0)
                                    Tidak ada tugas yang cocok dengan pencarian atau filter.
                                @else
                                    Belum ada tugas yang diberikan.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>
                @if ($daftar->total() > 0)
                    Menampilkan {{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}
                    dari {{ $daftar->total() }} tugas
                @else
                    Tidak ada data
                @endif
            </span>

            @if ($daftar->hasPages())
                <span style="display:flex;gap:8px;align-items:center">
                    @unless ($daftar->onFirstPage())
                        <a class="outline-btn" style="text-decoration:none" href="{{ $daftar->url(1) }}"
                            aria-label="Halaman pertama" title="Halaman pertama">&lt;&lt;</a>
                        <a class="outline-btn" style="text-decoration:none" href="{{ $daftar->previousPageUrl() }}"
                            aria-label="Halaman sebelumnya" title="Halaman sebelumnya">&lt;</a>
                    @endunless

                    <span>Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }}</span>

                    @if ($daftar->hasMorePages())
                        <a class="outline-btn" style="text-decoration:none" href="{{ $daftar->nextPageUrl() }}"
                            aria-label="Halaman berikutnya" title="Halaman berikutnya">&gt;</a>
                        <a class="outline-btn" style="text-decoration:none"
                            href="{{ $daftar->url($daftar->lastPage()) }}" aria-label="Halaman terakhir"
                            title="Halaman terakhir">&gt;&gt;</a>
                    @endif
                </span>
            @endif
        </div>
    </section>

    {{-- POPOVER AKSI (satu untuk semua baris) --}}
    <div class="mitra-popover" id="penPopover" role="dialog" aria-label="Detail penugasan">
        <div class="pop-field">
            <span class="pop-label">Mitra</span>
            <strong class="pop-name" id="popNama"></strong>
        </div>

        <div class="pop-row">
            <div class="pop-field">
                <span class="pop-label">Sobat ID</span>
                <strong class="pop-value" id="popSobat"></strong>
            </div>
            <div class="pop-field">
                <span class="pop-label">Honor</span>
                <strong class="pop-value" id="popHonor"></strong>
            </div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Uraian Pekerjaan</span>
            <div class="pop-text" id="popUraian"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Koordinator</span>
            <div class="pop-text" id="popKoordinator"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Periode</span>
            <div class="pop-text" id="popPeriode"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Pembayaran</span>
            <label class="pop-check">
                <input type="checkbox" id="popBayar">
                <span id="popBayarTeks">Tandai honor sudah dibayar</span>
            </label>
            <small class="pop-sub" id="popBayarWaktu"></small>
        </div>

        <div class="pop-field">
            <span class="pop-label">Aksi</span>
            <div class="pop-actions">
                <button type="button" class="pop-btn pop-btn-del" id="popHapus">
                    Hapus Tugas
                    <svg viewBox="0 0 24 24">
                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                    </svg>
                </button>
                <button type="button" class="pop-btn pop-btn-edit" id="popEdit">
                    Edit Tugas
                    <svg viewBox="0 0 24 24">
                        <path d="M4 20h4L19 9l-4-4L4 16v4zM13.5 6.5l4 4" />
                    </svg>
                </button>
            </div>

            <div class="pop-warning" id="popKunci" role="alert" hidden>
                <svg viewBox="0 0 24 24">
                    <path d="M12 3l10 18H2L12 3zM12 10v4M12 17.5v.01" />
                </svg>
                <span id="popKunciTeks"></span>
            </div>
        </div>
    </div>

    {{-- PANEL FILTER --}}
    <div class="filter-overlay" id="filterOverlay"></div>

    <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Penugasan" aria-hidden="true">
        <form method="GET" action="{{ route('admin.penugasan') }}" id="filterForm" class="filter-form">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">

            <div class="filter-body">
                <h2 class="filter-title">Filter Penugasan</h2>

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

                        <div class="honor-fields" id="honorNominal">
                            <span class="filter-sub">Jumlah</span>
                            <div class="rupiah-input">
                                <span>Rp</span>
                                <input type="text" inputmode="numeric" name="honor_jumlah" data-rupiah
                                    value="{{ request('honor_jumlah') }}" placeholder="100.000">
                            </div>
                        </div>

                        <div class="honor-fields" id="honorRentang">
                            <div class="honor-two">
                                <div>
                                    <span class="filter-sub">Dari</span>
                                    <div class="rupiah-input">
                                        <span>Rp</span>
                                        <input type="text" inputmode="numeric" name="honor_dari" data-rupiah
                                            value="{{ request('honor_dari') }}" placeholder="100.000">
                                    </div>
                                </div>
                                <div>
                                    <span class="filter-sub">Sampai</span>
                                    <div class="rupiah-input">
                                        <span>Rp</span>
                                        <input type="text" inputmode="numeric" name="honor_sampai" data-rupiah
                                            value="{{ request('honor_sampai') }}" placeholder="100.000">
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
                            @foreach ($daftarWilayah as $w)
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

                {{-- KOORDINATOR --}}
                <div class="filter-section">
                    <label class="filter-label" for="filterKoordinator">Koordinator</label>
                    <div class="select-wrap">
                        <select name="koordinator" id="filterKoordinator">
                            <option value="">Pilih Koordinator</option>
                            @foreach ($daftarKoordinator as $k)
                                <option value="{{ $k->id }}"
                                    {{ (string) request('koordinator') === (string) $k->id ? 'selected' : '' }}>
                                    {{ $k->name }}</option>
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
                            @foreach ($daftarTugas as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                        <svg>
                            <use href="#i-down" />
                        </svg>
                    </div>

                    <div class="filter-chips" id="tugasChips" data-selected='@json($tugasDipilih)'></div>
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
                <button type="submit" class="btn-apply">Terapkan</button>
            </div>
        </form>
    </aside>

    {{-- MODAL EXPORT --}}
    <div class="modal-overlay" id="exportOverlay"></div>

    <div class="export-modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportTitle"
        aria-hidden="true">
        <form method="GET" action="{{ route('admin.penugasan.export') }}" id="exportForm">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenFilter !!}

            <h2 id="exportTitle" class="export-title">Export Data Penugasan</h2>
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

    {{-- FORM TERSEMBUNYI: hapus satu tugas & pembayaran massal --}}
    <form id="hapusForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    <form id="bulkForm" method="POST" action="{{ route('admin.penugasan.bayar-massal') }}" style="display:none">
        @csrf
        <input type="hidden" name="aksi" id="bulkAksi">
        <input type="hidden" name="semua_hasil" id="bulkSemuaHasil" value="0">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="status" value="{{ $status }}">
        {!! $hiddenFilter !!}
        <div id="bulkIds"></div>
    </form>

    @push('scripts')
        <script>
            /* ===== Pilih baris + pembayaran massal ===== */
            (function() {
                const semua = document.getElementById('selectAll');
                const bar = document.getElementById('bulkBar');
                const hitung = document.getElementById('bulkCount');
                const hint = document.getElementById('bulkHint');
                const hasilOn = document.getElementById('bulkHasilOn');
                const form = document.getElementById('bulkForm');
                const TOTAL = @json($daftar->total());
                const ADA_HALAMAN_LAIN = @json($daftar->hasPages());
                let semuaHasil = false;

                const baris = () => [...document.querySelectorAll('#penugasanBody .row-check')];

                function segarkan() {
                    const cek = baris();
                    const n = cek.filter(c => c.checked).length;
                    if (n === 0) semuaHasil = false;

                    semua.checked = cek.length > 0 && n === cek.length;
                    semua.indeterminate = n > 0 && n < cek.length;

                    bar.hidden = n === 0;
                    hitung.textContent = semuaHasil ? TOTAL : n;
                    hint.hidden = !(ADA_HALAMAN_LAIN && n > 0 && n === cek.length && !semuaHasil);
                    hasilOn.hidden = !semuaHasil;
                    document.getElementById('bulkSemuaHasil').value = semuaHasil ? '1' : '0';
                }

                semua.addEventListener('change', () => {
                    semuaHasil = false;
                    baris().forEach(c => c.checked = semua.checked);
                    segarkan();
                });

                document.getElementById('penugasanBody').addEventListener('change', e => {
                    if (!e.target.classList.contains('row-check')) return;
                    semuaHasil = false;
                    segarkan();
                });

                document.getElementById('bulkPilihHasil')?.addEventListener('click', () => {
                    semuaHasil = true;
                    segarkan();
                });
                document.getElementById('bulkBatalHasil')?.addEventListener('click', () => {
                    semuaHasil = false;
                    segarkan();
                });
                document.getElementById('bulkReset').addEventListener('click', () => {
                    semuaHasil = false;
                    baris().forEach(c => c.checked = false);
                    segarkan();
                });

                bar.querySelectorAll('[data-aksi]').forEach(tombol => {
                    tombol.addEventListener('click', () => {
                        const aksi = tombol.dataset.aksi;
                        const dipilih = baris().filter(c => c.checked);
                        const jumlah = semuaHasil ? TOTAL : dipilih.length;
                        if (jumlah === 0) return;

                        const teks = aksi === 'bayar' ?
                            'Tandai ' + jumlah + ' tugas sebagai SUDAH dibayar?' :
                            'Tandai ' + jumlah + ' tugas sebagai BELUM dibayar?';
                        if (!confirm(teks)) return;

                        document.getElementById('bulkAksi').value = aksi;
                        const wadah = document.getElementById('bulkIds');
                        wadah.innerHTML = '';
                        if (!semuaHasil) {
                            dipilih.forEach(c => {
                                const i = document.createElement('input');
                                i.type = 'hidden';
                                i.name = 'ids[]';
                                i.value = c.value;
                                wadah.appendChild(i);
                            });
                        }
                        form.submit();
                    });
                });

                segarkan();
            })();

            /* ===== Popover aksi penugasan ===== */
            (function() {
                const pop = document.getElementById('penPopover');
                const tbody = document.getElementById('penugasanBody');
                const hapusForm = document.getElementById('hapusForm');
                const popBayar = document.getElementById('popBayar');
                const popEdit = document.getElementById('popEdit');
                const popHapus = document.getElementById('popHapus');
                const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
                let aktif = null;
                let info = null;

                const pesan = (jenis, judul, teks) =>
                    typeof popup === 'function' ? popup({
                        jenis,
                        judul,
                        pesan: teks
                    }) : alert(teks);

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
                    left = Math.max(margin, Math.min(left, window.innerWidth - w - margin));

                    let top = Math.max(margin, Math.min(r.top, window.innerHeight - h - margin));

                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }

                function buka(btn) {
                    if (aktif && aktif !== btn) aktif.setAttribute('aria-expanded', 'false');

                    try {
                        info = JSON.parse(btn.dataset.info);
                    } catch (e) {
                        return;
                    }

                    document.getElementById('popNama').textContent = info.nama;
                    document.getElementById('popSobat').textContent = info.sobat;
                    document.getElementById('popHonor').textContent = info.honor;
                    document.getElementById('popUraian').textContent = info.uraian;
                    document.getElementById('popKoordinator').textContent = info.koordinator;
                    document.getElementById('popPeriode').textContent = info.periode;

                    popBayar.checked = !!info.dibayar;
                    popBayar.disabled = false;
                    document.getElementById('popBayarTeks').textContent =
                        info.dibayar ? 'Honor sudah dibayar' : 'Tandai honor sudah dibayar';
                    document.getElementById('popBayarWaktu').textContent =
                        info.waktu ? 'Dibayar pada ' + info.waktu : '';

                    popEdit.style.display = info.editUrl ? '' : 'none';
                    popHapus.style.display = info.hapusUrl ? '' : 'none';

                    const terkunci = !!info.terkunci;
                    popEdit.disabled = terkunci;
                    popHapus.disabled = terkunci;
                    document.getElementById('popKunciTeks').textContent = info.alasan || '';
                    document.getElementById('popKunci').hidden = !terkunci;

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

                /* Ceklist pembayaran satu tugas */
                popBayar.addEventListener('change', async () => {
                    if (!info) return;
                    const dibayar = popBayar.checked;
                    popBayar.disabled = true;

                    try {
                        const res = await fetch(info.bayarUrl, {
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
                        window.location.reload(); // kartu ringkasan & daftar ikut diperbarui
                    } catch (e) {
                        popBayar.checked = !dibayar;
                        popBayar.disabled = false;
                        pesan('gagal', 'Tidak berhasil', 'Status pembayaran gagal disimpan. Coba lagi.');
                    }
                });

                popEdit.addEventListener('click', () => {
                    if (info?.editUrl) window.location = info.editUrl;
                });

                popHapus.addEventListener('click', async () => {
                    const url = info?.hapusUrl;
                    const nama = info?.nama;
                    const uraian = info?.uraian;
                    tutup();
                    if (!url) return;

                    const teks = 'Tugas "' + uraian + '" untuk ' + nama +
                        ' akan dihapus permanen dan tidak bisa dikembalikan.';
                    const lanjut = typeof popup === 'function' ?
                        await popup({
                            jenis: 'hapus',
                            judul: 'Hapus tugas?',
                            pesan: teks,
                            tombolOk: 'Ya, hapus',
                            tombolBatal: 'Batal'
                        }) :
                        confirm(teks);

                    if (lanjut) {
                        hapusForm.action = url;
                        hapusForm.submit();
                    }
                });
            })();

            /* ===== Filter ===== */
            (function() {
                const panel = document.getElementById('filterPanel');
                const overlay = document.getElementById('filterOverlay');
                const toggle = document.getElementById('filterToggle');
                const form = document.getElementById('filterForm');
                const chips = document.getElementById('tugasChips');
                const selTugas = document.getElementById('filterTugas');

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
                    const nominal = form.querySelector('input[name="honor_mode"]:checked').value === 'nominal';
                    boxNominal.hidden = !nominal;
                    boxRentang.hidden = nominal;
                    boxNominal.querySelectorAll('input').forEach(i => i.disabled = !nominal);
                    boxRentang.querySelectorAll('input').forEach(i => i.disabled = nominal);
                }
                form.querySelectorAll('input[name="honor_mode"]').forEach(r => r.addEventListener('change', aturMode));
                aturMode();

                /* Format rupiah */
                const formatRp = v => v.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                form.querySelectorAll('[data-rupiah]').forEach(input => {
                    input.value = formatRp(input.value);
                    input.addEventListener('input', () => input.value = formatRp(input.value));
                });
                form.addEventListener('submit', () => {
                    form.querySelectorAll('[data-rupiah]').forEach(i => i.value = i.value.replace(/\./g, ''));
                });

                /* Tugas: dropdown -> chip */
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

                try {
                    JSON.parse(chips.dataset.selected || '[]').forEach(tambahChip);
                } catch (e) {}

                /* Reset */
                document.getElementById('filterReset').addEventListener('click', () => {
                    form.querySelectorAll('[data-rupiah]').forEach(i => i.value = '');
                    form.querySelector('input[name="honor_mode"][value="nominal"]').checked = true;
                    document.getElementById('filterWilayah').value = '';
                    document.getElementById('filterKoordinator').value = '';
                    chips.innerHTML = '';
                    selTugas.value = '';
                    aturMode();
                    form.submit();
                });
            })();

            /* ===== Export ===== */
            (function() {
                const modal = document.getElementById('exportModal');
                const overlay = document.getElementById('exportOverlay');
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

                document.getElementById('exportPenugasan').addEventListener('click', buka);
                overlay.addEventListener('click', tutup);
                document.getElementById('exportCancel').addEventListener('click', tutup);
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') tutup();
                });
                form.addEventListener('submit', () => setTimeout(tutup, 400));
            })();
        </script>
    @endpush
</x-admin.main>
