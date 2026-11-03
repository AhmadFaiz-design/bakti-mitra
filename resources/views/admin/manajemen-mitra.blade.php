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

                <div class="filter-wrap">
                    <button type="button" class="outline-btn" id="filterToggle" aria-expanded="false">
                        <svg>
                            <use href="#i-filter" />
                        </svg>
                        Filter
                    </button>

                    <div class="filter-menu" id="filterMenu">
                        <button type="button" data-sort="name"
                            onclick="window.location='{{ request()->fullUrlWithQuery(['sort' => 'name', 'page' => null]) }}'">
                            Nama A–Z
                        </button>
                        <button type="button" data-sort="honor-desc"
                            onclick="window.location='{{ request()->fullUrlWithQuery(['sort' => 'honor-desc', 'page' => null]) }}'">
                            Honor tertinggi
                        </button>
                        <button type="button" data-sort="honor-asc"
                            onclick="window.location='{{ request()->fullUrlWithQuery(['sort' => 'honor-asc', 'page' => null]) }}'">
                            Honor terendah
                        </button>
                    </div>
                </div>
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

                    <button type="button" class="primary-btn"
                        onclick="alert('Form tambah mitra akan dibuat pada tahap berikutnya.')">
                        <svg>
                            <use href="#i-plus" />
                        </svg>
                        Add Mitra
                    </button>
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
                                        Rp {{ number_format($mitra->pekerjaan_sum_jumlah ?? 3500000, 0, ',', '.') }}
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
                                        $tugas = ['Kambing Goreng', 'Perkedel Sapi'];
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
                                <button type="button" class="outline-btn" disabled>
                                    << /button>
                                    @else
                                        <a class="outline-btn" style="text-decoration:none"
                                            href="{{ $daftarMitra->previousPageUrl() }}">
                                            << /a>
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
                            if (url) window.location = url;
                            else alert('Halaman edit mitra belum dibuat.');
                        });

                        document.getElementById('popHapus').addEventListener('click', () => {
                            const url = aktif?.dataset.deleteUrl;
                            if (!url) {
                                alert('Fitur hapus mitra belum dibuat.');
                                return;
                            }
                            if (confirm('Hapus akun mitra "' + aktif.dataset.nama +
                                    '"? Tindakan ini tidak bisa dibatalkan.')) {
                                hapusForm.action = url;
                                hapusForm.submit();
                            }
                        });
                    })();
                </script>
            @endpush
        </x-admin.main>
