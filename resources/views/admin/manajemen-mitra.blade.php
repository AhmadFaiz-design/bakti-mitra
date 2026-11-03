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
                        Penyelesaian Tugas Mitra
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
                <label class="mitra-search">
                    <svg>
                        <use href="#i-search" />
                    </svg>
                    <input id="searchMitra" type="search" placeholder="Cari nama mitra atau lainnya..."
                        autocomplete="off">
                </label>

                <button type="button" class="outline-btn" id="exportMitra">
                    <svg>
                        <use href="#i-download" />
                    </svg>
                    Export
                </button>

                <div class="status-switch" aria-label="Filter status mitra">
                    <button type="button" class="active" data-status="active" aria-pressed="true">
                        Active
                    </button>

                    <button type="button" data-status="inactive" aria-pressed="false">
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
                        <button type="button" data-sort="name">
                            Nama A–Z
                        </button>
                        <button type="button" data-sort="honor-desc">
                            Honor tertinggi
                        </button>
                        <button type="button" data-sort="honor-asc">
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

                                    <td class="id-cell">{{ $mitra->user->username }}</td>

                                    <td class="id-cell">{{ $mitra->nik }}</td>

                                    <td class="honor-cell">
                                        Rp {{ number_format($mitra->pekerjaan_sum_jumlah ?? 0, 0, ',', '.') }}
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
                                        Belum ada data mitra.
                                        <a href="{{ route('admin.mitra.import') }}">Import dari Excel</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span id="mitraCount">Menampilkan data mitra</span>
                    <span>Data dari database</span>
                </div>
            </section>
        </main>

    </div>
    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">
        @csrf
    </form>
    <script>
        /* Toggle sidebar panjang <-> pendek */
        const app = document.getElementById('app');
        const btn = document.getElementById('toggle');

        function setCollapsed(state) {
            app.classList.toggle('collapsed', state);
            btn.setAttribute('aria-expanded', String(!state));
            btn.setAttribute('aria-label', state ? 'Perbesar sidebar' : 'Perkecil sidebar');
            document.getElementById('toggleLabel').textContent = state ? 'Perbesar' : 'Perkecil';
            try {
                localStorage.setItem('sidebarCollapsed', state ? '1' : '0');
            } catch (e) {}
        }

        btn.addEventListener('click', () => setCollapsed(!app.classList.contains('collapsed')));

        /* Ingat pilihan terakhir pengguna */
        try {
            if (localStorage.getItem('sidebarCollapsed') === '1') setCollapsed(true);
        } catch (e) {}
    </script>
</body>

</html>
