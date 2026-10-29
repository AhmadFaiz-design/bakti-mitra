<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Masuk – Bakti Mitra</title>
    <style>
        /* ===== GANTI WARNA DI SINI ===== */
        :root {
            --primary: #0f6b4f;
            --primary-dark: #0a4d39;
            --accent: #e0a526;
            --bg: #f4f7f5;
            --surface: #ffffff;
            --text: #17261f;
            --muted: #5f7068;
            --border: #d5e0da;
            --error: #b3261e;
            color-scheme: light;
            box-sizing: border-box;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --primary: #2fa37c;
                --primary-dark: #258a69;
                --accent: #f0be4d;
                --bg: #0e1713;
                --surface: #16231d;
                --text: #e8f1ec;
                --muted: #9bb0a5;
                --border: #2a3d34;
                --error: #f2b8b5;
                color-scheme: dark;
            }
        }

        :root[data-theme="dark"] {
            --primary: #2fa37c;
            --primary-dark: #258a69;
            --accent: #f0be4d;
            --bg: #0e1713;
            --surface: #16231d;
            --text: #e8f1ec;
            --muted: #9bb0a5;
            --border: #2a3d34;
            --error: #f2b8b5;
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
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .card {
            width: 100%;
            max-width: 400px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .08);
        }

        .head {
            background: var(--primary);
            color: #fff;
            padding: 28px 24px;
            text-align: center;
            border-bottom: 4px solid var(--accent);
        }

        .logo {
            width: 52px;
            height: 52px;
            margin: 0 auto 12px;
            border-radius: 14px;
            background: var(--accent);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 22px;
        }

        .head h1 {
            margin: 0;
            font-size: 22px;
        }

        .head p {
            margin: 4px 0 0;
            font-size: 14px;
            opacity: .9;
        }

        .body {
            padding: 24px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .field {
            margin-bottom: 16px;
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            padding: 12px 14px;
            font-size: 15px;
            color: var(--text);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 25%, transparent);
        }

        .pw {
            position: relative;
        }

        .pw button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: 0;
            color: var(--muted);
            font-size: 13px;
            cursor: pointer;
            padding: 6px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .row label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 400;
            margin: 0;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 13px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            transition: background .15s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .msg {
            margin-top: 14px;
            font-size: 13px;
            text-align: center;
            min-height: 18px;
            color: var(--error);
        }

        .foot {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            padding: 0 24px 22px;
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="head">
            <div class="logo">BM</div>
            <h1>Bakti Mitra</h1>
            <p>Silakan masuk ke akun Anda</p>
        </div>
        <div class="body">
            <form method="POST" action="{{ route('login.proses') }}">
                @csrf

                @if ($errors->any())
                    <div class="msg" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="field">
                    <label for="u">NIP atau Sobat ID</label>
                    <input id="u" name="username" type="text" value="{{ old('username') }}"
                        autocomplete="username" placeholder="Masukkan NIP atau Sobat ID" required autofocus>
                </div>
                <div class="field">
                    <label for="p">Kata sandi</label>
                    <div class="pw">
                        <input id="p" name="password" type="password" autocomplete="current-password"
                            placeholder="••••••••" required>
                        <button type="button" id="t">Lihat</button>
                    </div>
                </div>
                <div class="row">
                    <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                </div>
                <button class="btn" type="submit">Masuk</button>
            </form>
        </div>
        <div class="foot">Akun dibuat oleh admin. Hubungi admin jika belum punya akun.</div>
    </main>
    <script>
        const p = document.getElementById('p'),
            t = document.getElementById('t');
        t.addEventListener('click', () => {
            const show = p.type === 'password';
            p.type = show ? 'text' : 'password';
            t.textContent = show ? 'Sembunyikan' : 'Lihat';
        });
    </script>
</body>

</html>
