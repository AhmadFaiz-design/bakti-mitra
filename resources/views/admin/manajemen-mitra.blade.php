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

                                    <td class="action-cell">
                                        <button type="button" class="action-btn"
                                            aria-label="Aksi {{ $mitra->user->name }}" onclick="toggleRowMenu(this)">
                                            &#8942;
                                        </button>

                                        <div class="row-action-menu">
                                            <button type="button"
                                                onclick="alert('Detail mitra akan dibuat berikutnya.')">
                                                Lihat detail
                                            </button>
                                            <button type="button"
                                                onclick="alert('Form edit mitra akan dibuat berikutnya.')">
                                                Edit mitra
                                            </button>
                                        </div>
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
                                <button type="button" class="outline-btn" disabled><</button>
                            @else
                                <a class="outline-btn" style="text-decoration:none"
                                    href="{{ $daftarMitra->previousPageUrl() }}"><</a>
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
                </script>
            @endpush
        </x-admin.main>
