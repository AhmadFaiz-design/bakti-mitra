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
                <section class="stats-mitra stats-mitra-5">
                    <div class="card hero">
                        <h2>
                            <svg>
                                <use href="#i-ring" />
                            </svg>
                            Progres Mitra Bertugas
                        </h2>
                        <p class="num hijau">{{ $persen }}%</p>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-user" />
                            </svg>
                            Total Mitra
                        </h2>
                        <p class="num">{{ $total }}</p>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-user" />
                            </svg>
                            Mitra Tersedia
                        </h2>
                        <p class="num">{{ $tersedia }}</p>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-user" />
                            </svg>
                            Mitra Bertugas
                        </h2>
                        <p class="num">{{ $bertugas }}</p>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-user" />
                            </svg>
                            Mitra Penuh
                        </h2>
                        <p class="num">{{ $penuh }}</p>
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

                    <x-admin.status-dropdown :options="[
                        'semua' => 'Semua',
                        'tersedia' => 'Tersedia',
                        'bertugas' => 'Bertugas',
                        'penuh' => 'Penuh',
                    ]" :current="$status" label="Status" />

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

                    <x-admin.bulk-delete :action="route('admin.mitra.hapus-massal')" body="mitraTableBody" noun="mitra"
                        catatan="Akun yang sudah memiliki data kontrak tidak akan dihapus." />

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
                                                value="{{ $mitra->id }}"
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
                                            @php $st = $mitra->status_ketersediaan; @endphp
                                            <span class="status-badge {{ $st['key'] }}">{{ $st['label'] }}</span>
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
                                                data-wilayah="{{ $mitra->wilayah }}"
                                                data-edit-url="{{ $urlEdit }}"
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
                                {{-- << dan < hanya tampil jika bukan halaman pertama --}}
                                @unless ($daftarMitra->onFirstPage())
                                    <a class="outline-btn" style="text-decoration:none"&lt;
                                        href="{{ $daftarMitra->url(1) }}" aria-label="Halaman pertama"
                                        title="Halaman pertama">&lt;&lt;</a>
                                    <a class="outline-btn" style="text-decoration:none"
                                        href="{{ $daftarMitra->previousPageUrl() }}" aria-label="Halaman sebelumnya"
                                        title="Halaman sebelumnya">&lt;</a>
                                @endunless

                                <span>Halaman {{ $daftarMitra->currentPage() }} dari
                                    {{ $daftarMitra->lastPage() }}</span>

                                {{-- > dan >> hanya tampil jika bukan halaman terakhir --}}
                                @if ($daftarMitra->hasMorePages())
                                    <a class="outline-btn" style="text-decoration:none"
                                        href="{{ $daftarMitra->nextPageUrl() }}" aria-label="Halaman berikutnya"
                                        title="Halaman berikutnya">&gt;</a>
                                    <a class="outline-btn" style="text-decoration:none"
                                        href="{{ $daftarMitra->url($daftarMitra->lastPage()) }}"
                                        aria-label="Halaman terakhir" title="Halaman terakhir">&gt;&gt;</a>
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

                <aside class="filter-panel" id="filterPanel" role="dialog" aria-label="Filter Mitra"
                    aria-hidden="true">
                    @php
                        $opsiWilayah = $daftarWilayah ?? ['Kota Kendari', 'Kab. Konawe Kepulauan'];
                        $opsiTugas = $daftarTugas ?? ['Susenas', 'Seruti'];

                        $parseRp = fn($v) => (int) preg_replace('/\D/', '', (string) $v);
                        $modeRentang = request('honor_mode') === 'rentang';
                        $honorDari =
                            $modeRentang && request()->filled('honor_dari') ? $parseRp(request('honor_dari')) : 0;
                        $honorSampai =
                            $modeRentang && request()->filled('honor_sampai')
                                ? $parseRp(request('honor_sampai'))
                                : null;
                        $honorBatas = max((int) ($honorMaks ?? 3500000), $honorDari, (int) $honorSampai);
                        $honorSampai ??= $honorBatas;
                    @endphp

                    <form method="GET" action="{{ route('admin.datamitra') }}" id="filterForm"
                        class="filter-form">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="honor_mode" value="rentang" id="honorMode">

                        <div class="filter-body">
                            <h2 class="filter-title">Filter Mitra</h2>

                            <x-admin.filter-wilayah :opsi="$opsiWilayah" :dipilih="request('wilayah')" />

                            <x-admin.filter-tugas judul="Pekerjaan" placeholder="Cari Pekerjaan" :opsi="$opsiTugas"
                                :dipilih="$tugasDipilih" />

                            <x-admin.filter-range judul="Rentang Honor" :max="$honorBatas" :dari="$honorDari"
                                :sampai="$honorSampai" nama-dari="honor_dari" nama-sampai="honor_sampai" :step="10000"
                                :rupiah="true" mode-id="honorMode" />
                        </div>

                        <div class="filter-footer">
                            <button type="button" class="filter-reset" id="filterReset">
                                <svg viewBox="0 0 24 24">
                                    <path
                                        d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5" />
                                </svg>
                                Reset
                            </button>
                            <button type="button" class="btn-cancel" id="filterCancel">Cancel</button>
                            <button type="submit" class="btn-apply">Terapkan</button>
                        </div>
                    </form>
                </aside>

                <x-admin.filter-js />
                <x-admin.export-js button="exportMitra" />

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
