@php
    $persen =
        $ringkasan['total_kontrak'] > 0
            ? (int) round(($ringkasan['cek_kontrak'] / $ringkasan['total_kontrak']) * 100)
            : 0;

    $belumSpj = max(0, (int) $ringkasan['total_kontrak'] - (int) $ringkasan['cek_spj']);
    $hiddenPeriode = $periode !== '' ? '<input type="hidden" name="periode" value="' . e($periode) . '">' : '';
@endphp

<x-mitra.main>
    <x-slot:titlex>Kontrak Saya</x-slot:titlex>

    {{-- HEADER HALAMAN (tanpa subjudul) --}}
    <x-header judul="Kontrak Bulanan Saya" />

    {{-- KARTU STATISTIK --}}
    <section class="stats-mitra stats-mitra-4">
        <div class="card hero">
            <h2>
                <svg>
                    <use href="#i-ring" />
                </svg>
                Verifikasi Kontrak
            </h2>
            <p class="num">{{ $persen }}%</p>
            <div class="progress"><span style="width: {{ $persen }}%"></span></div>
        </div>

        <div class="card stat">
            <h2>
                <svg>
                    <use href="#i-cal" />
                </svg>
                Hari Kontrak
            </h2>
            <p class="num kecil">{{ $hariKontrak }} Hari</p>
            <span class="badge">{{ $tugasBulanIni }} Tugas Bulan Ini</span>
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
        <form method="GET" action="{{ route('mitra.kontrak') }}" style="display: contents">
            @if (request()->filled('per_page'))
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            @endif
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenPeriode !!}
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input type="search" name="q" value="{{ $q }}"
                    placeholder="Cari nomor kontrak atau nomor urut..." autocomplete="off">
            </label>
        </form>

        <x-admin.status-dropdown :options="[
            'semua' => 'Semua',
            'belum' => 'Menunggu Verifikasi',
            'verif' => 'Kontrak Terverifikasi',
            'lengkap' => 'SPJ Lengkap',
        ]" :current="$status" label="Status" />

        <button type="button" class="outline-btn" id="filterToggle" aria-expanded="false" aria-controls="filterPanel">
            <svg>
                <use href="#i-filter" />
            </svg>
            Filter
            @if ($periode !== '')
                <span class="filter-count">1</span>
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
                Daftar Kontrak
            </h2>
        </div>

        <div class="table-scroll">
            <table class="mitra-table penugasan-table mitra-tugas-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Periode Kontrak</th>
                        <th>Nomor Kontrak &amp; Tgl SPK</th>
                        <th>Jumlah Tugas</th>
                        <th>Total Honor</th>
                        <th>Verifikasi Kontrak</th>
                        <th>Verifikasi SPJ</th>
                        <th class="aksi-col">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kontrakMitraBody">
                    @forelse ($daftar as $k)
                        @php
                            $bisaUnduhSpk = $k->cek_kontrak && !empty($k->nomor_kontrak) && $k->pekerjaan_count > 0;
                            $rincian = $k->pekerjaan
                                ->map(function ($p) {
                                    $vol =
                                        rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',') ?: '0';
                                    return [
                                        'uraian' => $p->uraian_pekerjaan,
                                        'koor' => $p->koordinator?->name ?? '-',
                                        'vol' => $vol . ' ' . $p->satuan,
                                        'honor' => 'Rp ' . number_format((int) $p->jumlah, 0, ',', '.'),
                                        'bayar' => $p->honor_dibayar ? 'Sudah Dibayar' : 'Belum Dibayar',
                                    ];
                                })
                                ->values();

                            $modalData = [
                                'periode' => $k->periode_label,
                                'nomor' => $k->nomor_kontrak ?: 'Belum diberi nomor',
                                'urut' => $k->nomor_urut_kontrak ?: '-',
                                'tglSpk' => $k->tanggal_spk ? $k->tanggal_spk->format('d/m/Y') : '-',
                                'honor' => 'Rp ' . number_format((int) ($k->total_honor ?? 0), 0, ',', '.'),
                                'items' => $rincian,
                            ];
                        @endphp
                        <tr>
                            <td>{{ $daftar->firstItem() + $loop->index }}</td>

                            <td>
                                <span class="profile-name">{{ $k->periode_label }}</span>
                            </td>

                            <td class="cell-wrap">
                                @if ($k->nomor_kontrak)
                                    <span class="profile-name">{{ $k->nomor_kontrak }}</span>
                                    <span class="cell-sub">
                                        No. Urut: {{ $k->nomor_urut_kontrak ?: '-' }} &middot;
                                        SPK: {{ $k->tanggal_spk ? $k->tanggal_spk->format('d/m/Y') : '-' }}
                                    </span>
                                @else
                                    <span class="status-badge pending">Menunggu Penomoran</span>
                                @endif
                            </td>

                            <td>{{ $k->pekerjaan_count }} Tugas</td>

                            <td class="honor-cell">Rp {{ number_format((int) ($k->total_honor ?? 0), 0, ',', '.') }}
                            </td>

                            <td>
                                <span class="status-badge {{ $k->cek_kontrak ? 'active' : 'pending' }}">
                                    {{ $k->cek_kontrak ? 'Terverifikasi' : 'Menunggu' }}
                                </span>
                            </td>

                            <td>
                                <span class="status-badge {{ $k->cek_spj ? 'active' : 'pending' }}">
                                    {{ $k->cek_spj ? 'Lengkap' : 'Menunggu' }}
                                </span>
                            </td>

                            <td class="action-cell">
                                <div style="display:inline-flex;gap:6px;align-items:center">
                                    <button type="button" class="detail-tugas-btn"
                                        data-kontrak="{{ json_encode($modalData) }}">
                                        Rincian
                                    </button>
                                    @if ($bisaUnduhSpk)
                                        <a href="{{ route('mitra.kontrak.spk', $k) }}" class="detail-tugas-btn"
                                            style="text-decoration:none" title="Download PDF SPK">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <path d="M7 10l5 5 5-5" />
                                                <path d="M12 15V3" />
                                            </svg>
                                            SPK PDF
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:24px">
                                Belum ada kontrak. Kontrak otomatis terbuat saat Anda mendapatkan tugas pada bulan
                                tersebut.
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

    {{-- MODAL RINCIAN PEKERJAAN DALAM KONTRAK --}}
    <div class="modal-overlay" id="kontrakMitraOverlay"></div>
    <div class="jobdesk-modal" id="kontrakMitraModal" role="dialog" aria-modal="true" aria-labelledby="kmTitle"
        aria-hidden="true">
        <div class="jobdesk-head">
            <h2 id="kmTitle">Rincian Kontrak</h2>
            <button type="button" class="jobdesk-close" id="kmClose" aria-label="Tutup">&times;</button>
        </div>

        <div class="jobdesk-grid" style="margin-bottom:14px">
            <div class="jobdesk-item">
                <span>Periode Kontrak</span>
                <strong id="kmPeriode"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Total Nilai Kontrak</span>
                <strong id="kmHonor"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Nomor Kontrak</span>
                <strong id="kmNomor"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Tanggal SPK (No. Urut)</span>
                <strong id="kmTgl"></strong>
            </div>
        </div>

        <div class="table-scroll" style="max-height:260px;overflow:auto;border:1px solid #F3E8DF;border-radius:14px">
            <table class="mitra-table" style="min-width:100%">
                <thead>
                    <tr>
                        <th>Tugas</th>
                        <th>Koordinator</th>
                        <th>Volume</th>
                        <th>Honor</th>
                    </tr>
                </thead>
                <tbody id="kmBody"></tbody>
            </table>
        </div>

        <div class="jobdesk-footer" style="margin-top:16px">
            <button type="button" class="dark-pill-btn" id="kmTutup">Tutup</button>
        </div>
    </div>

    {{-- PANEL FILTER --}}
    <div class="filter-overlay" id="filterOverlay"></div>
    <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Kontrak" aria-hidden="true">
        <form method="GET" action="{{ route('mitra.kontrak') }}" id="filterForm" class="filter-form">
            @if (request()->filled('per_page'))
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            @endif
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">

            <div class="filter-body">
                <h2 class="filter-title">Filter Kontrak</h2>

                <x-admin.filter-tugas judul="Periode Kontrak" placeholder="Cari Bulan / Tahun" nama="periode"
                    :banyak="false" :opsi="$daftarPeriode" :dipilih="$periode !== '' ? [$periode] : []" />
            </div>

            <div class="filter-footer">
                <button type="button" class="filter-reset" id="filterReset">Reset</button>
                <button type="button" class="btn-cancel" id="filterCancel">Cancel</button>
                <button type="submit" class="btn-apply">Terapkan</button>
            </div>
        </form>
    </aside>

    <x-admin.filter-js />

    @push('scripts')
        <script>
            (function() {
                const modal = document.getElementById('kontrakMitraModal');
                const overlay = document.getElementById('kontrakMitraOverlay');
                const bodyEl = document.getElementById('kmBody');

                function tutup() {
                    modal.classList.remove('open');
                    overlay.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                }

                document.getElementById('kontrakMitraBody').addEventListener('click', e => {
                    const btn = e.target.closest('.detail-tugas-btn[data-kontrak]');
                    if (!btn) return;

                    let d;
                    try {
                        d = JSON.parse(btn.dataset.kontrak);
                    } catch (err) {
                        return;
                    }

                    document.getElementById('kmPeriode').textContent = d.periode;
                    document.getElementById('kmHonor').textContent = d.honor;
                    document.getElementById('kmNomor').textContent = d.nomor;
                    document.getElementById('kmTgl').textContent = d.tglSpk + ' (' + d.urut + ')';

                    bodyEl.innerHTML = '';
                    (d.items || []).forEach(it => {
                        const tr = document.createElement('tr');
                        [it.uraian, it.koor, it.vol, it.honor + ' (' + it.bayar + ')'].forEach(txt => {
                            const td = document.createElement('td');
                            td.textContent = txt;
                            tr.appendChild(td);
                        });
                        bodyEl.appendChild(tr);
                    });

                    modal.classList.add('open');
                    overlay.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                });

                overlay.addEventListener('click', tutup);
                document.getElementById('kmClose').addEventListener('click', tutup);
                document.getElementById('kmTutup').addEventListener('click', tutup);
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') tutup();
                });
            })();
        </script>
    @endpush
</x-mitra.main>
