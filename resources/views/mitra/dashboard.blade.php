<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mitra – Bakti Mitra</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; background: #f4f7f5; color: #17261f;
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
        }
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 24px; background: #0f6b4f; color: #fff;
            border-bottom: 4px solid #e0a526;
        }
        .topbar h1 { margin: 0; font-size: 18px; }
        .user { display: flex; align-items: center; gap: 14px; font-size: 14px; }
        .btn-logout {
            padding: 8px 16px; font-size: 14px; font-weight: 600; color: #0f6b4f;
            background: #fff; border: 0; border-radius: 8px; cursor: pointer;
        }
        .btn-logout:hover { background: #e0a526; }
        main { padding: 24px; }
    </style>
</head>
<body>
    <header class="topbar">
        <h1>Bakti Mitra – Mitra</h1>
        <div class="user">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">Keluar</button>
            </form>
        </div>
    </header>

    <main>
        <p>Mitra ganteng</p>
    </main>
</body>
</html>
