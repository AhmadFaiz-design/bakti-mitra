@php
    $persen = $ringkasan['total'] > 0 ? (int) round(($ringkasan['dibayar'] / $ringkasan['total']) * 100) : 0;

    $tugasDipilih = array_values(array_filter((array) request('tugas', [])));

    // Slider rentang honor
    $parseRp = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
    $honorDari = request()->filled('honor_dari') ? $parseRp(request('honor_dari')) : 0;
    $honorSampaiReq = request()->filled('honor_sampai') ? $parseRp(request('honor_sampai')) : null;
    $honorBatas = max((int) $honorMaks, $honorDari, (int) $honorSampaiReq);
    $honorSampai = $honorSampaiReq ?? $honorBatas;
    $adaHonor = $honorDari > 0 || ($honorSampaiReq !== null && $honorSampaiReq < $honorBatas);

    $jumlahFilter = (int) $adaHonor + (int) request()->filled('wilayah') + count($tugasDipilih);

    // Input tersembunyi untuk meneruskan filter ke form pencarian & export
    $kunciFilter = ['honor_mode', 'honor_dari', 'honor_sampai', 'wilayah'];
    $hiddenFilter = collect($kunciFilter)
        ->filter(fn($k) => request()->filled($k))
        ->values()
        ->map(fn($k) => '<input type="hidden" name="' . $k . '" value="' . e(request($k)) . '">')
        ->merge(collect($tugasDipilih)->map(fn($t) => '<input type="hidden" name="tugas[]" value="' . e($t) . '">'))
        ->implode('');
@endphp

