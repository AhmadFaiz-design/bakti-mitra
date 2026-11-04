<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mitra</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1F1A3D; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6F6A8F; margin-bottom: 14px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #8A79F1; color: #fff; text-align: left; padding: 7px 6px; font-size: 9px; }
        td { padding: 6px; border-bottom: 1px solid #E6E2FB; }
        tr:nth-child(even) td { background: #F8F6FF; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Data Mitra BPS Kota Kendari</h1>
    <div class="meta">
        Diekspor pada {{ $tanggal }} &middot; {{ $daftar->count() }} mitra<br>
        {{ implode('  |  ', $ringkasan) }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:4%">No</th>
                <th style="width:24%">Nama</th>
                <th style="width:16%">Sobat ID</th>
                <th style="width:18%">NIK</th>
                <th style="width:14%">Wilayah</th>
                <th class="right" style="width:14%">Total Honor</th>
                <th class="center" style="width:10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftar as $i => $m)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $m->user?->name }}</td>
                    <td>{{ $m->user?->username }}</td>
                    <td>{{ $m->nik }}</td>
                    <td>{{ $m->wilayah }}</td>
                    <td class="right">Rp {{ number_format($m->pekerjaan_sum_jumlah ?? 0, 0, ',', '.') }}</td>
                    <td class="center">{{ ($m->user?->is_active ?? false) ? 'Aktif' : 'Tidak Aktif' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
