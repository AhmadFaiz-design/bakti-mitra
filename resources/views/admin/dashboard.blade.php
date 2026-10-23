<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* 1. PALET WARNA: ubah di sini, seluruh halaman ikut berubah */
        :root {
            --primary: #8A79F1;
            /* ungu utama */
            --primary-dark: #5B49C9;
            /* ungu tua untuk teks di atas latar terang */
            --ink: #1F1A3D;
            /* indigo gelap: sidebar & teks */
            --bg: #F3F1FD;
            /* latar halaman (ungu sangat muda) */
            --tint: #E6E2FB;
            /* ungu muda: track bar, badge */
            --white: #fff;
            --lime: #D6F55B;
            /* aksen kontras (lawan ungu) */
            --muted: #6F6A8F;
            /* teks sekunder */
            --radius: 24px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--ink);
            padding: 20px 0px
        }

        svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex: none
        }

        /* 2. KERANGKA UTAMA: sidebar + konten */
        .app {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
            max-width: 1280px;
            margin: 0px 20px;
        }

        .sidebar {
            background: var(--ink);
            border-radius: var(--radius);
            padding: 24px 20px;
            color: var(--white);
            min-height: calc(100vh - 40px)
        }

        .logo {
            height: 64px;
            border-radius: 20px;
            background: var(--primary);
            margin-bottom: 40px
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 10px
        }

        .menu a,
        .menu button {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 11px 14px;
            border: 0;
            border-radius: 14px;
            background: transparent;
            color: #CFCAF0;
            font: 500 14px 'Poppins';
            text-decoration: none;
            cursor: pointer;
            text-align: left
        }

        .menu a:hover,
        .menu button:hover {
            background: rgba(255, 255, 255, .08)
        }

        .menu .active {
            background: var(--primary);
            color: var(--white)
        }

        .menu .chev {
            margin-left: auto;
            transition: transform .2s
        }

        .submenu {
            display: none;
            flex-direction: column;
            gap: 4px;
            padding: 6px 0 4px 14px
        }

        .submenu.open {
            display: flex
        }

        .has-sub.open .chev {
            transform: rotate(180deg)
        }

        /* 3. KONTEN KANAN */
        .topbar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap
        }

        .topbar h1 {
            font-size: 28px;
            font-weight: 600
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--ink);
            color: var(--white);
            border: 0;
            border-radius: 16px;
            padding: 12px 20px;
            font: 500 14px 'Poppins'
        }

        .search {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--white);
            border: 1.5px solid var(--tint);
            border-radius: 16px;
            padding: 11px 18px;
            width: min(300px, 100%);
            color: var(--muted)
        }

        .search input {
            border: 0;
            outline: 0;
            background: none;
            font: 400 14px 'Poppins';
            width: 100%
        }

        .search:focus-within {
            border-color: var(--primary)
        }

        .user {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            font-size: 14px
        }

        .bell {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--white);
            display: grid;
            place-items: center
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--lime))
        }

        /* 4. KARTU: dasar semua kotak */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px
        }

        .card h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 500
        }

        .stats {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px
        }

        .stat .num {
            font-size: 34px;
            font-weight: 600;
            margin: 10px 0
        }

        .badge {
            display: inline-block;
            background: var(--tint);
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 99px
        }

        .badge.lime {
            background: var(--lime);
            color: var(--ink)
        }

        .badge.dark {
            background: var(--ink);
            color: var(--white)
        }

        /* kartu ungu (seperti kartu hijau pada referensi) */
        .hero {
            background: var(--primary);
            color: var(--white)
        }

        .progress {
            height: 12px;
            background: rgba(255, 255, 255, .3);
            border-radius: 99px;
            overflow: hidden;
            margin-top: 6px
        }

        .progress span {
            display: block;
            height: 100%;
            width: 32%;
            background: var(--lime);
            border-radius: 99px
        }

        /* 5. GRAFIK BATANG: murni CSS */
        .chart-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px
        }

        .chart {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            height: 190px;
            border-bottom: 2px solid var(--ink);
            padding-left: 44px;
            position: relative
        }

        .axis {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 100%;
            display: flex;
            flex-direction: column-reverse;
            justify-content: space-between;
            font-size: 13px;
            color: var(--muted)
        }

        .bar {
            flex: 1;
            max-width: 38px;
            height: var(--t);
            background: var(--tint);
            border-radius: 99px 99px 0 0;
            display: flex;
            align-items: flex-end
        }

        .bar i {
            display: block;
            width: 100%;
            height: var(--v);
            background: var(--primary);
            border-radius: 99px 99px 0 0
        }

        .bar.top i {
            background: var(--ink)
        }

        .labels {
            display: flex;
            gap: 14px;
            padding-left: 44px;
            margin-top: 10px;
            font-size: 13px
        }

        .labels span {
            flex: 1;
            max-width: 38px;
            text-align: center;
            overflow: visible;
            white-space: nowrap;
            font-size: 11px
        }

        /* 6. DUA KARTU BAWAH */
        .bottom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px
        }

        .list {
            list-style: none;
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px
        }

        .list li {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px
        }

        .list .dot {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--tint)
        }

        .list .badge {
            margin-left: auto
        }

        .task {
            align-items: flex-start;
            font-size: 13px;
            line-height: 1.5
        }

        /* 7. RESPONSIF */
        @media(max-width:1000px) {
            .app {
                grid-template-columns: 1fr
            }

            .sidebar {
                min-height: 0
            }

            .stats {
                grid-template-columns: 1fr 1fr
            }

            .hero {
                grid-column: 1/-1
            }

            .bottom {
                grid-template-columns: 1fr
            }
        }
    </style>
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

    <div class="app">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo"></div>
            <nav class="menu">
                <a href="#" class="active"><svg>
                        <use href="#i-home" />
                    </svg>Dashboard</a>
                <a href="#"><svg>
                        <use href="#i-user" />
                    </svg>Mitra</a>
                <a href="#"><svg>
                        <use href="#i-user" />
                    </svg>Koordinator</a>
                <div class="has-sub" id="sub">
                    <button type="button"
                        onclick="document.getElementById('sub').classList.toggle('open');document.getElementById('subList').classList.toggle('open')">
                        <svg>
                            <use href="#i-plus" />
                        </svg>Buat Akun<svg class="chev">
                            <use href="#i-down" />
                        </svg>
                    </button>
                    <div class="submenu" id="subList">
                        <a href="#"><svg>
                                <use href="#i-user" />
                            </svg>Mitra</a>
                        <a href="#"><svg>
                                <use href="#i-user" />
                            </svg>Koordinator</a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- KONTEN -->
        <main>
            <header class="topbar">
                <h1>Dashboard</h1>
                <button class="pill">Bulan <svg>
                        <use href="#i-down" />
                    </svg></button>
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
</body>

</html>
