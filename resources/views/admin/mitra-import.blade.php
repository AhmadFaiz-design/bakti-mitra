<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Mitra – Bakti Mitra</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7f5;
            color: #17261f;
            font-family: "Segoe UI", system-ui, Roboto, Arial, sans-serif;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 24px;
            background: #0f6b4f;
            color: #fff;
            border-bottom: 4px solid #e0a526;
        }

        .topbar h1 {
            margin: 0;
            font-size: 18px;
        }

        .topbar a {
            color: #fff;
            font-size: 14px;
        }

        main {
            max-width: 900px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #d5e0da;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 17px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #d5e0da;
        }

        th {
            background: #f4f7f5;
        }

        code {
            background: #f4f7f5;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .btn {
            padding: 10px 18px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            background: #0f6b4f;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn:hover {
            background: #0a4d39;
        }

        .error {
            color: #b3261e;
            font-size: 14px;
            margin-top: 8px;
        }

        .warn {
            background: #fff7e0;
            border: 1px solid #e0a526;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .ok {
            color: #0f6b4f;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header class="topbar">
        <h1>Import Mitra</h1>
        <a href="{{ route('admin.dashboard') }}">← Kembali ke Dashboard</a>
    </header>

    <main>
        <section class="card">
            <h2>Unggah file Excel</h2>
            <p style="font-size:14px">
                Baris pertama harus berisi judul kolom:
                <code>nama</code> <code>sobat_id</code> <code>nik</code>
                <code>alamat</code> <code>pekerjaan</code> <code>jenis_kelamin</code> <code>wilayah</code>.
                Kolom <code>nama</code>, <code>sobat_id</code>, dan <code>nik</code> wajib diisi.
                Format kolom <code>sobat_id</code> dan <code>nik</code> harus <b>Text</b>.
            </p>

            <form method="POST" action="{{ route('admin.mitra.import.proses') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" required>
                <button type="submit" class="btn">Import</button>
                @error('file')
                    <div class="error">{{ $message }}</div>
                @enderror
            </form>
        </section>

        @if (session('hasil'))
            @php($hasil = session('hasil'))

            <section class="card">
                <h2 class="ok">{{ count($hasil['berhasil']) }} akun berhasil dibuat</h2>

                @if (count($hasil['berhasil']))
                    <div class="warn">
                        Password awal setiap akun sama dengan Sobat ID-nya.
                    </div>
                    <table>
                        <tr>
                            <th>Nama</th>
                            <th>Username (Sobat ID)</th>
                            <th>Password</th>
                        </tr>
                        @foreach ($hasil['berhasil'] as [$nama, $username, $password])
                            <tr>
                                <td>{{ $nama }}</td>
                                <td>{{ $username }}</td>
                                <td><code>{{ $password }}</code></td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </section>

            @if (count($hasil['gagal']))
                <section class="card">
                    <h2 style="color:#b3261e">{{ count($hasil['gagal']) }} baris dilewati</h2>
                    <table>
                        <tr>
                            <th>Baris Excel</th>
                            <th>Nama</th>
                            <th>Alasan</th>
                        </tr>
                        @foreach ($hasil['gagal'] as [$baris, $nama, $alasan])
                            <tr>
                                <td>{{ $baris }}</td>
                                <td>{{ $nama }}</td>
                                <td>{{ $alasan }}</td>
                            </tr>
                        @endforeach
                    </table>
                </section>
            @endif
        @endif
    </main>
</body>

</html>
