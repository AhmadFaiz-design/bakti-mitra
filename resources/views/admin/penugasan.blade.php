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

    <style>
        .aksi-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .assign-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #d5e0da;
            background: #f4faf6;
            color: #166534;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s, border-color .15s;
        }

        .assign-btn:hover {
            background: #e6f4ea;
            border-color: #9dc8ad;
        }

        .assign-btn svg {
            width: 15px;
            height: 15px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .status-badge.empty {
            background: #f1f5f3;
            color: #5f7068;
        }

        .status-badge.partial {
            background: #e8f1ff;
            color: #1d4ed8;
        }
    </style>

    {{-- HEADER HALAMAN --}}
    <header class="page-heading">
        <div>
            <h1>Penugasan Mitra</h1>
            <p>Daftar tugas, petugas yang ditugaskan, dan status pembayaran honornya</p>
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
                    placeholder="Cari uraian pekerjaan, RO, koordinator, atau nama mitra..." autocomplete="off">
            </label>
        </form>

        <button type="button" class="outline-btn" id="exportPenugasan">
            <svg>
                <use href="#i-download" />
            </svg>
            Export
        </button>

        <x-admin.status-dropdown :options="[
            'semua' => 'Semua',
            'kosong' => 'Belum Ada Petugas',
            'belum' => 'Belum Dibayar',
            'lunas' => 'Sudah Dibayar',
        ]" :current="$status" label="Status" />

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
                    Tambah Tugas
                </a>
            @endif
        </div>

        {{-- Bar aksi massal --}}
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
                <button type="button" class="danger-btn" id="bulkHapus">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                    </svg>
                    Hapus
                </button>
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
                        <th>Uraian Pekerjaan</th>
                        <th>Koordinator</th>
                        <th>Periode</th>
                        <th>Petugas (Mitra)</th>
                        <th>Tarif / Total Vol</th>
                        <th>Total Honor</th>
                        <th>Status</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>

                <tbody id="penugasanBody">
                    @forelse ($daftar as $t)
                        @php
                            $jumlahPetugas = (int) $t->pekerjaan_count;
                            $jumlahDibayar = (int) $t->pekerjaan_dibayar_count;
                            $totalVolume = (float) ($t->total_volume ?? 0);
                            $totalHonor = (int) ($t->total_honor ?? 0);

                            $mulai = $t->tanggal_mulai
                                ? \Carbon\Carbon::parse($t->tanggal_mulai)->format('d/m/Y')
                                : '-';
                            $selesai = $t->tanggal_selesai
                                ? \Carbon\Carbon::parse($t->tanggal_selesai)->format('d/m/Y')
                                : '-';
                            $volText = rtrim(rtrim(number_format($totalVolume, 2, ',', '.'), '0'), ',');

                            $namaMitra = $t->pekerjaan
                                ->map(fn($p) => $p->kontrak?->mitra?->user?->name)
                                ->filter()
                                ->values();

                            $adaDibayar = $jumlahDibayar > 0;
                            $semuaDibayar = $jumlahPetugas > 0 && $jumlahDibayar === $jumlahPetugas;
                            $adaDiverifikasi = $t->pekerjaan->contains(
                                fn($p) => (bool) ($p->kontrak?->cek_kontrak || $p->kontrak?->cek_spj),
                            );
                            $terkunci = $adaDibayar || $adaDiverifikasi;

                            $alasan = $adaDibayar
                                ? 'Tugas tidak bisa diubah atau dihapus karena sudah ada petugas yang honornya dibayar.'
                                : ($adaDiverifikasi
                                    ? 'Tugas tidak bisa diubah atau dihapus karena kontrak salah satu petugas sudah diverifikasi.'
                                    : '');

                            $info = [
                                'uraian' => $t->uraian_pekerjaan ?? '-',
                                'ro' => $t->ro ?: '-',
                                'koordinator' => $t->koordinator?->name ?? '-',
                                'periode' => $mulai . ' s.d. ' . $selesai,
                                'jumlahPetugas' => $jumlahPetugas,
                                'petugasTeks' => $jumlahPetugas > 0
                                    ? $jumlahPetugas . ' Mitra (Total Vol: ' . ($volText ?: '0') . ' ' . $t->satuan . ')'
                                    : 'Belum ada petugas',
                                'honor' => 'Rp ' . number_format($totalHonor, 0, ',', '.'),
                                'dibayar' => $semuaDibayar,
                                'sebagian' => $adaDibayar && !$semuaDibayar,
                                'bayarUrl' => route('admin.penugasan.bayar', $t),
                                'assignUrl' => route('admin.penugasan.assign', $t),
                                'editUrl' => Route::has('admin.penugasan.edit')
                                    ? route('admin.penugasan.edit', $t)
                                    : '',
                                'hapusUrl' => Route::has('admin.penugasan.hapus')
                                    ? route('admin.penugasan.hapus', $t)
                                    : '',
                                'terkunci' => $terkunci,
                                'alasan' => $alasan,
                            ];
                        @endphp

                        <tr>
                            <td>
                                <input type="checkbox" class="check-mitra row-check" value="{{ $t->id }}"
                                    aria-label="Pilih tugas {{ $t->uraian_pekerjaan }}">
                            </td>

                            <td class="cell-wrap">
                                <span class="profile-name">{{ $t->uraian_pekerjaan }}</span>
                                @if ($t->ro)
                                    <span class="cell-sub">RO: {{ $t->ro }}</span>
                                @endif
                            </td>

                            <td>
                                {{ $t->koordinator?->name ?? '-' }}
                                @if ($t->koordinator?->username)
                                    <span class="cell-sub">{{ $t->koordinator->username }}</span>
                                @endif
                            </td>

                            <td class="id-cell">
                                {{ $mulai }}
                                <span class="cell-sub">s.d. {{ $selesai }}</span>
                            </td>

                            <td>
                                @if ($jumlahPetugas === 0)
                                    <span class="status-badge empty">Belum ada petugas</span>
                                @else
                                    <span class="profile-name">{{ $jumlahPetugas }} Mitra</span>
                                    <span class="cell-sub">
                                        {{ $namaMitra->take(2)->implode(', ') }}@if ($jumlahPetugas > 2)
                                            +{{ $jumlahPetugas - 2 }} lainnya
                                        @endif
                                    </span>
                                @endif
                            </td>

                            <td class="id-cell">
                                Rp {{ number_format($t->harga_satuan ?? 0, 0, ',', '.') }} / {{ $t->satuan }}
                                <span class="cell-sub">
                                    Total Vol: {{ $jumlahPetugas > 0 ? ($volText ?: '0') . ' ' . $t->satuan : '-' }}
                                </span>
                            </td>

                            <td class="honor-cell">Rp {{ number_format($totalHonor, 0, ',', '.') }}</td>

                            <td>
                                @if ($jumlahPetugas === 0)
                                    <span class="status-badge empty">Belum Diassign</span>
                                @elseif ($semuaDibayar)
                                    <span class="status-badge active">Sudah Dibayar</span>
                                @elseif ($adaDibayar)
                                    <span class="status-badge partial">Sebagian ({{ $jumlahDibayar }}/{{ $jumlahPetugas }})</span>
                                @else
                                    <span class="status-badge pending">Belum Dibayar</span>
                                @endif
                            </td>

                            <td class="action-cell">
                                <div class="aksi-wrap">
                                    <a href="{{ route('admin.penugasan.assign', $t) }}" class="assign-btn"
                                        title="Assign petugas dan volume untuk tugas ini">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2" />
                                            <path d="M19 8v6M16 11h6" />
                                        </svg>
                                        Assign Petugas
                                    </a>
                                    <button type="button" class="action-btn"
                                        aria-label="Aksi {{ $t->uraian_pekerjaan }}" aria-haspopup="true"
                                        aria-expanded="false" data-info="{{ json_encode($info) }}">
                                        &#8942;
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:24px">
                                @if ($q !== '' || $status !== 'semua' || $jumlahFilter > 0)
                                    Tidak ada tugas yang cocok dengan pencarian atau filter.
                                @else
                                    Belum ada tugas yang dibuat.
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

    {{-- POPOVER AKSI --}}
    <div class="mitra-popover" id="penPopover" role="dialog" aria-label="Detail penugasan">
        <div class="pop-field">
            <span class="pop-label">Uraian Pekerjaan</span>
            <strong class="pop-name" id="popUraian"></strong>
        </div>

        <div class="pop-row">
            <div class="pop-field">
                <span class="pop-label">RO</span>
                <strong class="pop-value" id="popRo"></strong>
            </div>
            <div class="pop-field">
                <span class="pop-label">Total Honor</span>
                <strong class="pop-value" id="popHonor"></strong>
            </div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Koordinator</span>
            <div class="pop-text" id="popKoordinator"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Petugas</span>
            <div class="pop-text" id="popPetugas"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Periode</span>
            <div class="pop-text" id="popPeriode"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Pembayaran Semua Petugas</span>
            <label class="pop-check">
                <input type="checkbox" id="popBayar">
                <span id="popBayarTeks">Tandai semua honor sudah dibayar</span>
            </label>
            <small class="pop-sub" id="popBayarSub">Untuk bayar per mitra, buka halaman Assign Petugas.</small>
        </div>

        <div class="pop-field">
            <span class="pop-label">Aksi</span>
            <div class="pop-actions" style="flex-wrap:wrap">
                <button type="button" class="pop-btn pop-btn-edit" id="popAssign" style="flex:1 1 100%">
                    Assign / Kelola Petugas
                </button>
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
        @php
            $parseRp = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
            $modeRentang = request('honor_mode') === 'rentang';
            $honorDari = $modeRentang && request()->filled('honor_dari') ? $parseRp(request('honor_dari')) : 0;
            $honorSampai = $modeRentang && request()->filled('honor_sampai') ? $parseRp(request('honor_sampai')) : null;
            $honorBatas = max((int) ($honorMaks ?? 2500000), $honorDari, (int) $honorSampai);
            $honorSampai ??= $honorBatas;
        @endphp

        <form method="GET" action="{{ route('admin.penugasan') }}" id="filterForm" class="filter-form">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="honor_mode" value="rentang" id="honorMode">

            <div class="filter-body">
                <h2 class="filter-title">Filter Penugasan</h2>

                <x-admin.filter-wilayah :opsi="$daftarWilayah" :dipilih="request('wilayah')" />

                <x-admin.filter-tugas judul="Koordinator" placeholder="Cari Koordinator" nama="koordinator"
                    :banyak="false" :opsi="collect($daftarKoordinator)->map(fn($k) => ['v' => $k->id, 't' => $k->name])"
                    :dipilih="request()->filled('koordinator') ? [request('koordinator')] : []" />

                <x-admin.filter-tugas judul="Tugas" placeholder="Cari Tugas" :opsi="$daftarTugas" :dipilih="$tugasDipilih" />

                <x-admin.filter-range judul="Rentang Total Honor" :max="$honorBatas" :dari="$honorDari" :sampai="$honorSampai"
                    nama-dari="honor_dari" nama-sampai="honor_sampai" :step="10000" :rupiah="true"
                    mode-id="honorMode" />
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

    {{-- MODAL EXPORT --}}
    <div class="modal-overlay" id="exportOverlay"></div>

    <div class="export-modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportTitle"
        aria-hidden="true">
        <form method="GET" action="{{ route('admin.penugasan.export') }}" id="exportForm">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenFilter !!}

            <h2 id="exportTitle" class="export-title">Export Data Penugasan</h2>
            <p class="export-sub">Data rincian honor mitra berdasarkan tugas hasil filter</p>

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

    {{-- FORM TERSEMBUNYI --}}
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

    <form id="bulkHapusForm" method="POST" action="{{ route('admin.penugasan.hapus-massal') }}"
        style="display:none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="semua_hasil" id="bulkHapusSemuaHasil" value="0">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="status" value="{{ $status }}">
        {!! $hiddenFilter !!}
        <div id="bulkHapusIds"></div>
    </form>

    @push('scripts')
        <script>
            /* ===== Pilih baris + aksi massal ===== */
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

                document.getElementById('bulkHapus').addEventListener('click', async () => {
                    const dipilih = baris().filter(c => c.checked);
                    const jumlah = semuaHasil ? TOTAL : dipilih.length;
                    if (jumlah === 0) return;

                    const teks = jumlah + ' tugas akan dihapus permanen beserta daftar petugasnya. ' +
                        'Tugas yang memiliki honor sudah dibayar atau kontrak terverifikasi tidak akan dihapus.';

                    const lanjut = typeof popup === 'function' ?
                        await popup({
                            jenis: 'hapus',
                            judul: 'Hapus ' + jumlah + ' tugas?',
                            pesan: teks,
                            tombolOk: 'Ya, hapus',
                            tombolBatal: 'Batal'
                        }) :
                        confirm(teks);

                    if (!lanjut) return;

                    document.getElementById('bulkHapusSemuaHasil').value = semuaHasil ? '1' : '0';
                    const wadah = document.getElementById('bulkHapusIds');
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
                    document.getElementById('bulkHapusForm').submit();
                });

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
                            'Tandai semua petugas pada ' + jumlah + ' tugas sebagai SUDAH dibayar?' :
                            'Tandai semua petugas pada ' + jumlah + ' tugas sebagai BELUM dibayar?';
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
                const popAssign = document.getElementById('popAssign');
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

                    document.getElementById('popUraian').textContent = info.uraian;
                    document.getElementById('popRo').textContent = info.ro;
                    document.getElementById('popHonor').textContent = info.honor;
                    document.getElementById('popKoordinator').textContent = info.koordinator;
                    document.getElementById('popPetugas').textContent = info.petugasTeks;
                    document.getElementById('popPeriode').textContent = info.periode;

                    const adaPetugas = Number(info.jumlahPetugas) > 0;
                    popBayar.checked = !!info.dibayar;
                    popBayar.disabled = !adaPetugas;
                    document.getElementById('popBayarTeks').textContent = !adaPetugas
                        ? 'Belum ada petugas untuk dibayar'
                        : (info.dibayar ? 'Semua honor sudah dibayar' : 'Tandai semua honor sudah dibayar');

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
                    (aktif === btn) ? tutup() : buka(btn);
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
                        window.location.reload();
                    } catch (e) {
                        popBayar.checked = !dibayar;
                        popBayar.disabled = false;
                        pesan('gagal', 'Tidak berhasil', 'Status pembayaran gagal disimpan. Coba lagi.');
                    }
                });

                popAssign.addEventListener('click', () => {
                    if (info?.assignUrl) window.location = info.assignUrl;
                });

                popEdit.addEventListener('click', () => {
                    if (info?.editUrl) window.location = info.editUrl;
                });

                popHapus.addEventListener('click', async () => {
                    const url = info?.hapusUrl;
                    const uraian = info?.uraian;
                    tutup();
                    if (!url) return;

                    const teks = 'Tugas "' + uraian +
                        '" beserta daftar petugasnya akan dihapus permanen dan tidak bisa dikembalikan.';
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
