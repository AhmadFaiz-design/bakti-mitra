@php
    $persen = $ringkasan['total'] > 0 ? (int) round(($ringkasan['dibayar'] / $ringkasan['total']) * 100) : 0;

    $tugasDipilih = array_values(array_filter((array) request('tugas', [])));

    $parseRp = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
    $honorDari = request()->filled('honor_dari') ? $parseRp(request('honor_dari')) : 0;
    $honorSampaiReq = request()->filled('honor_sampai') ? $parseRp(request('honor_sampai')) : null;
    $honorBatas = max((int) ($honorMaks ?? 2500000), $honorDari, (int) $honorSampaiReq);
    $honorSampai = $honorSampaiReq ?? $honorBatas;
    $adaHonor = $honorDari > 0 || ($honorSampaiReq !== null && $honorSampaiReq < $honorBatas);

    $jumlahFilter =
        (int) $adaHonor +
        (int) request()->filled('wilayah') +
        (int) ($bisaPilihKoordinator && request()->filled('koordinator')) +
        count($tugasDipilih);

    $kunciFilter = ['honor_mode', 'honor_jumlah', 'honor_dari', 'honor_sampai', 'wilayah'];
    if ($bisaPilihKoordinator) {
        $kunciFilter[] = 'koordinator';
    }

    $hiddenFilter = collect($kunciFilter)
        ->filter(fn($k) => request()->filled($k))
        ->values()
        ->map(fn($k) => '<input type="hidden" name="' . $k . '" value="' . e(request($k)) . '">')
        ->merge(collect($tugasDipilih)->map(fn($t) => '<input type="hidden" name="tugas[]" value="' . e($t) . '">'))
        ->implode('');

    $colspan = $bisaPilihKoordinator ? 9 : 8;
@endphp

