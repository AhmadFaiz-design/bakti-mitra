<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penugasan Mitra</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1F1A3D; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #6F6A8F; margin-bottom: 12px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #8A79F1; color: #fff; text-align: left; padding: 6px 5px; font-size: 8.5px; }
        td { padding: 5px; border-bottom: 1px solid #E6E2FB; vertical-align: top; }
        tr:nth-child(even) td { background: #F8F6FF; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total td { font-weight: bold; background: #E6E2FB; border-bottom: 0; }
        .sub { color: #6F6A8F; font-size: 8px; }
    </style>
</head>
<body>
    <h1>Penugasan Mitra BPS Kota Kendari</h1>
    <div class="meta">
        Diekspor pada {{ $tanggal }} &middot; {{ $daftar->count() }} tugas<br>
        {{ implode('  |  ', $keterangan) }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:3%">No</th>
                <th style="width:15%">Mitra</th>
                <th style="width:27%">Uraian Pekerjaan</th>
                <th style="width:13%">Koordinator</th>
                <th style="width:14%">Periode</th>
                <th class="right" style="width:8%">Volume</th>
                <th class="right" style="width:11%">Honor</th>
                <th class="center" style="width:9%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftar as $i => $p)
                @php
                    $user = $p->kontrak?->mitra?->user;
                    $mulai = $p->tanggal_mulai ? \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/Y') : '-';
                    $selesai = $p->tanggal_selesai ? \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/Y') : '-';
                @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $user?->name }}<div class="sub">{{ $user?->username }}</div></td>
                    <td>{{ $p->uraian_pekerjaan }}@if ($p->ro)<div class="sub">RO: {{ $p->ro }}</div>@endif</td>
                    <td>{{ $p->koordinator?->name ?? '-' }}</td>
                    <td>{{ $mulai }}<div class="sub">s.d. {{ $selesai }}</div></td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',') }} {{ $p->satuan }}</td>
                    <td class="right">Rp {{ number_format($p->jumlah ?? 0, 0, ',', '.') }}</td>
                    <td class="center">{{ $p->honor_dibayar ? 'Sudah Dibayar' : 'Belum Dibayar' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">Tidak ada data.</td></tr>
            @endforelse

            @if ($daftar->count() > 0)
                <tr class="total">
                    <td colspan="6" class="right">Total Honor</td>
                    <td class="right">Rp {{ number_format($totalHonor, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
