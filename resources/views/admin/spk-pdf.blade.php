<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK - {{ $user?->name ?? 'Mitra' }}</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #111827;
            margin: 24px 28px;
        }
        .kop {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .kop h1 {
            margin: 0;
            font-size: 15px;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        .kop p {
            margin: 4px 0 0;
            font-size: 11px;
        }
        .meta {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 3px 4px;
            vertical-align: top;
        }
        .meta td.lbl {
            width: 150px;
            font-weight: bold;
        }
        .tbl {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        .tbl th,
        .tbl td {
            border: 1px solid #374151;
            padding: 6px 7px;
            vertical-align: top;
            font-size: 10px;
        }
        .tbl th {
            background: #f3f4f6;
            text-align: center;
            font-weight: bold;
        }
        .right {
            text-align: right;
        }
        .center {
            text-align: center;
        }
        .terbilang {
            margin: 6px 0 14px;
            font-style: italic;
        }
        .ttd {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }
        .ttd td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .ttd-ruang {
            height: 64px;
        }
    </style>
</head>
<body>
    <div class="kop">
        <h1>Surat Perjanjian Kerja (SPK) Mitra Statistik</h1>
        <p>Nomor: <strong>{{ $kontrak->nomor_kontrak ?: '(Belum Bernomor)' }}</strong></p>
        <p>Periode: {{ $kontrak->periode_label }}</p>
    </div>

    <p>
        Pada hari ini{{ $kontrak->tanggal_spk ? ', tanggal ' . $kontrak->tanggal_spk->locale('id')->translatedFormat('d F Y') : '' }},
        dilaksanakan perjanjian penugasan pekerjaan mitra statistik dengan rincian sebagai berikut:
    </p>

    <table class="meta">
        <tr>
            <td class="lbl">Nama Mitra</td>
            <td>: {{ $user?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Sobat ID</td>
            <td>: {{ $user?->username ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Wilayah Tugas</td>
            <td>: {{ $mitra?->wilayah ?: '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">No. Urut Kontrak</td>
            <td>: {{ $kontrak->nomor_urut_kontrak ?: '-' }}</td>
        </tr>
    </table>

    <table class="tbl">
        <thead>
            <tr>
                <th style="width:26px">No</th>
                <th>Uraian Pekerjaan</th>
                <th style="width:85px">RO</th>
                <th style="width:95px">Koordinator</th>
                <th style="width:95px">Periode</th>
                <th style="width:70px">Volume</th>
                <th style="width:75px">Harga Satuan</th>
                <th style="width:85px">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pekerjaan as $i => $p)
                @php
                    $vol = rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',') ?: '0';
                @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $p->uraian_pekerjaan }}</td>
                    <td class="center">{{ $p->ro ?: '-' }}</td>
                    <td>{{ $p->koordinator?->name ?? '-' }}</td>
                    <td class="center">
                        {{ $p->tanggal_mulai ? $p->tanggal_mulai->format('d/m/Y') : '-' }}<br>
                        s.d. {{ $p->tanggal_selesai ? $p->tanggal_selesai->format('d/m/Y') : '-' }}
                    </td>
                    <td class="center">{{ $vol }} {{ $p->satuan }}</td>
                    <td class="right">{{ number_format((int) $p->harga_satuan, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format((int) $p->jumlah, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="7" class="right">Total Nilai Kontrak</th>
                <th class="right">Rp {{ number_format($totalHonor, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <p class="terbilang">Terbilang: <strong>{{ $terbilang }}</strong></p>

    <p>
        Demikian Surat Perjanjian Kerja ini dibuat untuk dilaksanakan dengan penuh tanggung jawab sesuai ketentuan yang berlaku.
    </p>

    <table class="ttd">
        <tr>
            <td>
                PIHAK PERTAMA<br>
                Pejabat Pembuat Komitmen
                <div class="ttd-ruang"></div>
                <strong>( ........................................ )</strong>
            </td>
            <td>
                PIHAK KEDUA<br>
                Mitra Statistik
                <div class="ttd-ruang"></div>
                <strong>{{ $user?->name ?? '........................................' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