<x-dynamic-component :component="$layout">
    <x-slot:titlex>Penugasan Mitra</x-slot:titlex>

    {{-- HEADER HALAMAN (tanpa subjudul) --}}
    <x-header :judul="$role === 'koordinator' ? 'Data Penugasan' : 'Penugasan Mitra'" />

    {{-- KARTU STATISTIK --}}
    <section class="stats-mitra stats-mitra-4">
        <div class="card hero">
            <h2>
                <svg>
                    <use href="#i-ring" />
                </svg>
                Progres Pembayaran
            </h2>
            <p class="num">{{ $persen }}%</p>
            <div class="progress"><span style="width: {{ $persen }}%"></span></div>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="6" width="18" height="12" rx="2.5" />
                    <path d="M3 10h18M7 14h4" />
                </svg>
                Total Honor
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['total'], 0, ',', '.') }}</p>
            <span class="badge {{ $tren['total']['kelas'] }}" title="Perbandingan bulan ini vs bulan lalu">
                {{ $tren['total']['teks'] }}
            </span>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24">
                    <path d="M13 6H5.5A2.5 2.5 0 0 0 3 8.5v7A2.5 2.5 0 0 0 5.5 18h13a2.5 2.5 0 0 0 2.5-2.5V12" />
                    <path d="M3 10h10M7 14h4M15.5 6.5l2 2 4-4" />
                </svg>
                Dibayar
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['dibayar'], 0, ',', '.') }}</p>
            <span class="badge {{ $tren['dibayar']['kelas'] }}" title="Perbandingan bulan ini vs bulan lalu">
                {{ $tren['dibayar']['teks'] }}
            </span>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="6" width="18" height="12" rx="2.5" />
                    <path d="M3 10h18M15 14h3" />
                </svg>
                Belum Dibayar
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['belum'], 0, ',', '.') }}</p>
            <span class="badge {{ $tren['belum']['kelas'] }}" title="Perbandingan bulan ini vs bulan lalu">
                {{ $tren['belum']['teks'] }}
            </span>
        </div>
    </section>

    {{-- PENCARIAN, STATUS, FILTER --}}
    <section class="mitra-toolbar">
        @if (request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        @endif
        <form method="GET" action="{{ route($routes['index']) }}" style="display: contents">
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenFilter !!}
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input id="searchPenugasan" type="search" name="q" value="{{ $q }}"
                    placeholder="Cari uraian pekerjaan, RO, atau nama mitra..." autocomplete="off">
            </label>
        </form>

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
            <h2>
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="16" rx="2.5" />
                    <path d="M3 10h18M9 4v16" />
                </svg>
                Tabel Tugas
            </h2>

            <div class="table-heading-actions">
                <button type="button" class="outline-btn" id="exportPenugasan">
                    <span>Export</span>
                    <svg viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <path d="M17 8l-5-5-5 5" />
                        <path d="M12 3v12" />
                    </svg>
                </button>

                <a href="{{ route($routes['buat']) }}" class="dark-pill-btn">
                    <span>Tambah Tugas</span>
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8.5v7M8.5 12h7" />
                    </svg>
                </a>
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
                        @if ($bisaPilihKoordinator)
                            <th>Koordinator</th>
                        @endif
                        <th>Periode</th>
                        <th>Petugas (Mitra)</th>
                        <th>Tarif / Total Vol</th>
                        <th>Total Honor</th>
                        <th>Status</th>
                        <th class="aksi-col">Aksi</th>
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
                                    ? 'Tugas tidak bisa diubah atau dihapus karena kontrak salah satu petugas pada tugas ini sudah diverifikasi.'
                                    : '');

                            $statusTeks =
                                $jumlahPetugas === 0
                                    ? 'Belum ada petugas'
                                    : ($semuaDibayar
                                        ? 'Sudah dibayar semua'
                                        : ($adaDibayar
                                            ? "Sebagian dibayar ($jumlahDibayar/$jumlahPetugas)"
                                            : 'Belum dibayar'));

                            $info = [
                                'uraian' => $t->uraian_pekerjaan ?? '-',
                                'ro' => $t->ro ?: '-',
                                'koordinator' => $t->koordinator?->name ?? '-',
                                'periode' => $mulai . ' s.d. ' . $selesai,
                                'jumlahPetugas' => $jumlahPetugas,
                                'petugasTeks' =>
                                    $jumlahPetugas > 0
                                        ? $jumlahPetugas .
                                            ' Mitra (Total Vol: ' .
                                            ($volText ?: '0') .
                                            ' ' .
                                            $t->satuan .
                                            ')'
                                        : 'Belum ada petugas',
                                'honor' => 'Rp ' . number_format($totalHonor, 0, ',', '.'),
                                'dibayar' => $semuaDibayar,
                                'statusTeks' => $statusTeks,
                                'bayarUrl' => $bisaKelolaBayar ? route($routes['bayar'], $t) : '',
                                'assignUrl' => route($routes['assign'], $t),
                                'editUrl' => route($routes['edit'], $t),
                                'hapusUrl' => route($routes['hapus'], $t),
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

                            @if ($bisaPilihKoordinator)
                                <td>
                                    {{ $t->koordinator?->name ?? '-' }}
                                    @if ($t->koordinator?->username)
                                        <span class="cell-sub">{{ $t->koordinator->username }}</span>
                                    @endif
                                </td>
                            @endif

                            <td class="id-cell">
                                {{ $mulai }}
                                <span class="cell-sub">s.d. {{ $selesai }}</span>
                            </td>

                            <td class="petugas-cell">
                                @if ($jumlahPetugas === 0)
                                    <span class="status-badge empty">Belum ada petugas</span>
                                @else
                                    <button type="button" class="mitra-cell-toggle" aria-expanded="false"
                                        title="Klik untuk melihat seluruh nama mitra">
                                        <span class="mitra-ringkas">
                                            <span class="mitra-head-row">
                                                <span class="profile-name">{{ $jumlahPetugas }} Mitra</span>
                                                <span class="mitra-arrow" aria-hidden="true">&#9662;</span>
                                            </span>
                                            <span class="cell-sub">
                                                {{ $namaMitra->take(2)->implode(', ') }}@if ($jumlahPetugas > 2)
                                                    +{{ $jumlahPetugas - 2 }} lainnya
                                                @endif
                                            </span>
                                        </span>

                                        <span class="mitra-lengkap" hidden>
                                            <span class="mitra-head-row">
                                                <span class="profile-name">{{ $jumlahPetugas }} Mitra</span>
                                                <span class="mitra-arrow" aria-hidden="true">&#9652;</span>
                                            </span>
                                            <span class="mitra-nama-list">
                                                @foreach ($namaMitra as $nm)
                                                    <span class="mitra-nama-item">{{ $nm }}</span>
                                                @endforeach
                                            </span>
                                        </span>
                                    </button>
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
                                    <span class="status-badge partial">Sebagian
                                        ({{ $jumlahDibayar }}/{{ $jumlahPetugas }})</span>
                                @else
                                    <span class="status-badge pending">Belum Dibayar</span>
                                @endif
                            </td>

                            <td class="action-cell">
                                <div class="aksi-wrap">
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
                                <td colspan="{{ $colspan }}" style="text-align:center;padding:24px">
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
                <div class="table-footer-left">
                    @if ($daftar->total() > 0)
                        <span>Menampilkan</span>
                        <x-admin.per-page :current="$perPage" />
                        <span>dari {{ $daftar->total() }} Penugasan</span>
                    @else
                        <span>Tidak ada data</span>
                    @endif
                </div>

                @if ($daftar->hasPages())
                    @php
                        $cur = $daftar->currentPage();
                        $last = $daftar->lastPage();
                        $pages = collect([1, $cur - 1, $cur, $cur + 1, $last])
                            ->filter(fn($p) => $p >= 1 && $p <= $last)
                            ->unique()
                            ->sort()
                            ->values();
                        $prev = null;
                    @endphp

                    <nav class="page-nav" aria-label="Navigasi halaman">
                        @if ($daftar->onFirstPage())
                            <span class="page-btn disabled" aria-disabled="true">&lsaquo;</span>
                        @else
                            <a class="page-btn" href="{{ $daftar->previousPageUrl() }}"
                                aria-label="Halaman sebelumnya">&lsaquo;</a>
                        @endif

                        @foreach ($pages as $p)
                            @if ($prev !== null && $p - $prev > 1)
                                <span class="page-dots">...</span>
                            @endif

                            @if ($p === $cur)
                                <span class="page-btn active" aria-current="page">{{ $p }}</span>
                            @else
                                <a class="page-btn" href="{{ $daftar->url($p) }}">{{ $p }}</a>
                            @endif

                            @php $prev = $p; @endphp
                        @endforeach

                        @if ($daftar->hasMorePages())
                            <a class="page-btn" href="{{ $daftar->nextPageUrl() }}"
                                aria-label="Halaman berikutnya">&rsaquo;</a>
                        @else
                            <span class="page-btn disabled" aria-disabled="true">&rsaquo;</span>
                        @endif
                    </nav>
                @endif
            </div>
        </section>

        {{-- POPOVER AKSI --}}
        <div class="mitra-popover" id="penPopover" role="dialog" aria-label="Detail penugasan">
            <div class="pop-field">
                <span class="pop-label">Uraian Pekerjaan</span>
                <strong class="pop-name" id="popUraian"></strong>
            </div>

            <div class="pop-field">
                <span class="pop-label">RO</span>
                <strong class="pop-value" id="popRo"></strong>
            </div>
            <div class="pop-field">
                <span class="pop-label">Total Honor</span>
                <strong class="pop-value" id="popHonor"></strong>
            </div>

            @if ($bisaPilihKoordinator)
                <div class="pop-field">
                    <span class="pop-label">Koordinator</span>
                    <div class="pop-text" id="popKoordinator"></div>
                </div>
            @endif

            <div class="pop-field">
                <span class="pop-label">Petugas</span>
                <div class="pop-text" id="popPetugas"></div>
            </div>

            <div class="pop-field">
                <span class="pop-label">Periode</span>
                <div class="pop-text" id="popPeriode"></div>
            </div>

            <div class="pop-field">
                <span class="pop-label">Pembayaran</span>
                @if ($bisaKelolaBayar)
                    <label class="pop-check">
                        <input type="checkbox" id="popBayar">
                        <span id="popBayarTeks">Tandai semua honor sudah dibayar</span>
                    </label>
                    <small class="pop-sub">Untuk bayar per mitra, buka halaman Assign Petugas.</small>
                @else
                    <div class="pop-text" id="popBayarReadonly"></div>
                @endif
            </div>

            <div class="pop-field">
                <span class="pop-label">Aksi</span>
                <div class="pop-actions pop-actions-wrap">
                    <button type="button" class="pop-btn pop-btn-edit pop-btn-full" id="popAssign">
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
            <form method="GET" action="{{ route($routes['index']) }}" id="filterForm" class="filter-form">
                @if (request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                @endif
                <input type="hidden" name="q" value="{{ $q }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="honor_mode" value="rentang" id="honorMode">

                <div class="filter-body">
                    <h2 class="filter-title">Filter Penugasan</h2>

                    <x-admin.filter-wilayah :opsi="$daftarWilayah" :dipilih="request('wilayah')" />

                    @if ($bisaPilihKoordinator)
                        <x-admin.filter-tugas judul="Koordinator" placeholder="Cari Koordinator" nama="koordinator"
                            :banyak="false" :opsi="collect($daftarKoordinator)->map(fn($k) => ['v' => $k->id, 't' => $k->name])" :dipilih="request()->filled('koordinator') ? [request('koordinator')] : []" />
                    @endif

                    <x-admin.filter-tugas judul="Tugas" placeholder="Cari Tugas" :opsi="$daftarTugas" :dipilih="$tugasDipilih" />

                    <x-admin.filter-range judul="Rentang Total Honor" :max="$honorBatas" :dari="$honorDari"
                        :sampai="$honorSampai" nama-dari="honor_dari" nama-sampai="honor_sampai" :step="10000"
                        :rupiah="true" mode-id="honorMode" />
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
            <form method="GET" action="{{ route($routes['export']) }}" id="exportForm">
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

        @if ($bisaKelolaBayar)
            <form id="bulkForm" method="POST" action="{{ route($routes['bayarMassal']) }}" style="display:none">
                @csrf
                <input type="hidden" name="aksi" id="bulkAksi">
                <input type="hidden" name="semua_hasil" id="bulkSemuaHasil" value="0">
                <input type="hidden" name="q" value="{{ $q }}">
                <input type="hidden" name="status" value="{{ $status }}">
                {!! $hiddenFilter !!}
                <div id="bulkIds"></div>
            </form>
        @endif

        <form id="bulkHapusForm" method="POST" action="{{ route($routes['hapusMassal']) }}" style="display:none">
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
                    const bulkForm = document.getElementById('bulkForm');
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

                    const hintWrap = document.getElementById('bulkHintWrap');

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
                        if (hintWrap) hintWrap.hidden = hint.hidden && hasilOn.hidden;

                        const bulkSemuaEl = document.getElementById('bulkSemuaHasil');
                        if (bulkSemuaEl) bulkSemuaEl.value = semuaHasil ? '1' : '0';
                    }

                    semua.addEventListener('change', () => {
                        semuaHasil = false;
                        baris().forEach(c => c.checked = semua.checked);
                        segarkan();
                    });

                    /* ===== Expand/collapse daftar nama mitra di sel Petugas ===== */
                    document.getElementById('penugasanBody').addEventListener('click', e => {
                        const toggle = e.target.closest('.mitra-cell-toggle');
                        if (!toggle) return;

                        const ringkas = toggle.querySelector('.mitra-ringkas');
                        const lengkap = toggle.querySelector('.mitra-lengkap');
                        const dibuka = toggle.getAttribute('aria-expanded') === 'true';

                        toggle.setAttribute('aria-expanded', String(!dibuka));
                        ringkas.hidden = !dibuka;
                        lengkap.hidden = dibuka;
                    });
                    document.getElementById('penugasanBody').addEventListener('change', e => {
                        if (e.target.matches('.row-check')) segarkan();
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

                    if (bulkForm) {
                        bar.querySelectorAll('[data-aksi]').forEach(tombol => {
                            tombol.addEventListener('click', async () => {
                                const aksi = tombol.dataset.aksi;
                                const dipilih = baris().filter(c => c.checked);
                                const jumlah = semuaHasil ? TOTAL : dipilih.length;
                                if (jumlah === 0) return;

                                const teks = aksi === 'bayar' ?
                                    'Tandai semua petugas pada ' + jumlah +
                                    ' tugas sebagai SUDAH dibayar?' :
                                    'Tandai semua petugas pada ' + jumlah + ' tugas sebagai BELUM dibayar?';

                                const lanjut = await popup({
                                    jenis: 'konfirmasi',
                                    judul: aksi === 'bayar' ? 'Tandai sudah dibayar?' :
                                        'Tandai belum dibayar?',
                                    pesan: teks,
                                    tombolOk: 'Ya, simpan',
                                    tombolBatal: 'Batal'
                                });
                                if (!lanjut) return;

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
                                bulkForm.submit();
                            });
                        });
                    }

                    segarkan();
                })();

                /* ===== Popover aksi penugasan ===== */
                (function() {
                    const pop = document.getElementById('penPopover');
                    const tbody = document.getElementById('penugasanBody');
                    const hapusForm = document.getElementById('hapusForm');
                    const popBayar = document.getElementById('popBayar');
                    const popBayarReadonly = document.getElementById('popBayarReadonly');
                    const popKoordinator = document.getElementById('popKoordinator');
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

                        const top = Math.max(margin, Math.min(r.top, window.innerHeight - h - margin));

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
                        if (popKoordinator) popKoordinator.textContent = info.koordinator;
                        document.getElementById('popPetugas').textContent = info.petugasTeks;
                        document.getElementById('popPeriode').textContent = info.periode;

                        if (popBayar) {
                            const adaPetugas = Number(info.jumlahPetugas) > 0;
                            popBayar.checked = !!info.dibayar;
                            popBayar.disabled = !adaPetugas;
                            document.getElementById('popBayarTeks').textContent = !adaPetugas ?
                                'Belum ada petugas untuk dibayar' :
                                (info.dibayar ? 'Semua honor sudah dibayar' : 'Tandai semua honor sudah dibayar');
                        }
                        if (popBayarReadonly) {
                            popBayarReadonly.textContent = info.statusTeks;
                        }

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

                    if (popBayar) {
                        popBayar.addEventListener('change', async () => {
                            if (!info || !info.bayarUrl) return;
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
                    }

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

        {{-- Floating Bar Aksi Massal --}}
        <div class="bulk-dock-wrap" id="bulkBar" hidden>
            <div class="bulk-dock-hint" id="bulkHintWrap" hidden>
                <span id="bulkHint" hidden>
                    Semua di halaman ini dipilih.
                    <button type="button" class="bulk-link" id="bulkPilihHasil">
                        Pilih semua {{ $daftar->total() }} tugas hasil filter
                    </button>
                </span>
                <span id="bulkHasilOn" hidden>
                    Seluruh {{ $daftar->total() }} tugas hasil filter dipilih.
                    <button type="button" class="bulk-link" id="bulkBatalHasil">Batalkan</button>
                </span>
            </div>

            <div class="bulk-dock" role="region" aria-label="Aksi massal penugasan">
                <span class="bulk-dock-count" id="bulkCount">0</span>

                @if ($bisaKelolaBayar)
                    <button type="button" class="dock-btn-lime" data-aksi="bayar">
                        <span>Dibayar</span>
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" />
                        </svg>
                    </button>

                    <button type="button" class="dock-btn-warn" data-aksi="batal">
                        <span>Belum Dibayar</span>
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M14.8 9.2l-5.6 5.6M9.2 9.2l5.6 5.6" />
                        </svg>
                    </button>
                @endif

                <button type="button" class="dock-btn-del" id="bulkHapus" title="Hapus tugas terpilih"
                    aria-label="Hapus tugas terpilih">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                    </svg>
                </button>

                <button type="button" class="dock-btn-close" id="bulkReset" title="Batal pilih"
                    aria-label="Batal pilih">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </x-dynamic-component>
