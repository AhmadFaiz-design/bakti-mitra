<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bakti Mitra - {{ $titlex ?? 'Dashboard' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    {{-- Tema koordinator: dimuat setelah style.css agar menimpa warna admin --}}
    <link rel="stylesheet" href="{{ asset('css/style-koor.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="theme-koor">
    {{-- Kumpulan ikon SVG --}}
    <svg width="0" height="0" style="position:absolute">
        <symbol id="i-home" viewBox="0 0 24 24">
            <path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5.5v-6h-5v6H4a1 1 0 0 1-1-1z" />
        </symbol>
        <symbol id="i-user" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c1-4 4-6 8-6s7 2 8 6" />
        </symbol>
        <symbol id="i-chat" viewBox="0 0 24 24">
            <path d="M12 20a8 8 0 1 0-6.9-4l-1.3 3.7 3.8-1.1A8 8 0 0 0 12 20z" />
            <circle cx="9" cy="12" r=".7" fill="currentColor" stroke="none" />
            <circle cx="12" cy="12" r=".7" fill="currentColor" stroke="none" />
            <circle cx="15" cy="12" r=".7" fill="currentColor" stroke="none" />
        </symbol>
        <symbol id="i-alarm" viewBox="0 0 24 24">
            <circle cx="12" cy="13" r="7.5" />
            <path d="M12 9.5v3.8l2.5 1.5M5 4.5L3 6.5M19 4.5l2 2M10 3h4" />
        </symbol>
        <symbol id="i-pin" viewBox="0 0 24 24">
            <path d="M12 21s6-5.5 6-10.5a6 6 0 1 0-12 0C6 15.5 12 21 12 21z" />
            <circle cx="12" cy="10.5" r="2.2" />
        </symbol>
        <symbol id="i-table" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="16" rx="2.5" />
            <path d="M3 10h18M9 4v16" />
        </symbol>
        <symbol id="i-export" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M17 8l-5-5-5 5" />
            <path d="M12 3v12" />
        </symbol>
        <symbol id="i-plus-circle" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8.5v7M8.5 12h7" />
        </symbol>
        <symbol id="i-briefcase" viewBox="0 0 24 24">
            <rect x="3" y="7" width="18" height="13" rx="2.5" />
            <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            <path d="M3 12h18" />
        </symbol>
        <symbol id="i-download" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M7 10l5 5 5-5" />
            <path d="M12 15V3" />
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
        <symbol id="i-users" viewBox="0 0 24 24">
            <circle cx="9" cy="8" r="3.5" />
            <path d="M2 20c.6-3.5 3.2-5.5 7-5.5s6.4 2 7 5.5" />
            <path d="M15.5 4.8a3.5 3.5 0 010 6.4M18 14.8c2 .7 3.6 2.4 4 5.2" />
        </symbol>
        <symbol id="i-filter" viewBox="0 0 24 24">
            <path d="M4 6h16" />
            <path d="M7 12h10" />
            <path d="M10 18h4" />
        </symbol>
        <symbol id="i-cash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <line x1="2" y1="12" x2="5" y2="12" />
            <line x1="19" y1="12" x2="22" y2="12" />
            <circle cx="12" cy="12" r="2.5" />
        </symbol>
    </svg>

    <div class="app" id="app">
        <aside class="sidebar">
            <div class="logo">
                <img class="long" src="{{ asset('images/Logo Bakti Mitra Long Hitam.png') }}" alt="Bakti Mitra">
                <img class="short" src="{{ asset('images/Logo Bakti Mitra Short Hitam.png') }}" alt="Bakti Mitra">
            </div>

            {{-- Menu koordinator --}}
            <nav class="menu">
                <x-admin.list-sidebar :href="route('koordinator.dashboard')" title="Dashboard" :active="request()->routeIs('koordinator.dashboard')">
                    <svg>
                        <use href="#i-home" />
                    </svg><span class="label">Dashboard</span>
                </x-admin.list-sidebar>

                <x-admin.list-sidebar :href="route('koordinator.datamitra')" title="Penugasan" :active="request()->routeIs('koordinator.datamitra*', 'koordinator.penugasan*')">
                    <svg>
                        <use href="#i-list" />
                    </svg><span class="label">Penugasan</span>
                </x-admin.list-sidebar>
            </nav>

            @php $user = auth()->user(); @endphp

            <div class="sidebar-bottom">
                <div class="sidebar-user">
                    <div class="sidebar-user-info" title="{{ $user?->name }}">
                        <div class="sidebar-avatar">
                            {{ strtoupper(substr($user?->name ?? 'K', 0, 1)) }}
                        </div>
                        <div class="sidebar-user-meta">
                            <strong class="sidebar-user-name">{{ $user?->name ?? 'Koordinator' }}</strong>
                            <span class="sidebar-user-id">{{ ucfirst($user?->role ?? 'Koordinator') }}</span>
                        </div>
                    </div>

                    <button type="button" class="logout-btn" id="logoutBtn" title="Logout">
                        <span class="label">Logout</span>
                        <svg>
                            <use href="#i-logout" />
                        </svg>
                    </button>
                </div>

                <button type="button" class="sidebar-collapse" id="toggle" aria-label="Perkecil sidebar"
                    aria-expanded="true">
                    <span class="label" id="toggleLabel">Perkecil</span>
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="16" rx="3" />
                        <path d="M9 4v16" />
                    </svg>
                </button>
            </div>
        </aside>

        <main>
            {{ $slot }}
        </main>
    </div>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">
        @csrf
    </form>

    <script>
        /* Konfirmasi popup sebelum logout */
        document.getElementById('logoutBtn')?.addEventListener('click', async () => {
            const teks = 'Anda akan keluar dari akun ini dan perlu login kembali untuk mengakses sistem.';
            const lanjut = typeof popup === 'function' ?
                await popup({
                    jenis: 'logout',
                    judul: 'Yakin ingin logout?',
                    pesan: teks,
                    tombolOk: 'Ya, logout',
                    tombolBatal: 'Batal'
                }) :
                confirm('Yakin ingin logout?');

            if (lanjut) {
                document.getElementById('logout-form')?.submit();
            }
        });

        /* Toggle sidebar panjang <-> pendek */
        const app = document.getElementById('app');
        const btn = document.getElementById('toggle');

        function setCollapsed(state) {
            app.classList.toggle('collapsed', state);
            btn?.setAttribute('aria-expanded', String(!state));
            btn?.setAttribute('aria-label', state ? 'Perbesar sidebar' : 'Perkecil sidebar');
            try {
                localStorage.setItem('sidebarCollapsed', state ? '1' : '0');
            } catch (e) {}
        }

        btn?.addEventListener('click', () => setCollapsed(!app.classList.contains('collapsed')));

        /* Ingat pilihan terakhir pengguna */
        try {
            if (localStorage.getItem('sidebarCollapsed') === '1') setCollapsed(true);
        } catch (e) {}
    </script>

    <x-admin.notifikasi />
    <x-popup />
    @stack('scripts')
</body>

</html>
