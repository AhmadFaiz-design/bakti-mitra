        <!-- KONTEN -->

        <x-admin.main>
            {{-- HEADER HALAMAN --}}
            <header class="page-heading">
                <div>
                    <h1>Koordinator BPS Kota Kendari</h1>
                    <p>Manajemen Data Koordinator BPS Kota Kendari</p>
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
                        <span>Alyssa</span>
                        <div class="avatar" style="width:48px;height:48px">
                            A
                        </div>
                    </div>
                </div>
            </header>

            {{-- KARTU STATISTIK --}}
            <section class="stats-koor">
                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-user" />
                        </svg>
                        Total Koordinator
                    </h2>
                    <p class="num">123</p>
                </div>
                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-user" />
                        </svg>
                        Koordinator Aktif
                    </h2>
                    <p class="num">13</p>
                </div>
                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-cal" />
                        </svg>
                        Koordinator Non Aktif
                    </h2>
                    <p class="num">110</p>
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
                        List Koordinator
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
                            @php
                                $mitraContoh = [
                                    [
                                        'nama' => 'Nicholas Saputra',
                                        'sobat_id' => '123412341234',
                                        'nik' => '1234123412341234',
                                        'honor' => 4500000,
                                        'status' => 'inactive',
                                    ],
                                    [
                                        'nama' => 'Andi Pratama',
                                        'sobat_id' => '123412341235',
                                        'nik' => '1234123412341235',
                                        'honor' => 2500000,
                                        'status' => 'active',
                                    ],
                                    [
                                        'nama' => 'Siti Rahma',
                                        'sobat_id' => '123412341236',
                                        'nik' => '1234123412341236',
                                        'honor' => 1800000,
                                        'status' => 'active',
                                    ],
                                    [
                                        'nama' => 'Muhammad Akbar',
                                        'sobat_id' => '123412341237',
                                        'nik' => '1234123412341237',
                                        'honor' => 3200000,
                                        'status' => 'inactive',
                                    ],
                                    [
                                        'nama' => 'Nur Aisyah',
                                        'sobat_id' => '123412341238',
                                        'nik' => '1234123412341238',
                                        'honor' => 1500000,
                                        'status' => 'active',
                                    ],
                                    [
                                        'nama' => 'Rizky Ramadhan',
                                        'sobat_id' => '123412341239',
                                        'nik' => '1234123412341239',
                                        'honor' => 2800000,
                                        'status' => 'inactive',
                                    ],
                                ];
                            @endphp

                            @foreach ($mitraContoh as $mitra)
                                <tr data-status="{{ $mitra['status'] }}" data-name="{{ strtolower($mitra['nama']) }}"
                                    data-honor="{{ $mitra['honor'] }}">

                                    <td>
                                        <input type="checkbox" class="check-mitra row-check"
                                            aria-label="Pilih {{ $mitra['nama'] }}">
                                    </td>

                                    <td>
                                        <div class="profile-avatar">
                                            {{ strtoupper(substr($mitra['nama'], 0, 1)) }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="profile-name">
                                            {{ $mitra['nama'] }}
                                        </span>
                                    </td>

                                    <td class="id-cell">
                                        {{ $mitra['sobat_id'] }}
                                    </td>

                                    <td class="id-cell">
                                        {{ $mitra['nik'] }}
                                    </td>

                                    <td class="honor-cell">
                                        Rp {{ number_format($mitra['honor'], 0, ',', '.') }}
                                    </td>

                                    <td>
                                        @if ($mitra['status'] === 'active')
                                            <span class="status-badge active">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="status-badge inactive">
                                                Tidak Aktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="action-cell">
                                        <button type="button" class="action-btn"
                                            aria-label="Aksi {{ $mitra['nama'] }}" onclick="toggleRowMenu(this)">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span id="mitraCount">Menampilkan data mitra</span>
                    <span>Data contoh — belum terhubung ke database</span>
                </div>
            </section>
        </x-admin.main>
