<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Koordinator</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1F1A3D; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6F6A8F; margin-bottom: 14px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #8A79F1; color: #fff; text-align: left; padding: 7px 6px; font-size: 9px; }
        td { padding: 6px; border-bottom: 1px solid #E6E2FB; }
        tr:nth-child(even) td { background: #F8F6FF; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Data Koordinator BPS Kota Kendari</h1>
    <div class="meta">
        Diekspor pada {{ $tanggal }} &middot; {{ $daftar->count() }} koordinator<br>
        {{ implode('  |  ', $ringkasan) }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:5%">No</th>
                <th style="width:25%">Nama</th>
                <th style="width:20%">NIP / ID</th>
                <th style="width:22%">Email</th>
                <th class="center" style="width:10%">Pekerjaan</th>
                <th class="center" style="width:8%">Mitra</th>
                <th class="center" style="width:10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftar as $i => $k)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $k->name }}</td>
                    <td>{{ $k->username }}</td>
                    <td>{{ $k->email ?: '-' }}</td>
                    <td class="center">{{ (int) $k->jumlah_pekerjaan }}</td>
                    <td class="center">{{ (int) $k->jumlah_mitra }}</td>
                    <td class="center">{{ $k->is_active ? 'Aktif' : 'Tidak Aktif' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