<x-koordinator.main>
    <x-slot:titlex>Data Penugasan</x-slot:titlex>

    {{-- HEADER HALAMAN --}}
    <x-header judul="Data Penugasan" subjudul="Daftar Tugas yang Anda Berikan Kepada Mitra dan Status Pembayaran Honornya" />

    {{-- KARTU STATISTIK --}}
    <section class="stats-mitra">
        <div class="card hero">
            <h2><svg>
                    <use href="#i-ring" />
                </svg> Progres Pembayaran</h2>
            <p class="num hijau">{{ $persen }}%</p>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18M16 14.5h2" /></svg>
                Total Honor
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['total'], 0, ',', '.') }}</p>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18M16 14.5h2" /></svg>
                Sudah Dibayar
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['dibayar'], 0, ',', '.') }}</p>
        </div>

        <div class="card stat">
            <h2>
                <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18M16 14.5h2" /></svg>
                Belum Dibayar
            </h2>
            <p class="num kecil">Rp {{ number_format($ringkasan['belum'], 0, ',', '.') }}</p>
        </div>
    </section>

    {{-- PENCARIAN, EXPORT, STATUS, FILTER --}}
    <section class="mitra-toolbar">
        <form method="GET" action="{{ route('koordinator.datamitra') }}" style="display: contents">
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

        <x-admin.status-dropdown :options="['semua' => 'Semua', 'belum' => 'Belum Dibayar', 'lunas' => 'Sudah Dibayar']" :current="$status" label="Pembayaran" />

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
        </div>

        <div class="table-scroll">
            <table class="mitra-table penugasan-table penugasan-koor">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Nama</th>
                        <th>Wilayah</th>
                        <th>Uraian Pekerjaan</th>
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
                            $wilayah = $p->kontrak?->mitra?->wilayah ?: '-';
                            $dibayar = (bool) $p->honor_dibayar;
                            $mulai = $p->tanggal_mulai ? \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/Y') : '-';
                            $selesai = $p->tanggal_selesai ? \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/Y') : '-';
                            $volume = rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',');

                            $info = [
                                'nama' => $nama,
                                'sobat' => $user?->username ?? '-',
                                'wilayah' => $wilayah,
                                'uraian' => $p->uraian_pekerjaan ?? '-',
                                'ro' => $p->ro ?: '',
                                'periode' => $mulai . ' s.d. ' . $selesai,
                                'volume' => trim($volume . ' ' . $p->satuan),
                                'honor' => 'Rp ' . number_format($p->jumlah ?? 0, 0, ',', '.'),
                                'dibayar' => $dibayar,
                                'waktu' => $dibayar ? $p->dibayar_pada?->format('d/m/Y H:i') ?? '' : '',
                            ];
                        @endphp

                        <tr>
                            <td>
                                <div class="profile-avatar">{{ strtoupper(mb_substr($nama, 0, 1)) }}</div>
                            </td>

                            <td>
                                <span class="profile-name">{{ $nama }}</span>
                                @if ($user)
                                    <span class="cell-sub">{{ $user->username }}</span>
                                @endif
                            </td>

                            <td><span class="profile-name">{{ $wilayah }}</span></td>

                            <td class="cell-wrap">
                                {{ $p->uraian_pekerjaan }}
                                @if ($p->ro)
                                    <span class="cell-sub">RO: {{ $p->ro }}</span>
                                @endif
                            </td>

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
                                <button type="button" class="action-btn" aria-label="Detail {{ $nama }}"
                                    aria-haspopup="true" aria-expanded="false" data-info="{{ json_encode($info) }}">
                                    &#8942;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:24px">
                                @if ($q !== '' || $status !== 'semua' || $jumlahFilter > 0)
                                    Tidak ada tugas yang cocok dengan pencarian atau filter.
                                @else
                                    Anda belum memberikan tugas kepada mitra.
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

    {{-- POPOVER DETAIL (satu untuk semua baris, hanya baca) --}}
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
            <span class="pop-label">Wilayah</span>
            <div class="pop-text" id="popWilayah"></div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Uraian Pekerjaan</span>
            <div class="pop-text" id="popUraian"></div>
            <small class="pop-sub" id="popRo"></small>
        </div>

        <div class="pop-row">
            <div class="pop-field">
                <span class="pop-label">Periode</span>
                <div class="pop-text" id="popPeriode"></div>
            </div>
            <div class="pop-field">
                <span class="pop-label">Volume</span>
                <div class="pop-text" id="popVolume"></div>
            </div>
        </div>

        <div class="pop-field">
            <span class="pop-label">Pembayaran</span>
            <div class="pop-text" id="popBayar"></div>
            <small class="pop-sub" id="popWaktu"></small>
        </div>
    </div>

    {{-- PANEL FILTER --}}
    <div class="filter-overlay" id="filterOverlay"></div>

    <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Penugasan" aria-hidden="true">
        <form method="GET" action="{{ route('koordinator.datamitra') }}" id="filterForm" class="filter-form">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="honor_mode" value="rentang" id="honorMode">

            <div class="filter-body">
                <h2 class="filter-title">Filter Penugasan</h2>

                <x-admin.filter-wilayah :opsi="$daftarWilayah" :dipilih="request('wilayah')" />

                <x-admin.filter-tugas judul="Pekerjaan" placeholder="Cari Pekerjaan" :opsi="$daftarTugas"
                    :dipilih="$tugasDipilih" />

                <x-admin.filter-range judul="Rentang Honor" :max="$honorBatas" :dari="$honorDari"
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
    <x-admin.export-js button="exportPenugasan" />

    {{-- MODAL EXPORT --}}
    <div class="modal-overlay" id="exportOverlay"></div>

    <div class="export-modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportTitle"
        aria-hidden="true">
        <form method="GET" action="{{ route('koordinator.penugasan.export') }}" id="exportForm">
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

    @push('scripts')
        <script>
            /* Popover detail penugasan */
            (function() {
                const pop = document.getElementById('penPopover');
                const tbody = document.getElementById('penugasanBody');
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
                    left = Math.max(margin, Math.min(left, window.innerWidth - w - margin));

                    const top = Math.max(margin, Math.min(r.top, window.innerHeight - h - margin));

                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }

                function isi(id, teks) {
                    document.getElementById(id).textContent = teks;
                }

                function buka(btn) {
                    if (aktif && aktif !== btn) aktif.setAttribute('aria-expanded', 'false');

                    let info;
                    try {
                        info = JSON.parse(btn.dataset.info);
                    } catch (e) {
                        return;
                    }

                    isi('popNama', info.nama);
                    isi('popSobat', info.sobat);
                    isi('popHonor', info.honor);
                    isi('popWilayah', info.wilayah);
                    isi('popUraian', info.uraian);
                    isi('popRo', info.ro ? 'RO: ' + info.ro : '');
                    isi('popPeriode', info.periode);
                    isi('popVolume', info.volume || '-');
                    isi('popBayar', info.dibayar ? 'Sudah dibayar' : 'Belum dibayar');
                    isi('popWaktu', info.waktu ? 'Dibayar pada ' + info.waktu : '');

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
            })();
        </script>
    @endpush
</x-koordinator.main>
