<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bakti Mitra - {{ $titlex ?? 'Dashboard' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body>
    {{-- Kumpulan ikon SVG --}}
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
        <aside class="sidebar">
            <div class="logo">
                <img class="long" src="{{ asset('images/Logo Bakti Mitra Long.png') }}" alt="Bakti Mitra">
                <img class="short" src="{{ asset('images/Logo Bakti Mitra Short.png') }}" alt="Bakti Mitra">
            </div>

            <nav class="menu">
                <x-admin.list-sidebar :href="route('admin.dashboard')" title="Dashboard" :active="request()->routeIs('admin.dashboard')">
                    <svg>
                        <use href="#i-home" />
                    </svg><span class="label">Dashboard</span>
                </x-admin.list-sidebar>

                <x-admin.list-sidebar :href="route('admin.datamitra')" title="Mitra" :active="request()->routeIs('admin.datamitra')">
                    <svg>
                        <use href="#i-user" />
                    </svg><span class="label">Mitra</span>
                </x-admin.list-sidebar>

                <x-admin.list-sidebar :href="route('admin.datakoor')" title="Koordinator" :active="request()->routeIs('admin.datakoor')">
                    <svg>
                        <use href="#i-user" />
                    </svg><span class="label">Koordinator</span>
                </x-admin.list-sidebar>

                <x-admin.list-sidebar :href="route('admin.tambahmitra')" title="Tambah Mitra" :active="request()->routeIs('admin.tambahmitra')">
                    <svg>
                        <use href="#i-plus" />
                    </svg><span class="label">Tambah Mitra</span>
                </x-admin.list-sidebar>

                {{-- Submenu "Buat Akun" dan tombol Keluar tetap seperti sebelumnya --}}
            </nav>

            <div class="sidebar-bottom">
                <button type="button" class="toggle logout-btn" title="Keluar"
                    onclick="document.getElementById('logout-form').submit();">
                    <svg>
                        <use href="#i-logout" />
                    </svg>
                    <span class="label">Keluar</span>
                </button>
                <button type="button" class="toggle" id="toggle" aria-label="Perkecil Sidebar" aria-expanded="true">
                    <svg>
                        <use href="#i-left" />
                    </svg>
                    <span class="label" id="toggleLabel">Perkecil</span>
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
    @stack('scripts')
</body>

</html>
