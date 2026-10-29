<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
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
    </svg>

    <div class="app" id="app">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <img class="long" src="{{ asset('images/Logo Bakti Mitra Long.png') }}" alt="Bakti Mitra">
                <img class="short" src="{{ asset('images/Logo Bakti Mitra Short.png')}}" alt="Bakti Mitra">
            </div>
            <nav class="menu">
                <a href="#" class="active" title="Dashboard"><svg>
                        <use href="#i-home" />
                    </svg><span class="label">Dashboard</span></a>
                <a href="#" title="Mitra"><svg>
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
            <header class="topbar">
                <h1>Dashboard</h1>
                {{-- <button class="pill">Bulan <svg>
                        <use href="#i-down" />
                    </svg></button> --}}
                <label class="search"><svg>
                        <use href="#i-search" />
                    </svg><input placeholder="Apa yang perlu dicari..."></label>
                <div class="user"><span class="bell"><svg>
                            <use href="#i-bell" />
                        </svg></span>Alyssa<div class="avatar"></div>
                </div>
            </header>

            <section class="stats">
                <div class="card hero">
                    <h2><svg>
                            <use href="#i-ring" />
                        </svg>Penyelesaian Tugas Mitra</h2>
                    <p class="num" style="font-size:34px;font-weight:600;margin:10px 0">32 %</p>
                    <div class="progress"><span></span></div>
                </div>
                <div class="card stat">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Total Mitra</h2>
                    <p class="num">123</p><span class="badge lime">120 Mitra Aktif</span>
                </div>
                <div class="card stat">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Total Koor</h2>
                    <p class="num">13</p><span class="badge">2 Aktif</span>
                </div>
                <div class="card stat">
                    <h2><svg>
                            <use href="#i-cal" />
                        </svg>Tanggal</h2>
                    <p class="num">15</p><span class="badge">2 Tugas</span>
                </div>
            </section>

            <section class="card">
                <div class="chart-head">
                    <h2><svg>
                            <use href="#i-chart" />
                        </svg>Progres Mitra</h2>
                    <button class="pill">Semua <svg>
                            <use href="#i-down" />
                        </svg></button>
                </div>
                <div class="chart">
                    <div class="axis"><span>10</span><span>20</span><span>30</span><span>40</span></div>
                    <div class="bar" style="--t:90%"><i style="--v:62%"></i></div>
                    <div class="bar" style="--t:90%"><i style="--v:36%"></i></div>
                    <div class="bar top" style="--t:90%"><i style="--v:82%"></i></div>
                    <div class="bar" style="--t:90%"><i style="--v:78%"></i></div>
                    <div class="bar top" style="--t:95%"><i style="--v:92%"></i></div>
                    <div class="bar" style="--t:90%"><i style="--v:78%"></i></div>
                    <div class="bar" style="--t:90%"><i style="--v:58%"></i></div>
                    <div class="bar top" style="--t:90%"><i style="--v:78%"></i></div>
                    <div class="bar" style="--t:95%"><i style="--v:20%"></i></div>
                    <div class="bar" style="--t:90%"><i style="--v:45%"></i></div>
                </div>
                <div class="labels">
                    <span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span>
                    <span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span>
                </div>
            </section>

            <section class="bottom">
                <div class="card">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Status Mitra</h2>
                    <ul class="list">
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge lime">Aktif</span></li>
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge">Tidak Aktif</span></li>
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge lime">Aktif</span></li>
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge lime">Aktif</span></li>
                    </ul>
                </div>
                <div class="card">
                    <h2><svg>
                            <use href="#i-list" />
                        </svg>Aktivitas Penugasan</h2>
                    <ul class="list">
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge">2
                                Mitra</span></li>
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge">2
                                Mitra</span></li>
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge dark">10
                                Mitra</span></li>
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge">2
                                Mitra</span></li>
                    </ul>
                </div>
            </section>
        </main>
    </div>
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
