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
        (int) request()->filled('periode') +
        (int) request()->filled('waktu') +
        (int) request()->filled('koordinator') +
        count($tugasDipilih);

    $kunciFilter = ['honor_mode', 'honor_jumlah', 'honor_dari', 'honor_sampai', 'periode', 'waktu', 'koordinator'];
    $hiddenFilter = collect($kunciFilter)
        ->filter(fn($k) => request()->filled($k))
        ->values()
        ->map(fn($k) => '<input type="hidden" name="' . $k . '" value="' . e(request($k)) . '">')
        ->merge(collect($tugasDipilih)->map(fn($t) => '<input type="hidden" name="tugas[]" value="' . e($t) . '">'))
        ->implode('');

    $opsiWaktu = [
        ['v' => 'berlangsung', 't' => 'Berlangsung'],
        ['v' => 'akan_datang', 't' => 'Akan Datang'],
        ['v' => 'selesai', 't' => 'Selesai'],
    ];
@endphp

<x-mitra.main>
    <x-slot:titlex>Daftar Tugas</x-slot:titlex>

    {{-- HEADER HALAMAN --}}
    <x-header judul="Daftar Tugas Saya" subjudul="Lihaat Detail Jobdesk, Periode Pelaksanaan, Target Volume, dan Status Pembayaran Honor Anda" />

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

    {{-- PENCARIAN, STATUS, FILTER --}}
    <section class="mitra-toolbar">
        <form method="GET" action="{{ route('mitra.tugas') }}" style="display: contents">
            <input type="hidden" name="status" value="{{ $status }}">
            {!! $hiddenFilter !!}
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input id="searchTugasMitra" type="search" name="q" value="{{ $q }}"
                    placeholder="Cari uraian pekerjaan, RO, atau koordinator..." autocomplete="off">
            </label>
        </form>

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

    {{-- TABEL DAFTAR TUGAS --}}
    <section class="table-card">
        <div class="table-heading">
            <h2><svg>
                    <use href="#i-list" />
                </svg> List Penugasan</h2>
        </div>

        <div class="table-scroll">
            <table class="mitra-table penugasan-table mitra-tugas-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Uraian Pekerjaan &amp; Jobdesk</th>
                        <th>Koordinator</th>
                        <th>Periode</th>
                        <th>Target Volume</th>
                        <th>Tarif &amp; Honor</th>
                        <th>Pembayaran</th>
                        <th class="aksi-col">Aksi</th>
                    </tr>
                </thead>

                <tbody id="tugasBody">
                    @forelse ($daftar as $p)
                        @php
                            $dibayar = (bool) $p->honor_dibayar;
                            $mulaiRaw = $p->tanggal_mulai?->format('Y-m-d');
                            $selesaiRaw = $p->tanggal_selesai?->format('Y-m-d');
                            $mulai = $p->tanggal_mulai ? $p->tanggal_mulai->format('d/m/Y') : '-';
                            $selesai = $p->tanggal_selesai ? $p->tanggal_selesai->format('d/m/Y') : '-';
                            $volume = rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',') ?: '0';

                            if ($selesaiRaw && $selesaiRaw < $hariIni) {
                                $waktuLabel = 'Selesai';
                                $waktuClass = 'selesai';
                            } elseif ($mulaiRaw && $mulaiRaw > $hariIni) {
                                $waktuLabel = 'Akan Datang';
                                $waktuClass = 'akan';
                            } else {
                                $waktuLabel = 'Berlangsung';
                                $waktuClass = 'berlangsung';
                            }

                            $kontrak = $p->kontrak;
                            $periodeKontrak = $kontrak
                                ? \Carbon\Carbon::create((int) $kontrak->periode_tahun, (int) $kontrak->periode_bulan, 1)
                                    ->locale('id')
                                    ->translatedFormat('F Y')
                                : '-';

                            $verifKontrak = $kontrak
                                ? (($kontrak->cek_kontrak && $kontrak->cek_spj)
                                    ? 'Kontrak & SPJ Terverifikasi'
                                    : ($kontrak->cek_kontrak
                                        ? 'Kontrak Terverifikasi (SPJ Menunggu)'
                                        : 'Menunggu Verifikasi Kontrak'))
                                : '-';

                            $pulsaTeks = $p->paket_data_pulsa
                                ? 'Termasuk' . ($p->ro_paket_data_pulsa ? ' (RO: ' . $p->ro_paket_data_pulsa . ')' : '')
                                : 'Tidak termasuk';

                            $anggaran = collect([
                                $p->kro_ro ? 'KRO: ' . $p->kro_ro : null,
                                $p->komponen ? 'Komponen: ' . $p->komponen : null,
                                $p->ro ? 'RO: ' . $p->ro : null,
                            ])->filter()->implode(' · ') ?: '-';

                            $detail = [
                                'uraian'      => $p->uraian_pekerjaan ?? '-',
                                'koordinator' => ($p->koordinator?->name ?? '-') . ($p->koordinator?->username ? ' (' . $p->koordinator->username . ')' : ''),
                                'periode'     => $mulai . ' s.d. ' . $selesai,
                                'waktu'       => $waktuLabel,
                                'volume'      => $volume . ' ' . $p->satuan,
                                'tarif'       => 'Rp ' . number_format((int) ($p->harga_satuan ?? 0), 0, ',', '.') . ' / ' . $p->satuan,
                                'honor'       => 'Rp ' . number_format((int) ($p->jumlah ?? 0), 0, ',', '.'),
                                'kontrak'     => $periodeKontrak . ' — ' . $verifKontrak,
                                'anggaran'    => $anggaran,
                                'pulsa'       => $pulsaTeks,
                                'pembayaran'  => $dibayar
                                    ? 'Sudah Dibayar' . ($p->dibayar_pada ? ' (' . $p->dibayar_pada->format('d/m/Y H:i') . ')' : '')
                                    : 'Belum Dibayar',
                            ];
                        @endphp

                        <tr>
                            <td>{{ $daftar->firstItem() + $loop->index }}</td>

                            <td class="cell-wrap">
                                <span class="profile-name">{{ $p->uraian_pekerjaan }}</span>
                                @if ($p->ro)
                                    <span class="cell-sub">RO: {{ $p->ro }}</span>
                                @endif
                                @if ($p->paket_data_pulsa)
                                    <span class="pulsa-chip">+ Paket Data / Pulsa</span>
                                @endif
                            </td>

                            <td>
                                <span class="profile-name">{{ $p->koordinator?->name ?? '-' }}</span>
                                @if ($p->koordinator?->username)
                                    <span class="cell-sub">{{ $p->koordinator->username }}</span>
                                @endif
                            </td>

                            <td class="id-cell">
                                {{ $mulai }}
                                <span class="cell-sub">s.d. {{ $selesai }}</span>
                                <span class="waktu-badge {{ $waktuClass }}">{{ $waktuLabel }}</span>
                            </td>

                            <td class="id-cell">{{ $volume }} {{ $p->satuan }}</td>

                            <td class="honor-cell">
                                Rp {{ number_format((int) ($p->jumlah ?? 0), 0, ',', '.') }}
                                <span class="cell-sub">@ Rp {{ number_format((int) ($p->harga_satuan ?? 0), 0, ',', '.') }} / {{ $p->satuan }}</span>
                            </td>

                            <td>
                                @if ($dibayar)
                                    <span class="status-badge active">Sudah Dibayar</span>
                                    @if ($p->dibayar_pada)
                                        <span class="cell-sub">{{ $p->dibayar_pada->format('d/m/Y H:i') }}</span>
                                    @endif
                                @else
                                    <span class="status-badge pending">Belum Dibayar</span>
                                @endif
                            </td>

                            <td class="action-cell">
                                <button type="button" class="detail-tugas-btn"
                                    data-detail="{{ json_encode($detail) }}"
                                    aria-label="Lihat detail {{ $p->uraian_pekerjaan }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:24px">
                                @if ($q !== '' || $status !== 'semua' || $jumlahFilter > 0)
                                    Tidak ada tugas yang cocok dengan pencarian atau filter.
                                @else
                                    Belum ada tugas yang diberikan kepada Anda.
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

    {{-- MODAL DETAIL JOBDESK --}}
    <div class="modal-overlay" id="jobdeskOverlay"></div>

    <div class="jobdesk-modal" id="jobdeskModal" role="dialog" aria-modal="true"
        aria-labelledby="jobdeskTitle" aria-hidden="true">
        <div class="jobdesk-head">
            <h2 id="jobdeskTitle">Detail Tugas &amp; Jobdesk</h2>
            <button type="button" class="jobdesk-close" id="jobdeskClose" aria-label="Tutup">&times;</button>
        </div>

        <div class="jobdesk-box">
            <span>Uraian Pekerjaan (Jobdesk)</span>
            <strong id="jdUraian"></strong>
        </div>

        <div class="jobdesk-grid">
            <div class="jobdesk-item">
                <span>Koordinator</span>
                <strong id="jdKoordinator"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Status Pelaksanaan</span>
                <strong id="jdWaktu"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Periode Pelaksanaan</span>
                <strong id="jdPeriode"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Target Volume</span>
                <strong id="jdVolume"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Harga Satuan</span>
                <strong id="jdTarif"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Total Honor</span>
                <strong id="jdHonor"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Informasi Anggaran</span>
                <strong id="jdAnggaran"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Paket Data / Pulsa</span>
                <strong id="jdPulsa"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Kontrak &amp; Verifikasi</span>
                <strong id="jdKontrak"></strong>
            </div>
            <div class="jobdesk-item">
                <span>Status Pembayaran</span>
                <strong id="jdPembayaran"></strong>
            </div>
        </div>

        <div class="jobdesk-footer">
            <button type="button" class="btn-apply" id="jobdeskTutup">Tutup</button>
        </div>
    </div>

    {{-- PANEL FILTER --}}
    <div class="filter-overlay" id="filterOverlay"></div>

    <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Tugas" aria-hidden="true">
        <form method="GET" action="{{ route('mitra.tugas') }}" id="filterForm" class="filter-form">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="honor_mode" value="rentang" id="honorMode">

            <div class="filter-body">
                <h2 class="filter-title">Filter Tugas</h2>

                <x-admin.filter-tugas judul="Periode Kontrak" placeholder="Cari Bulan / Tahun" nama="periode"
                    :banyak="false" :opsi="$daftarPeriode"
                    :dipilih="request()->filled('periode') ? [request('periode')] : []" />

                <x-admin.filter-tugas judul="Pelaksanaan" placeholder="Cari Status" nama="waktu"
                    :banyak="false" :opsi="$opsiWaktu"
                    :dipilih="request()->filled('waktu') ? [request('waktu')] : []" />

                <x-admin.filter-tugas judul="Koordinator" placeholder="Cari Koordinator" nama="koordinator"
                    :banyak="false"
                    :opsi="collect($daftarKoordinator)->map(fn($k) => ['v' => $k->id, 't' => $k->name])"
                    :dipilih="request()->filled('koordinator') ? [request('koordinator')] : []" />

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

    @push('scripts')
        <script>
            (function() {
                const modal = document.getElementById('jobdeskModal');
                const overlay = document.getElementById('jobdeskOverlay');
                const tbody = document.getElementById('tugasBody');

                function isi(id, teks) {
                    document.getElementById(id).textContent = teks || '-';
                }

                function buka(data) {
                    isi('jdUraian', data.uraian);
                    isi('jdKoordinator', data.koordinator);
                    isi('jdWaktu', data.waktu);
                    isi('jdPeriode', data.periode);
                    isi('jdVolume', data.volume);
                    isi('jdTarif', data.tarif);
                    isi('jdHonor', data.honor);
                    isi('jdAnggaran', data.anggaran);
                    isi('jdPulsa', data.pulsa);
                    isi('jdKontrak', data.kontrak);
                    isi('jdPembayaran', data.pembayaran);

                    modal.classList.add('open');
                    overlay.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                }

                function tutup() {
                    modal.classList.remove('open');
                    overlay.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                }

                tbody.addEventListener('click', e => {
                    const btn = e.target.closest('.detail-tugas-btn');
                    if (!btn) return;
                    try {
                        buka(JSON.parse(btn.dataset.detail));
                    } catch (err) {}
                });

                overlay.addEventListener('click', tutup);
                document.getElementById('jobdeskClose').addEventListener('click', tutup);
                document.getElementById('jobdeskTutup').addEventListener('click', tutup);
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') tutup();
                });
            })();
        </script>
    @endpush
</x-mitra.main>
