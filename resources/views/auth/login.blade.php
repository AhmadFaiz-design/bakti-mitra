<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Masuk – Bakti Mitra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- Terapkan tema tersimpan sebelum render agar tidak berkedip --}}
    <script>
        (function() {
            try {
                var saved = localStorage.getItem('bm-theme');
                var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <style>
        /* ===== GANTI WARNA DI SINI ===== */
        :root {
            --primary: #8A79F1;
            --primary-dark: #5B49C9;
            --bg: #ffffff;
            --card: #ffffff;
            --field: #ffffff;
            --text: #0f0f14;
            --muted: #55556b;
            --border: transparent;
            --error: #b3261e;
            --shadow: 0 30px 60px rgba(0, 0, 0, .16);
            --toggle-bg: #D9D9D9;
            --toggle-fg: #0f0f14;

            /* gradasi bawah halaman — mode terang */
            --g-base: #f1eefb;
            --g1: #C9A7FF;
            /* violet */
            --g2: #FF9ECD;
            /* pink */
            --g3: #FFD84D;
            /* kuning */
            --g4: #D6F55B;
            /* lime */
            --g5: #5EE0E6;
            /* aqua */
            --g-opacity: .9;

            color-scheme: light;
            box-sizing: border-box;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #151517;
            }
        }

        :root[data-theme="dark"] {
            --primary: #9a8bff;
            --primary-dark: #7a69e6;
            --bg: #161618;
            --card: #232326;
            --field: #2e2e33;
            --text: #f1f1f5;
            --muted: #a3a3b5;
            --error: #f2b8b5;
            --shadow: 0 30px 60px rgba(0, 0, 0, .55);
            --toggle-bg: #2b2b30;
            --toggle-fg: #f1f1f5;

            /* gradasi bawah halaman — mode malam */
            --g-base: #0c0c14;
            --g1: #6a4fa8;
            /* violet */
            --g2: #9a4470;
            /* pink */
            --g3: #9a8020;
            /* kuning */
            --g4: #6e8a22;
            /* lime */
            --g5: #1f8087;
            /* aqua */
            --g-opacity: 1;

            color-scheme: dark;
        }

        html {
            scroll-padding-top: env(safe-area-inset-top, 0px);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            background: var(--bg);
            color: var(--text);
            font-family: "Poppins", "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
        }

        /* ===== Gradasi bawah halaman ===== */
        .glow {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 42vh;
            z-index: 0;
            pointer-events: none;
            opacity: var(--g-opacity);
            background:
                radial-gradient(ellipse 34% 90% at 6% 100%, var(--g1) 0%, transparent 70%),
                radial-gradient(ellipse 30% 85% at 30% 105%, var(--g2) 0%, transparent 70%),
                radial-gradient(ellipse 28% 80% at 55% 105%, var(--g3) 0%, transparent 70%),
                radial-gradient(ellipse 24% 80% at 74% 105%, var(--g4) 0%, transparent 70%),
                radial-gradient(ellipse 30% 95% at 98% 100%, var(--g5) 0%, transparent 70%),
                linear-gradient(to top, var(--g-base) 0%, transparent 85%);
            filter: blur(18px);
            -webkit-mask-image: linear-gradient(to top, #000 35%, transparent 100%);
            mask-image: linear-gradient(to top, #000 35%, transparent 100%);
        }

        /* ===== Tombol mode malam (pojok kiri atas) ===== */
        .theme-toggle {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
            width: 106px;
            height: 106px;
            padding: 0 0 18px 18px;
            border: 0;
            border-radius: 0 0 100% 0;
            background: var(--color-black);
            color: var(--toggle-fg);
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            padding: 24px 0 0 26px;
        }

        .theme-toggle svg {
            width: 42px;
            height: 42px;
        }

        .theme-toggle .sun {
            display: none;
        }

        [data-theme="dark"] .theme-toggle .moon {
            display: none;
        }

        [data-theme="dark"] .theme-toggle .sun {
            display: block;
        }

        .theme-toggle:focus-visible,
        .btn:focus-visible,
        .eye:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 3px;
        }

        /* ===== Layout ===== */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 40px;
            padding: 48px 6vw;
            max-width: 1440px;
            margin: 0 auto;
        }

        .welcome {
            font-size: clamp(32px, 4.4vw, 62px);
            line-height: 1.55;
            font-weight: 400;
            margin: 0;
        }

        .welcome strong {
            font-weight: 600;
        }

        .card {
            width: 100%;
            max-width: 560px;
            justify-self: center;
            background: var(--color-white);
            border-radius: 40px;
            padding: 48px 56px 56px;
            box-shadow: var(--shadow);
        }

        /* ===== Logo ===== */
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 64px;
            height: 48px;
        }

        .mark {
            width: 40px;
            height: 48px;
            flex: none;
        }

        .brand-name {
            font-weight: 600;
            font-size: 18px;
            line-height: 1.1;
        }

        .brand-tag {
            font-size: 7px;
            line-height: 1.2;
            max-width: 150px;
            margin-top: 2px;
            color: var(--text);
        }

        /* ===== Form ===== */
        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            margin-bottom: 10px;
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            height: 68px;
            padding: 0 22px;
            font: inherit;
            font-size: 16px;
            color: var(--text);
            background: var(--field);
            border: 2px solid var(--border);
            border-radius: 16px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 25%, transparent);
        }

        .pw {
            position: relative;
        }

        .pw input {
            padding-right: 64px;
        }

        .eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            background: none;
            border: 0;
            border-radius: 10px;
            color: var(--text);
            cursor: pointer;
        }

        .eye svg {
            width: 28px;
            height: 28px;
        }

        .eye .off {
            display: none;
        }

        .eye.on .off {
            display: block;
        }

        .eye.on .shown {
            display: none;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            margin: 4px 0 52px;
            cursor: pointer;
        }

        .remember input {
            appearance: none;
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            margin: 0;
            background: var(--field);
            border-radius: 4px;
            display: grid;
            place-items: center;
            cursor: pointer;
        }

        .remember input::after {
            content: "";
            width: 12px;
            height: 7px;
            border-left: 3px solid var(--primary-dark);
            border-bottom: 3px solid var(--primary-dark);
            transform: rotate(-45deg) translate(1px, -1px) scale(0);
            transition: transform .12s;
        }

        .remember input:checked::after {
            transform: rotate(-45deg) translate(1px, -1px) scale(1);
        }

        .remember input:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }

        .btn {
            width: 100%;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font: inherit;
            font-size: 16px;
            font-weight: 500;
            color: var(--text);
            background: var(--field);
            border: 0;
            border-radius: 16px;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .btn svg {
            width: 18px;
            height: 18px;
            transition: transform .15s;
        }

        .btn:hover {
            background: var(--primary);
            color: #fff;
        }

        .btn:hover svg {
            transform: translateX(3px);
        }

        .msg {
            margin-bottom: 18px;
            padding: 12px 16px;
            font-size: 13px;
            color: var(--error);
            background: color-mix(in srgb, var(--error) 10%, var(--field));
            border-radius: 12px;
        }

        .foot {
            margin: 22px 0 0;
            font-size: 12px;
            text-align: center;
            color: var(--muted);
        }

        /* ===== Transisi mode malam: menyapu dari ujung kiri atas, ease-in-out ===== */
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none;
            mix-blend-mode: normal;
        }

        ::view-transition-new(root) {
            z-index: 2;
        }

        ::view-transition-old(root) {
            z-index: 1;
        }

        /* Cadangan untuk browser tanpa View Transitions */
        body,
        .card,
        input,
        .btn,
        .glow,
        .theme-toggle {
            transition: background-color .6s ease-in-out, color .6s ease-in-out, opacity .6s ease-in-out, box-shadow .6s ease-in-out;
        }

        @media (max-width: 900px) {
            .page {
                grid-template-columns: 1fr;
                padding: 130px 20px 56px;
                gap: 28px;
            }

            .welcome {
                font-size: clamp(28px, 7vw, 40px);
            }

            .card {
                padding: 36px 24px 40px;
                border-radius: 32px;
            }

            .brand {
                margin-bottom: 40px;
            }

            .theme-toggle {
                width: 84px;
                height: 84px;
                padding: 18px 0 0 20px;
            }

            .theme-toggle svg {
                width: 34px;
                height: 34px;
            }

            .glow {
                height: 32vh;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            ::view-transition-old(root),
            ::view-transition-new(root) {
                transition: none !important;
                animation: none !important;
            }
        }

        /* ===== Perapihan: logo, field, tombol ===== */
        :root {
            --card: #ECEAF5;
            --border: rgba(0, 0, 0, .08);
        }

        :root[data-theme="dark"] {
            --border: rgba(255, 255, 255, .08);
        }

        .card {
            max-width: 460px;
            padding: 36px 40px 36px;
            border-radius: 32px;
        }

        /* Logo */
        .brand {
            margin-bottom: 36px;
        }

        .logo {
            display: block;
            height: 44px;
            width: auto;
            max-width: 100%;
        }

        /* Logo putih → jadi hitam di mode terang */
        [data-theme="light"] .logo {
            filter: invert(1);
        }

        /* Form */
        .field {
            margin-bottom: 16px;
        }

        label {
            font-size: 13px;
            margin-bottom: 8px;
        }

        input[type=text],
        input[type=password] {
            height: 48px;
            padding: 0 16px;
            font-size: 15px;
            border-width: 1px;
            border-radius: 12px;
        }

        .pw input {
            padding-right: 52px;
        }

        .eye {
            right: 8px;
            width: 36px;
            height: 36px;
        }

        .eye svg {
            width: 22px;
            height: 22px;
        }

        .remember {
            font-size: 13px;
            gap: 10px;
            margin: 4px 0 28px;
        }

        .remember input {
            width: 20px;
            height: 20px;
            border: 1px solid var(--border);
        }

        .btn {
            height: 48px;
            font-size: 15px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .foot {
            margin-top: 18px;
        }

        @media (max-width: 900px) {
            .card {
                padding: 28px 20px 28px;
            }

            .logo {
                height: 38px;
            }
        }
    </style>
</head>

<body>
    <div class="glow" aria-hidden="true"></div>

    <button class="theme-toggle" id="theme" type="button" aria-label="Ubah ke mode malam">
        <svg class="moon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M21 14.5A9 9 0 0 1 9.5 3a.6.6 0 0 0-.8-.7A10 10 0 1 0 21.7 15.3a.6.6 0 0 0-.7-.8Z" />
        </svg>
        <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4.5" fill="currentColor" />
            <path
                d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8" />
        </svg>
    </button>

    <div class="page">
        <h1 class="welcome">
            Selamat Datang,<br>
            di <strong>Bakti Mitra BPS Kota Kendari</strong>
        </h1>

        <main class="card">
            <div class="brand">
                <img src="{{ asset('images/Logo Bakti Mitra Long Putih.png') }}" alt="Bakti Mitra" class="logo">
            </div>

            <form method="POST" action="{{ route('login.proses') }}">
                @csrf

                @if ($errors->any())
                    <div class="msg" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="field">
                    <label for="u">Sobat ID atau NIP</label>
                    <input id="u" name="username" type="text" value="{{ old('username') }}"
                        autocomplete="username" required autofocus>
                </div>

                <div class="field">
                    <label for="p">Password</label>
                    <div class="pw">
                        <input id="p" name="password" type="password" autocomplete="current-password" required>
                        <button type="button" class="eye" id="t" aria-label="Tampilkan password">
                            <svg class="shown" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="2.500" />
                            </svg>
                            <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="2.500" />
                                <path d="M4 4l16 16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    Ingat Saya
                </label>

                <button class="btn" type="submit">
                    Login
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </form>

            <p class="foot">Akun dibuat oleh admin. Hubungi admin jika belum punya akun.</p>
        </main>
    </div>

    <script>
        /* ===== Lihat / sembunyikan password ===== */
        const pw = document.getElementById('p');
        const eye = document.getElementById('t');
        eye.addEventListener('click', () => {
            const show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            eye.classList.toggle('on', show);
            eye.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        });

        /* ===== Mode malam: menyapu dari pojok kiri atas, ease-in-out ===== */
        const root = document.documentElement;
        const btn = document.getElementById('theme');

        function applyTheme(dark) {
            root.setAttribute('data-theme', dark ? 'dark' : 'light');
            btn.setAttribute('aria-label', dark ? 'Ubah ke mode terang' : 'Ubah ke mode malam');
            try {
                localStorage.setItem('bm-theme', dark ? 'dark' : 'light');
            } catch (e) {}
        }

        applyTheme(root.getAttribute('data-theme') === 'dark');

        btn.addEventListener('click', () => {
            const dark = root.getAttribute('data-theme') !== 'dark';
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!document.startViewTransition || reduce) {
                applyTheme(dark);
                return;
            }

            const radius = Math.hypot(window.innerWidth, window.innerHeight);
            const transition = document.startViewTransition(() => applyTheme(dark));

            transition.ready.then(() => {
                root.animate({
                    clipPath: ['circle(0px at 0 0)', `circle(${radius}px at 0 0)`]
                }, {
                    duration: 800,
                    easing: 'ease-in-out',
                    pseudoElement: '::view-transition-new(root)'
                });
            });
        });
    </script>
</body>

</html>
