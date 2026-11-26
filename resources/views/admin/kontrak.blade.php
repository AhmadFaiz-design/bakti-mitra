@php
    $periodeDipilih = $periode ?? '';
    $jumlahFilter = (int) ($periodeDipilih !== '') + (int) request()->filled('wilayah');

    $kunciFilter = ['periode', 'wilayah'];
    $hiddenFilter = collect($kunciFilter)
        ->filter(fn($k) => request()->filled($k))
        ->values()
        ->map(fn($k) => '<input type="hidden" name="' . $k . '" value="' . e(request($k)) . '">')
        ->implode('');

    $persenKontrak =
        $ringkasan['total_kontrak'] > 0
            ? (int) round(($ringkasan['cek_kontrak'] / $ringkasan['total_kontrak']) * 100)
            : 0;

    $belumKontrak = max(0, (int) $ringkasan['total_kontrak'] - (int) $ringkasan['cek_kontrak']);
    $belumSpj = max(0, (int) $ringkasan['total_kontrak'] - (int) $ringkasan['cek_spj']);
@endphp

<x-admin.main>
    <x-slot:titlex>Kontrak Bulanan Mitra</x-slot:titlex>

    {{-- HEADER HALAMAN (tanpa subjudul) --}}
    <x-header judul="Kontrak Bulanan Mitra" />

    {{-- KARTU STATISTIK --}}
    <section class="stats-mitra stats-mitra-4">
        <div class="card hero">
            <h2>
                <svg>
                    <use href="#i-ring" />
                </svg>
                Verifikasi Kontrak
            </h2>
            <p class="num">{{ $persenKontrak }}%</p>
            <div class="progress"><span style="width: {{ $persenKontrak }}%"></span></div>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="6" width="18" height="12" rx="2.5" />
                    <path d="M3 10h18M7 14h4" />
                </svg>
                Total Kontrak
            </h2>
            <p class="num kecil">{{ $ringkasan['total_kontrak'] }} Kontrak</p>
            <span class="badge {{ $belumKontrak > 0 ? 'warn' : 'lime' }}">
                {{ $belumKontrak }} Belum Diverifikasi
            </span>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24">
                    <path d="M13 6H5.5A2.5 2.5 0 0 0 3 8.5v7A2.5 2.5 0 0 0 5.5 18h13a2.5 2.5 0 0 0 2.5-2.5V12" />
                    <path d="M3 10h10M7 14h4M15.5 6.5l2 2 4-4" />
                </svg>
                Total SPJ
            </h2>
            <p class="num kecil">{{ $ringkasan['total_kontrak'] }} SPJ</p>
            <span class="badge {{ $belumSpj > 0 ? 'warn' : 'lime' }}">
                {{ $belumSpj }} Belum Diverifikasi
            </span>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="6" width="18" height="12" rx="2.5" />
                    <path d="M3 10h18M15 14h3" />
                </svg>
                Total Honor
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['total_honor'], 0, ',', '.') }}</p>
            <span class="badge {{ $trenHonor['kelas'] }}" title="Perbandingan bulan ini vs bulan lalu">
                {{ $trenHonor['teks'] }}
            </span>
        </div>
    </section>

    {{-- PENCARIAN, STATUS, FILTER --}}
    <section class="mitra-toolbar">
        <form method="GET" action="{{ route('admin.kontrak') }}" style="display: contents">
            @if (request()->filled('per_page'))
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            @endif
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenFilter !!}
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input type="search" name="q" value="{{ $q }}"
                    placeholder="Cari nama mitra, Sobat ID, atau nomor kontrak..." autocomplete="off">
            </label>
        </form>

        <x-admin.status-dropdown :options="[
            'semua' => 'Semua',
            'belum_nomor' => 'Belum Bernomor',
            'belum_kontrak' => 'Belum Verif. Kontrak',
            'menunggu_spj' => 'Menunggu SPJ',
            'lengkap' => 'Lengkap',
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

    {{-- TABEL KONTRAK --}}
    <section class="table-card">
        <div class="table-heading">
            <h2>
                <svg>
                    <use href="#i-table" />
                </svg>
                Daftar Kontrak Mitra
            </h2>

            <div class="table-heading-actions">
                <button type="button" class="outline-btn" id="exportKontrak">
                    <span>Export</span>
                    <svg>
                        <use href="#i-export" />
                    </svg>
                </button>

                <button type="button" class="dark-pill-btn" id="bukaGenerateModal">
                    <span>Generate Nomor Kontrak</span>
                    <svg>
                        <use href="#i-plus-circle" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="table-scroll">
            <table class="mitra-table kontrak-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="check-mitra" id="selectAll"
                                aria-label="Pilih semua kontrak di halaman ini">
                        </th>
                        <th>Mitra</th>
                        <th>Periode</th>
                        <th>No. Urut</th>
                        <th>Nomor Kontrak &amp; Tgl SPK</th>
                        <th>Total Honor</th>
                        <th>Cek Kontrak</th>
                        <th>Cek SPJ</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="kontrakBody">
                    @forelse ($daftar as $k)
                        @php
                            $user = $k->mitra?->user;
                            $nama = $user?->name ?? '-';
                            $wilayah = $k->mitra?->wilayah;
                            $terkunci = $k->terkunci();
                            $tglSpk = $k->tanggal_spk ? $k->tanggal_spk->format('Y-m-d') : '';

                            $info = [
                                'nama' => $nama,
                                'sobat' => $user?->username ?? '-',
                                'periode' => $k->periode_label . ' (' . $k->pekerjaan_count . ' Tugas)',
                                'honor' => 'Rp ' . number_format((int) ($k->total_honor ?? 0), 0, ',', '.'),
                                'urut' => $k->nomor_urut_kontrak ?: '',
                                'nomor' => $k->nomor_kontrak ?: '',
                                'tglRaw' => $tglSpk,
                                'tglTeks' => $k->tanggal_spk ? $k->tanggal_spk->format('d/m/Y') : '-',
                                'cekKontrak' => (bool) $k->cek_kontrak,
                                'cekSpj' => (bool) $k->cek_spj,
                                'verifUrl' => route('admin.kontrak.verifikasi', $k),
                                'updateUrl' => route('admin.kontrak.update', $k),
                                'spkUrl' => route('admin.kontrak.spk', $k),
                                'terkunci' => $terkunci,
                                'alasan' => $terkunci
                                    ? 'Nomor kontrak tidak bisa diubah selama kontrak atau SPJ terverifikasi.'
                                    : '',
                            ];
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="check-mitra row-check" value="{{ $k->id }}"
                                    aria-label="Pilih kontrak {{ $nama }}">
                            </td>

                            <td>
                                <span class="profile-name">{{ $nama }}</span>
                                <span class="cell-sub">
                                    {{ $user?->username ?? '-' }}@if ($wilayah)
                                        &middot; {{ $wilayah }}
                                    @endif
                                </span>
                            </td>

                            <td>
                                <span class="profile-name">{{ $k->periode_label }}</span>
                                <span class="cell-sub">{{ $k->pekerjaan_count }} Tugas</span>
                            </td>

                            <td class="id-cell">{{ $k->nomor_urut_kontrak ?: '-' }}</td>

                            <td class="cell-wrap">
                                @if ($k->nomor_kontrak)
                                    <span class="profile-name">{{ $k->nomor_kontrak }}</span>
                                    <span class="cell-sub">
                                        SPK: {{ $k->tanggal_spk ? $k->tanggal_spk->format('d/m/Y') : '-' }}
                                    </span>
                                @else
                                    <span class="status-badge pending">Belum bernomor</span>
                                @endif
                            </td>

                            <td class="honor-cell">Rp {{ number_format((int) ($k->total_honor ?? 0), 0, ',', '.') }}
                            </td>

                            <td>
                                <span class="status-badge {{ $k->cek_kontrak ? 'active' : 'pending' }}">
                                    {{ $k->cek_kontrak ? 'Terverifikasi' : 'Belum' }}
                                </span>
                            </td>

                            <td>
                                <span class="status-badge {{ $k->cek_spj ? 'active' : 'pending' }}">
                                    {{ $k->cek_spj ? 'Lengkap' : 'Belum' }}
                                </span>
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
                                <td colspan="9" style="text-align:center;padding:24px">
                                    Belum ada data kontrak pada filter ini. Kontrak otomatis terbuat saat mitra di-assign ke
                                    tugas.
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
                        <span>dari {{ $daftar->total() }} Kontrak</span>
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

        {{-- POPOVER AKSI KONTRAK --}}
        <div class="mitra-popover" id="kontrakPopover" role="dialog" aria-label="Detail kontrak mitra">
            <div class="pop-field">
                <span class="pop-label">Mitra</span>
                <strong class="pop-name" id="popKtrNama"></strong>
            </div>

            <div class="pop-row">
                <div class="pop-field">
                    <span class="pop-label">Sobat ID</span>
                    <strong class="pop-value" id="popKtrSobat"></strong>
                </div>
                <div class="pop-field">
                    <span class="pop-label">Total Honor</span>
                    <strong class="pop-value" id="popKtrHonor"></strong>
                </div>
            </div>

            <div class="pop-field">
                <span class="pop-label">Periode</span>
                <div class="pop-text" id="popKtrPeriode"></div>
            </div>

            <div class="pop-field">
                <span class="pop-label">Nomor Kontrak &amp; Tgl SPK</span>
                <div class="pop-text" id="popKtrNomor"></div>
                <small class="pop-sub" id="popKtrTgl"></small>
            </div>

            <div class="pop-field">
                <span class="pop-label">Verifikasi</span>
                <div class="pop-checks-col">
                    <label class="pop-check">
                        <input type="checkbox" id="popCekKontrak">
                        <span>Cek Kontrak (terverifikasi)</span>
                    </label>
                    <label class="pop-check">
                        <input type="checkbox" id="popCekSpj">
                        <span>Cek SPJ (lengkap)</span>
                    </label>
                </div>
            </div>

            <div class="pop-field">
                <span class="pop-label">Aksi</span>
                <div class="pop-actions">
                    <button type="button" class="pop-btn pop-btn-edit" id="popEditNomor">
                        Edit Nomor
                        <svg viewBox="0 0 24 24">
                            <path d="M4 20h4L19 9l-4-4L4 16v4zM13.5 6.5l4 4" />
                        </svg>
                    </button>
                    <a href="#" class="pop-btn pop-btn-spk" id="popSpkPdf">
                        SPK PDF
                        <svg viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                        </svg>
                    </a>
                </div>

                <div class="pop-warning" id="popKtrKunci" role="alert" hidden>
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3l10 18H2L12 3zM12 10v4M12 17.5v.01" />
                    </svg>
                    <span id="popKtrKunciTeks"></span>
                </div>
            </div>
        </div>

        {{-- MODAL OVERLAY BERSAMA --}}
        <div class="modal-overlay" id="kontrakOverlay"></div>

        {{-- MODAL GENERATE NOMOR KONTRAK --}}
        <div class="kontrak-modal" id="generateModal" role="dialog" aria-modal="true" aria-labelledby="genTitle"
            aria-hidden="true">
            <div class="kontrak-modal-head">
                <h2 id="genTitle">Generate Nomor Kontrak Bulanan</h2>
                <button type="button" class="outline-btn kontrak-modal-close" id="genClose">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.kontrak.generate') }}">
                @csrf
                <div class="kontrak-modal-grid">
                    <div class="kontrak-field full">
                        <label for="genPeriode">Periode Bulan Kontrak</label>
                        <select id="genPeriode" name="periode" required>
                            @foreach ($daftarPeriode as $pItem)
                                <option value="{{ $pItem['v'] }}" @selected($periodeDefaultGenerate === $pItem['v'])>
                                    {{ $pItem['t'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="kontrak-field">
                        <label for="genKodeX">Kode Bulan (x) — sama untuk 1 bulan</label>
                        <input type="text" id="genKodeX" name="kode_x" placeholder="Contoh: 216" required>
                    </div>

                    <div class="kontrak-field">
                        <label for="genMulaiY">Mulai Nomor Urut (y)</label>
                        <input type="number" id="genMulaiY" name="mulai_y" min="1" value="1" required>
                    </div>

                    <div class="kontrak-field">
                        <label for="genKodeUnit">Kode Unit / Klasifikasi</label>
                        <input type="text" id="genKodeUnit" name="kode_unit" value="74710/PL-300" required>
                    </div>

                    <div class="kontrak-field">
                        <label for="genTanggalSpk">Tanggal SPK</label>
                        <input type="date" id="genTanggalSpk" name="tanggal_spk"
                            value="{{ now()->format('Y-m-d') }}" required>
                    </div>

                    <div class="kontrak-field full">
                        <label for="genUrut">Urutan Penomoran Mitra</label>
                        <select id="genUrut" name="urut_berdasarkan">
                            <option value="nama">Berdasarkan Nama Mitra (A - Z)</option>
                            <option value="id">Berdasarkan Urutan Kontrak Dibuat</option>
                        </select>
                    </div>

                    <div class="kontrak-field full">
                        <label class="kontrak-field-check">
                            <input type="checkbox" name="timpa" value="1">
                            Timpa ulang nomor kontrak yang sudah ada (selain yang sudah diverifikasi)
                        </label>
                    </div>
                </div>

                <div class="kontrak-preview-box">
                    <small id="genInfoPeriode">Preview format penomoran:</small>
                    <div>No. Urut: <strong id="prevUrut">216.01</strong></div>
                    <div>No. Kontrak: <strong id="prevNomor">B-216.01/74710/PL-300/{{ now()->year }}</strong></div>
                </div>

                <div class="kontrak-modal-footer">
                    <button type="button" class="btn-cancel" id="genCancel">Batal</button>
                    <button type="submit" class="btn-apply" @disabled($daftarPeriode->isEmpty())>Generate Nomor</button>
                </div>
            </form>
        </div>

        {{-- MODAL EDIT NOMOR KONTRAK SATUAN --}}
        <div class="kontrak-modal" id="editKontrakModal" role="dialog" aria-modal="true"
            aria-labelledby="editKontrakTitle" aria-hidden="true">
            <div class="kontrak-modal-head">
                <h2 id="editKontrakTitle">Edit Nomor Kontrak</h2>
                <button type="button" class="outline-btn kontrak-modal-close" id="editClose">&times;</button>
            </div>

            <form method="POST" id="editKontrakForm">
                @csrf
                @method('PUT')
                <p id="editMitraInfo" class="kontrak-modal-sub"></p>

                <div class="kontrak-modal-grid">
                    <div class="kontrak-field">
                        <label for="editUrut">Nomor Urut (x.y)</label>
                        <input type="text" id="editUrut" name="nomor_urut_kontrak" placeholder="216.01" required>
                    </div>
                    <div class="kontrak-field">
                        <label for="editTgl">Tanggal SPK</label>
                        <input type="date" id="editTgl" name="tanggal_spk" required>
                    </div>
                    <div class="kontrak-field full">
                        <label for="editNomor">Nomor Kontrak Lengkap</label>
                        <input type="text" id="editNomor" name="nomor_kontrak"
                            placeholder="B-216.01/74710/PL-300/2026" required>
                    </div>
                </div>

                <div class="kontrak-modal-footer">
                    <button type="button" class="btn-cancel" id="editCancel">Batal</button>
                    <button type="submit" class="btn-apply">Simpan</button>
                </div>
            </form>
        </div>

        {{-- MODAL EXPORT KONTRAK --}}
        <div class="export-modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportTitle"
            aria-hidden="true">
            <form method="GET" action="{{ Route::has('admin.kontrak.export') ? route('admin.kontrak.export') : '#' }}"
                id="exportForm">
                <input type="hidden" name="q" value="{{ $q }}">
                <input type="hidden" name="status" value="{{ $status }}">
                {!! $hiddenFilter !!}

                <h2 id="exportTitle" class="export-title">Export Kontrak Mitra</h2>
                <p class="export-sub">Data kontrak yang diexport berdasarkan hasil filter</p>

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

        {{-- PANEL FILTER --}}
        <div class="filter-overlay" id="filterOverlay"></div>
        <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Kontrak" aria-hidden="true">
            <form method="GET" action="{{ route('admin.kontrak') }}" id="filterForm" class="filter-form">
                @if (request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                @endif
                <input type="hidden" name="q" value="{{ $q }}">
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="filter-body">
                    <h2 class="filter-title">Filter Kontrak</h2>

                    <x-admin.filter-tugas judul="Periode Kontrak" placeholder="Cari Bulan / Tahun" nama="periode"
                        :banyak="false" :opsi="$daftarPeriode" :dipilih="$periodeDipilih !== '' ? [$periodeDipilih] : []" />

                    <x-admin.filter-wilayah :opsi="$daftarWilayah" :dipilih="request('wilayah')" />
                </div>

                <div class="filter-footer">
                    <button type="button" class="filter-reset" id="filterReset">Reset</button>
                    <button type="button" class="btn-cancel" id="filterCancel">Cancel</button>
                    <button type="submit" class="btn-apply">Terapkan</button>
                </div>
            </form>
        </aside>

        <x-admin.filter-js />

        {{-- Floating Bar Aksi Massal + Bar Pilihan Verifikasi di Atasnya --}}
        <div class="bulk-dock-wrap" id="bulkBar" hidden>
            <div class="bulk-dock-hint" id="bulkHintWrap" hidden>
                <span id="bulkHint" hidden>
                    Semua di halaman ini dipilih.
                    <button type="button" class="bulk-link" id="bulkPilihHasil">
                        Pilih semua {{ $daftar->total() }} kontrak hasil filter
                    </button>
                </span>
                <span id="bulkHasilOn" hidden>
                    Seluruh {{ $daftar->total() }} kontrak hasil filter dipilih.
                    <button type="button" class="bulk-link" id="bulkBatalHasil">Batalkan</button>
                </span>
            </div>

            <div class="bulk-verif-pop" id="bulkVerifPanel" hidden>
                <form method="POST" action="{{ route('admin.kontrak.verifikasi-massal') }}" id="bulkVerifForm">
                    @csrf
                    <input type="hidden" name="mode" id="bulkVerifMode">
                    <input type="hidden" name="semua_hasil" id="bulkVerifSemua" value="0">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    {!! $hiddenFilter !!}
                    <div id="bulkVerifIds"></div>

                    <p class="bulk-verif-title" id="bulkModalTitle">Pilih jenis verifikasi yang ingin diterapkan</p>

                    <div class="bulk-verif-checks">
                        <label class="bulk-verif-check">
                            <input type="checkbox" name="targets[]" value="kontrak" id="bulkTargetKontrak">
                            <span id="bulkLabelKontrak">Verifikasi Kontrak</span>
                        </label>

                        <label class="bulk-verif-check">
                            <input type="checkbox" name="targets[]" value="spj" id="bulkTargetSpj">
                            <span id="bulkLabelSpj">SPJ</span>
                        </label>
                    </div>

                    <div class="bulk-verif-note" id="bulkModalInfo" hidden>
                        Catatan: Verifikasi kontrak hanya berlaku untuk kontrak yang sudah memiliki Nomor Kontrak &amp;
                        Tanggal SPK.
                    </div>

                    <div class="bulk-verif-actions">
                        <button type="button" class="outline-btn" id="bulkCancel">Batal</button>
                        <button type="submit" class="dark-pill-btn" id="bulkVerifSubmit" disabled>Ya,
                            verifikasi</button>
                    </div>
                </form>
            </div>

            <div class="bulk-dock" role="region" aria-label="Aksi massal kontrak">
                <span class="bulk-dock-count" id="bulkCount">0</span>

                <button type="button" class="dock-btn-lime" data-bulk-mode="verifikasi">
                    <span>Verifikasi</span>
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M8.5 12.5l2.5 2.5 4.5-5" />
                    </svg>
                </button>

                <button type="button" class="dock-btn-warn" data-bulk-mode="batal">
                    <span>Batal Verifikasi</span>
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M14.8 9.2l-5.6 5.6M9.2 9.2l5.6 5.6" />
                    </svg>
                </button>

                <button type="button" class="dock-btn-del" id="bulkHapusKontrak" title="Hapus kontrak"
                    aria-label="Hapus kontrak">
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

        @push('scripts')
            <script>
                (function() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
                    const overlay = document.getElementById('kontrakOverlay');
                    const genModal = document.getElementById('generateModal');
                    const editModal = document.getElementById('editKontrakModal');
                    const exportModal = document.getElementById('exportModal');

                    const pesanError = teks =>
                        typeof popup === 'function' ? popup({
                            jenis: 'gagal',
                            judul: 'Belum bisa diproses',
                            pesan: teks
                        }) : alert(teks);

                    function tutupSemuaModal() {
                        [genModal, editModal, exportModal].filter(Boolean).forEach(m => {
                            m.classList.remove('open');
                            m.setAttribute('aria-hidden', 'true');
                        });
                        overlay.classList.remove('open');
                    }

                    function bukaModal(modal) {
                        tutupSemuaModal();
                        modal.classList.add('open');
                        overlay.classList.add('open');
                        modal.setAttribute('aria-hidden', 'false');
                    }

                    /* ===== Export Modal ===== */
                    document.getElementById('exportKontrak')?.addEventListener('click', () => {
                        tutupPopover();
                        bukaModal(exportModal);
                    });
                    document.getElementById('exportCancel')?.addEventListener('click', tutupSemuaModal);
                    document.getElementById('exportForm')?.addEventListener('submit', e => {
                        if (e.target.getAttribute('action') === '#') {
                            e.preventDefault();
                            tutupSemuaModal();
                            pesanError('Route admin.kontrak.export belum ditambahkan di routes/web.php.');
                            return;
                        }
                        setTimeout(tutupSemuaModal, 400);
                    });

                    /* ===== Popover Aksi Kontrak ===== */
                    const pop = document.getElementById('kontrakPopover');
                    const tbody = document.getElementById('kontrakBody');
                    const popCekKontrak = document.getElementById('popCekKontrak');
                    const popCekSpj = document.getElementById('popCekSpj');
                    const popEditNomor = document.getElementById('popEditNomor');
                    const popSpkPdf = document.getElementById('popSpkPdf');
                    let aktifBtn = null;
                    let info = null;

                    function tutupPopover() {
                        pop.classList.remove('open');
                        if (aktifBtn) aktifBtn.setAttribute('aria-expanded', 'false');
                        aktifBtn = null;
                    }

                    function posisiPopover(btn) {
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

                    function bukaPopover(btn) {
                        if (aktifBtn && aktifBtn !== btn) aktifBtn.setAttribute('aria-expanded', 'false');

                        try {
                            info = JSON.parse(btn.dataset.info);
                        } catch (e) {
                            return;
                        }

                        document.getElementById('popKtrNama').textContent = info.nama;
                        document.getElementById('popKtrSobat').textContent = info.sobat;
                        document.getElementById('popKtrHonor').textContent = info.honor;
                        document.getElementById('popKtrPeriode').textContent = info.periode;
                        document.getElementById('popKtrNomor').textContent = info.nomor || 'Belum bernomor';
                        document.getElementById('popKtrTgl').textContent = info.nomor ?
                            ('No. Urut: ' + (info.urut || '-') + ' · SPK: ' + info.tglTeks) :
                            'Generate atau edit nomor terlebih dahulu';

                        popCekKontrak.checked = !!info.cekKontrak;
                        popCekSpj.checked = !!info.cekSpj;
                        popCekKontrak.disabled = false;
                        popCekSpj.disabled = false;

                        const terkunci = !!info.terkunci;
                        popEditNomor.disabled = terkunci;
                        popSpkPdf.href = info.spkUrl;
                        document.getElementById('popKtrKunciTeks').textContent = info.alasan || '';
                        document.getElementById('popKtrKunci').hidden = !terkunci;

                        aktifBtn = btn;
                        btn.setAttribute('aria-expanded', 'true');
                        pop.classList.add('open');
                        posisiPopover(btn);
                    }

                    tbody.addEventListener('click', e => {
                        const btn = e.target.closest('.action-btn');
                        if (!btn) return;
                        e.stopPropagation();
                        (aktifBtn === btn) ? tutupPopover(): bukaPopover(btn);
                    });

                    document.addEventListener('click', e => {
                        if (aktifBtn && !pop.contains(e.target)) tutupPopover();
                    });
                    window.addEventListener('resize', tutupPopover);
                    window.addEventListener('scroll', e => {
                        if (aktifBtn && !pop.contains(e.target)) tutupPopover();
                    }, true);

                    async function kirimVerifikasi(kolom, cb) {
                        if (!info) return;
                        const nilai = cb.checked;
                        cb.disabled = true;

                        try {
                            const res = await fetch(info.verifUrl, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    kolom,
                                    nilai: nilai ? 1 : 0
                                })
                            });
                            if (!res.ok) {
                                const err = await res.json().catch(() => ({}));
                                throw new Error(err.message || 'Gagal memperbarui status.');
                            }
                            window.location.reload();
                        } catch (err) {
                            cb.checked = !nilai;
                            cb.disabled = false;
                            pesanError(err.message);
                        }
                    }

                    popCekKontrak.addEventListener('change', () => kirimVerifikasi('cek_kontrak', popCekKontrak));
                    popCekSpj.addEventListener('change', () => kirimVerifikasi('cek_spj', popCekSpj));

                    popEditNomor.addEventListener('click', () => {
                        if (!info || info.terkunci) return;
                        const d = info;
                        tutupPopover();
                        document.getElementById('editKontrakForm').action = d.updateUrl;
                        document.getElementById('editMitraInfo').textContent = d.nama + ' — ' + d.periode;
                        document.getElementById('editUrut').value = d.urut || '';
                        document.getElementById('editNomor').value = d.nomor || '';
                        document.getElementById('editTgl').value = d.tglRaw || '{{ now()->format('Y-m-d') }}';
                        bukaModal(editModal);
                    });

                    /* ===== Modal Generate Nomor ===== */
                    const saran = @json($saranPerPeriode);
                    const genPeriode = document.getElementById('genPeriode');
                    const genKodeX = document.getElementById('genKodeX');
                    const genMulaiY = document.getElementById('genMulaiY');
                    const genKodeUnit = document.getElementById('genKodeUnit');
                    const prevUrut = document.getElementById('prevUrut');
                    const prevNomor = document.getElementById('prevNomor');
                    const genInfo = document.getElementById('genInfoPeriode');

                    function updatePreview() {
                        const p = genPeriode.value || '{{ now()->format('Y-m') }}';
                        const tahun = p.split('-')[0] || '{{ now()->year }}';
                        const x = (genKodeX.value || '216').trim();
                        const yNum = Math.max(1, parseInt(genMulaiY.value || '1', 10));
                        const yPad = String(yNum).padStart(2, '0');
                        const unit = (genKodeUnit.value || '74710/PL-300').replace(/^\/+|\/+$/g, '');

                        const urut = x + '.' + yPad;
                        prevUrut.textContent = urut;
                        prevNomor.textContent = 'B-' + urut + '/' + unit + '/' + tahun;
                    }

                    function isiSaranPeriode() {
                        const dataSaran = saran[genPeriode.value];
                        if (dataSaran) {
                            if (dataSaran.kode_x) genKodeX.value = dataSaran.kode_x;
                            genMulaiY.value = dataSaran.mulai_y || 1;
                            genInfo.textContent = 'Total ' + dataSaran.total + ' kontrak (' + dataSaran.belum_nomor +
                                ' belum bernomor). Preview:';
                        }
                        updatePreview();
                    }

                    document.getElementById('bukaGenerateModal').addEventListener('click', () => {
                        tutupPopover();
                        isiSaranPeriode();
                        bukaModal(genModal);
                    });
                    genPeriode.addEventListener('change', isiSaranPeriode);
                    [genKodeX, genMulaiY, genKodeUnit].forEach(el => el.addEventListener('input', updatePreview));
                    document.getElementById('genClose')?.addEventListener('click', tutupSemuaModal);
                    document.getElementById('genCancel')?.addEventListener('click', tutupSemuaModal);

                    document.getElementById('editClose')?.addEventListener('click', tutupSemuaModal);
                    document.getElementById('editCancel')?.addEventListener('click', tutupSemuaModal);
                    overlay.addEventListener('click', tutupSemuaModal);
                    document.addEventListener('keydown', e => {
                        if (e.key === 'Escape') {
                            tutupPopover();
                            tutupSemuaModal();
                        }
                    });

                    /* ===== Aksi Massal (Floating Dock + Panel Verifikasi) ===== */
                    const semua = document.getElementById('selectAll');
                    const bar = document.getElementById('bulkBar');
                    const hitung = document.getElementById('bulkCount');
                    const hint = document.getElementById('bulkHint');
                    const hasilOn = document.getElementById('bulkHasilOn');
                    const hintWrap = document.getElementById('bulkHintWrap');
                    const bulkForm = document.getElementById('bulkVerifForm');
                    const verifPanel = document.getElementById('bulkVerifPanel');
                    const modeBtns = bar.querySelectorAll('[data-bulk-mode]');
                    const TOTAL = @json($daftar->total());
                    const ADA_HALAMAN_LAIN = @json($daftar->hasPages());
                    let semuaHasil = false;

                    const bulkModalTitle = document.getElementById('bulkModalTitle');
                    const bulkVerifMode = document.getElementById('bulkVerifMode');
                    const bulkTargetKontrak = document.getElementById('bulkTargetKontrak');
                    const bulkTargetSpj = document.getElementById('bulkTargetSpj');
                    const bulkLabelKontrak = document.getElementById('bulkLabelKontrak');
                    const bulkLabelSpj = document.getElementById('bulkLabelSpj');
                    const bulkModalInfo = document.getElementById('bulkModalInfo');
                    const bulkVerifSubmit = document.getElementById('bulkVerifSubmit');

                    const baris = () => [...document.querySelectorAll('#kontrakBody .row-check')];

                    function tutupVerifPanel() {
                        verifPanel.hidden = true;
                        modeBtns.forEach(b => b.classList.remove('is-active'));
                    }

                    function segarkanTombolVerif() {
                        const adaDipilih = bulkTargetKontrak.checked || bulkTargetSpj.checked;
                        bulkVerifSubmit.disabled = !adaDipilih;
                        bulkModalInfo.hidden = !(bulkVerifMode.value === 'verifikasi' && bulkTargetKontrak.checked);
                    }

                    function isiIdsVerif() {
                        const dipilih = baris().filter(c => c.checked);
                        const wadah = document.getElementById('bulkVerifIds');
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
                    }

                    function segarkan() {
                        const cek = baris();
                        const n = cek.filter(c => c.checked).length;
                        if (n === 0) {
                            semuaHasil = false;
                            tutupVerifPanel();
                        }

                        semua.checked = cek.length > 0 && n === cek.length;
                        semua.indeterminate = n > 0 && n < cek.length;

                        bar.hidden = n === 0;
                        hitung.textContent = semuaHasil ? TOTAL : n;
                        hint.hidden = !(ADA_HALAMAN_LAIN && n > 0 && n === cek.length && !semuaHasil);
                        hasilOn.hidden = !semuaHasil;
                        if (hintWrap) hintWrap.hidden = hint.hidden && hasilOn.hidden;
                        document.getElementById('bulkVerifSemua').value = semuaHasil ? '1' : '0';
                    }

                    semua.addEventListener('change', () => {
                        semuaHasil = false;
                        baris().forEach(c => c.checked = semua.checked);
                        segarkan();
                    });
                    tbody.addEventListener('change', e => {
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
                        tutupVerifPanel();
                        baris().forEach(c => c.checked = false);
                        segarkan();
                    });

                    [bulkTargetKontrak, bulkTargetSpj].forEach(cb => {
                        cb.addEventListener('change', segarkanTombolVerif);
                    });

                    document.getElementById('bulkCancel')?.addEventListener('click', tutupVerifPanel);

                    modeBtns.forEach(btn => {
                        btn.addEventListener('click', () => {
                            const dipilih = baris().filter(c => c.checked);
                            const jumlah = semuaHasil ? TOTAL : dipilih.length;
                            if (jumlah === 0) return;

                            const mode = btn.dataset.bulkMode;
                            const sudahTerbukaSama = !verifPanel.hidden && bulkVerifMode.value === mode;
                            if (sudahTerbukaSama) {
                                tutupVerifPanel();
                                return;
                            }

                            const isVerif = mode === 'verifikasi';
                            bulkVerifMode.value = mode;
                            bulkModalTitle.textContent = isVerif ?
                                'Pilih jenis verifikasi yang ingin diterapkan' :
                                'Pilih jenis verifikasi yang ingin dibatalkan';

                            bulkLabelKontrak.textContent = isVerif ?
                                'Verifikasi Kontrak' :
                                'Batalkan Verifikasi Kontrak';
                            bulkLabelSpj.textContent = isVerif ? 'SPJ' : 'Batalkan SPJ';
                            bulkVerifSubmit.textContent = isVerif ? 'Ya, verifikasi' : 'Ya, batalkan';

                            bulkTargetKontrak.checked = false;
                            bulkTargetSpj.checked = false;
                            segarkanTombolVerif();

                            modeBtns.forEach(b => b.classList.toggle('is-active', b === btn));
                            tutupPopover();
                            verifPanel.hidden = false;
                        });
                    });

                    bulkForm.addEventListener('submit', e => {
                        if (!bulkTargetKontrak.checked && !bulkTargetSpj.checked) {
                            e.preventDefault();
                            pesanError('Pilih minimal satu jenis verifikasi (Kontrak atau SPJ).');
                            return;
                        }
                        isiIdsVerif();
                    });

                    document.getElementById('bulkHapusKontrak')?.addEventListener('click', async () => {
                        const pesan = 'Kontrak tidak dapat dihapus langsung dari halaman ini. ' +
                            'Jika ingin menghapus kontrak, harus melalui tabel Penugasan lalu pilih dan hapus mitra yang bersangkutan di penugasan.';

                        if (typeof popup === 'function') {
                            const kePenugasan = await popup({
                                jenis: 'peringatan',
                                judul: 'Hapus melalui Penugasan',
                                pesan,
                                tombolOk: 'Buka Penugasan',
                                tombolBatal: 'Tutup'
                            });
                            if (kePenugasan) {
                                window.location.href = @json(route('admin.penugasan'));
                            }
                        } else {
                            alert(pesan);
                        }
                    });

                    segarkan();
                })();
            </script>
        @endpush
    </x-admin.main>
