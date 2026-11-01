<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bakti Mitra - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <svg width="0" height="0" style="position:absolute">
        <symbol id="i-home" viewBox="0 0 24 24">
            <path d="M3 11l9-8 9 8M5 10v10h14V10" />
        </symbol>
        <symbol id="i-user" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c1-4 4-6 8-6s7 2 8 6" />
        </symbol>
        <symbol id="i-plus" viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14" />
        </symbol>
        <symbol id="i-left" viewBox="0 0 24 24">
            <path d="M15 6l-6 6 6 6" />
        </symbol>
        <symbol id="i-down" viewBox="0 0 24 24">
            <path d="M6 9l6 6 6-6" />
        </symbol>
        <symbol id="i-search" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4-4" />
        </symbol>
        <symbol id="i-bell" viewBox="0 0 24 24">
            <path d="M6 17V11a6 6 0 0112 0v6l2 2H4zM10 21h4" />
        </symbol>
        <symbol id="i-cal" viewBox="0 0 24 24">
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M3 10h18M8 3v4M16 3v4" />
        </symbol>
        <symbol id="i-chart" viewBox="0 0 24 24">
            <path d="M3 3v18h18M7 15l4-5 3 3 5-6" />
        </symbol>
        <symbol id="i-ring" viewBox="0 0 24 24">
            <path d="M12 3a9 9 0 109 9" />
        </symbol>
        <symbol id="i-list" viewBox="0 0 24 24">
            <path d="M10 6h11M10 12h11M10 18h11M3 6l1.5 1.5L7 5M3 12l1.5 1.5L7 11M3 18l1.5 1.5L7 17" />
        </symbol>
        <symbol id="i-logout" viewBox="0 0 24 24">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
        </symbol>

        <symbol id="i-download" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M7 10l5 5 5-5" />
            <path d="M12 15V3" />
        </symbol>

        <symbol id="i-filter" viewBox="0 0 24 24">
            <path d="M4 6h16" />
            <path d="M7 12h10" />
            <path d="M10 18h4" />
        </symbol>

    </svg>

    <div class="app" id="app">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <img class="long" src="{{ asset('images/Logo Bakti Mitra Long.png') }}" alt="Bakti Mitra">
                <img class="short" src="{{ asset('images/Logo Bakti Mitra Short.png') }}" alt="Bakti Mitra">
            </div>
            <nav class="menu">
                <a href="#" title="Keluar"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg>
                        <use href="#i-logout" />
                    </svg><span class="label">Keluar</span>
                </a>
                <a href="#" title="Dashboard"><svg>
                        <use href="#i-home" />
                    </svg><span class="label">Dashboard</span></a>
                <a href="{{ route('admin.datamitra') }}" class="active" title="Mitra"><svg>
                        <use href="#i-user" />
                    </svg><span class="label">Mitra</span></a>
                <a href="#" title="Koordinator"><svg>
                        <use href="#i-user" />
                    </svg><span class="label">Koordinator</span></a>
                <div class="has-sub" id="sub">
                    <button type="button" title="Buat Akun"
                        onclick="document.getElementById('sub').classList.toggle('open');document.getElementById('subList').classList.toggle('open')">
                        <svg>
                            <use href="#i-plus" />
                        </svg><span class="label">Buat Akun</span><svg class="chev">
                            <use href="#i-down" />
                        </svg>
                    </button>
                    <div class="submenu" id="subList">
                        <a href="#" title="Buat akun Mitra"><svg>
                                <use href="#i-user" />
                            </svg><span class="label">Mitra</span></a>
                        <a href="#" title="Buat akun Koordinator"><svg>
                                <use href="#i-user" />
                            </svg><span class="label">Koordinator</span></a>
                    </div>
                </div>
            </nav>

            <button type="button" class="toggle" id="toggle" aria-label="Perkecil sidebar" aria-expanded="true"
                title="Perkecil / perbesar sidebar">
                <svg>
                    <use href="#i-left" />
                </svg><span class="label" id="toggleLabel">Perkecil</span>
            </button>
        </aside>

        <!-- KONTEN -->

        <main>
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
                        <span>Alyssa</span>
                        <div class="avatar" style="width:48px;height:48px">
                            A
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
                    <p class="num">123</p>
                </div>

                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-user" />
                        </svg>
                        Mitra Aktif
                    </h2>
                    <p class="num">13</p>
                </div>

                <div class="card stat">
                    <h2>
                        <svg>
                            <use href="#i-cal" />
                        </svg>
                        Mitra Non Aktif
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
