@php
    $persen = $ringkasan['total'] > 0 ? (int) round($ringkasan['dibayar'] / $ringkasan['total'] * 100) : 0;
    $urlStatus = fn ($s) => request()->fullUrlWithQuery(['status' => $s, 'page' => null]);
@endphp

<x-admin.main class="list-page">
    <style>
        .pn {
            --ungu: #8b7cf0;
            --ungu-tua: #6f5fe0;
            --ungu-muda: #e8e4fc;
            --teks: #1f1b3a;
            --redup: #6b6785;
            --garis: #e3dff5;
            --hijau-bg: #e0f3e8;
            --hijau: #1f7a4a;
            --oranye-bg: #fff1dc;
            --oranye: #a86400;
        }

        .pn svg {
            width: 20px;
            height: 20px;
            flex: none;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ---------- Header ---------- */
        .pn .page-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ---------- Kartu statistik ---------- */
        .pn .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .pn .stat {
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: 24px;
            padding: 22px 26px;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .pn .stat-judul {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 17px;
            font-weight: 600;
            color: var(--teks);
        }

        .pn .stat-nilai {
            font-size: 34px;
            font-weight: 700;
            color: var(--teks);
            line-height: 1.1;
        }

        .pn .stat-nilai.kecil {
            font-size: 26px;
        }

        .pn .stat.utama {
            background: var(--ungu);
            border-color: var(--ungu);
        }

        .pn .stat.utama .stat-judul,
        .pn .stat.utama .stat-nilai {
            color: #fff;
        }

        .pn .stat-nilai.hijau {
            color: var(--hijau);
        }

        .pn .stat-nilai.oranye {
            color: var(--oranye);
        }

        /* ---------- Toolbar ---------- */
        .pn .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
        }

        .pn .cari {
            flex: 1;
            min-width: 240px;
            display: flex;
            align-items: center;
            gap: 12px;
            height: 52px;
            padding: 0 18px;
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: 16px;
            color: var(--redup);
        }

        .pn .cari input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin: 0;
            height: auto;
            width: 100%;
            font: inherit;
            font-size: 15px;
            color: var(--teks);
        }

        .pn .tab {
            display: flex;
            gap: 4px;
            padding: 5px;
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: 16px;
        }

        .pn .tab a {
            display: inline-flex;
            align-items: center;
            height: 40px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: var(--teks);
            text-decoration: none;
            white-space: nowrap;
        }

        .pn .tab a:hover {
            background: #f3f0ff;
        }

        .pn .tab a.aktif {
            background: var(--ungu);
            color: #fff;
        }

        /* ---------- Kartu daftar ---------- */
        .pn .kartu {
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: 24px;
            padding: 28px 30px 20px;
        }

        .pn .kartu-kepala {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
        }

        .pn .kartu-kepala h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            color: var(--teks);
        }

        .pn .tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 48px;
            padding: 0 22px;
            border-radius: 14px;
            border: 0;
            background: var(--ungu);
            color: #fff;
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            text-decoration: none;
            cursor: pointer;
            width: auto;
            flex: none;
        }

        .pn .tombol:hover {
            background: var(--ungu-tua);
        }

        /* ---------- Tabel ---------- */
        .pn .tabel-wrap {
            overflow-x: auto;
        }

        .pn table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            color: var(--teks);
        }

        .pn th {
            text-align: left;
            font-size: 14px;
            font-weight: 500;
            color: var(--redup);
            padding: 12px 12px;
            border-bottom: 1px solid var(--garis);
            white-space: nowrap;
        }

        .pn td {
            padding: 16px 12px;
            border-bottom: 1px solid #efecfa;
            vertical-align: middle;
        }

        .pn tr:last-child td {
            border-bottom: 0;
        }

        .pn .angka {
            text-align: right;
            white-space: nowrap;
        }

        .pn td small {
            display: block;
            color: var(--redup);
            font-size: 12.5px;
            margin-top: 2px;
        }

        .pn .orang {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pn .avatar-kecil {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--ungu-muda);
            color: #5b45d6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex: none;
        }

        .pn .nama {
            font-weight: 500;
        }

        .pn .bayar-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            cursor: pointer;
        }

        .pn .bayar-cell input[type="checkbox"] {
            width: 20px;
            height: 20px;
            min-width: 20px;
            flex: none;
            padding: 0;
            margin: 0;
            border: 0;
            box-shadow: none;
            background: none;
            accent-color: var(--ungu-tua);
            cursor: pointer;
        }

        .pn .bayar-cell input[type="checkbox"]:disabled {
            opacity: .5;
            cursor: wait;
        }

        .pn .pill {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .pn .pill.lunas {
            background: var(--hijau-bg);
            color: var(--hijau);
        }

        .pn .pill.belum {
            background: var(--oranye-bg);
            color: var(--oranye);
        }

        /* ---------- Menu titik tiga ---------- */
        .pn .menu {
            position: relative;
            display: inline-block;
        }

        .pn .menu-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--teks);
            cursor: pointer;
            padding: 0;
        }

        .pn .menu-btn:hover {
            background: #f3f0ff;
        }

        .pn .menu-isi {
            position: absolute;
            right: 0;
            top: 40px;
            z-index: 20;
            min-width: 150px;
            padding: 6px;
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(31, 27, 58, .12);
        }

        .pn .menu-isi[hidden] {
            display: none;
        }

        .pn .menu-isi button {
            width: 100%;
            text-align: left;
            border: 0;
            background: transparent;
            padding: 9px 12px;
            border-radius: 8px;
            font: inherit;
            font-size: 14px;
            color: #b3261e;
            cursor: pointer;
        }

        .pn .menu-isi button:hover {
            background: #fdecea;
        }

        .pn .menu-isi a.menu-link {
            display: block;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--teks);
            text-decoration: none;
        }

        .pn .menu-isi a.menu-link:hover {
            background: #f3f0ff;
        }

        /* ---------- Lain-lain ---------- */
        .pn .alert {
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .pn .alert-sukses {
            background: #e8f5ee;
            border: 1px solid #198754;
            color: #146c43;
        }

        .pn .alert-gagal {
            background: #fdecea;
            border: 1px solid #b3261e;
            color: #b3261e;
        }

        .pn .kosong {
            padding: 36px;
            text-align: center;
            color: var(--redup);
        }

        .pn .paginasi {
            margin-top: 16px;
        }

        @media (max-width: 1100px) {
            .pn .stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .pn .stat-grid {
                grid-template-columns: 1fr;
            }

            .pn .kartu {
                padding: 20px 16px;
            }
        }
    </style>

    <div class="pn">
        {{-- HEADER HALAMAN --}}
        <header class="page-header">
            <div class="page-title">
                <h1>Penugasan Mitra</h1>
                <p>Daftar tugas yang sudah diberikan dan status pembayaran honornya</p>
            </div>

            <div class="page-actions">
                <span class="username">{{ auth()->user()->name }}</span>
                <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        @if (session('sukses'))
            <div class="alert alert-sukses" role="status">{{ session('sukses') }}</div>
        @endif
        @if (session('gagal'))
            <div class="alert alert-gagal" role="alert">{{ session('gagal') }}</div>
        @endif

        {{-- KARTU STATISTIK --}}
        <div class="stat-grid">
            <div class="stat utama">
                <div class="stat-judul">
                    <svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-6.2-8.55" /></svg>
                    Progres Pembayaran
                </div>
                <div class="stat-nilai" id="sumPersen">{{ $persen }}%</div>
            </div>

            <div class="stat">
                <div class="stat-judul">
                    <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18M16 14.5h2" /></svg>
                    Total Honor
                </div>
                <div class="stat-nilai kecil" id="sumTotal" data-nilai="{{ $ringkasan['total'] }}">
                    Rp {{ number_format($ringkasan['total'], 0, ',', '.') }}
                </div>
            </div>

            <div class="stat">
                <div class="stat-judul">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="m8.5 12.5 2.5 2.5 4.5-5" /></svg>
                    Sudah Dibayar
                </div>
                <div class="stat-nilai kecil hijau" id="sumDibayar" data-nilai="{{ $ringkasan['dibayar'] }}">
                    Rp {{ number_format($ringkasan['dibayar'], 0, ',', '.') }}
                </div>
            </div>

            <div class="stat">
                <div class="stat-judul">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                    Belum Dibayar
                </div>
                <div class="stat-nilai kecil oranye" id="sumBelum" data-nilai="{{ $ringkasan['belum'] }}">
                    Rp {{ number_format($ringkasan['belum'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- TOOLBAR: PENCARIAN & TAB STATUS --}}
        <div class="toolbar">
            <form method="GET" action="{{ route('admin.penugasan') }}" class="cari">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                <input type="search" name="q" value="{{ $q }}"
                    placeholder="Cari uraian pekerjaan atau nama mitra..." autocomplete="off">
                @if ($status !== 'semua')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
            </form>

            <nav class="tab" aria-label="Filter status pembayaran">
                <a href="{{ $urlStatus('semua') }}" class="{{ $status === 'semua' ? 'aktif' : '' }}">Semua</a>
                <a href="{{ $urlStatus('belum') }}" class="{{ $status === 'belum' ? 'aktif' : '' }}">Belum Dibayar</a>
                <a href="{{ $urlStatus('lunas') }}" class="{{ $status === 'lunas' ? 'aktif' : '' }}">Sudah Dibayar</a>
            </nav>
        </div>

        {{-- KARTU DAFTAR --}}
        <section class="kartu">
            <div class="kartu-kepala">
                <h2>
                    <svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2" /><path d="M9 4.5h6M9 11h6M9 15h4" /></svg>
                    List Penugasan
                </h2>

                @if (Route::has('admin.penugasan.buat'))
                    <a href="{{ route('admin.penugasan.buat') }}" class="tombol">
                        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg>
                        Beri Tugas
                    </a>
                @endif
            </div>

            <div class="tabel-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mitra</th>
                            <th>Uraian Pekerjaan</th>
                            <th>Koordinator</th>
                            <th>Periode</th>
                            <th class="angka">Volume</th>
                            <th class="angka">Honor</th>
                            <th>Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $p)
                            @php
                                $user = $p->kontrak?->mitra?->user;
                                $nama = $user?->name ?? '-';
                            @endphp
                            <tr>
                                <td>
                                    <div class="orang">
                                        <div class="avatar-kecil">{{ strtoupper(mb_substr($nama, 0, 1)) }}</div>
                                        <div>
                                            <div class="nama">{{ $nama }}</div>
                                            @if ($user)
                                                <small>{{ $user->username }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $p->uraian_pekerjaan }}
                                    @if ($p->ro)
                                        <small>RO: {{ $p->ro }}</small>
                                    @endif
                                </td>
                                <td>{{ $p->koordinator?->name ?? '-' }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/Y') }}
                                    <small>s.d. {{ \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/Y') }}</small>
                                </td>
                                <td class="angka">
                                    {{ rtrim(rtrim(number_format((float) $p->volume, 2, ',', '.'), '0'), ',') }}
                                    {{ $p->satuan }}
                                </td>
                                <td class="angka">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                                <td>
                                    <label class="bayar-cell">
                                        <input type="checkbox" class="cek-bayar"
                                            data-url="{{ route('admin.penugasan.bayar', $p) }}"
                                            data-jumlah="{{ (int) $p->jumlah }}" @checked($p->honor_dibayar)>
                                        <span>
                                            <span class="pill {{ $p->honor_dibayar ? 'lunas' : 'belum' }}">
                                                {{ $p->honor_dibayar ? 'Lunas' : 'Belum' }}
                                            </span>
                                            <small class="bayar-waktu">
                                                {{ $p->honor_dibayar && $p->dibayar_pada ? $p->dibayar_pada->format('d/m/Y H:i') : '' }}
                                            </small>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    @if (Route::has('admin.penugasan.edit') || Route::has('admin.penugasan.hapus'))
                                        <div class="menu">
                                            <button type="button" class="menu-btn" aria-label="Aksi" aria-haspopup="true">
                                                <svg viewBox="0 0 24 24" style="fill:currentColor;stroke:none">
                                                    <circle cx="12" cy="5" r="1.7" />
                                                    <circle cx="12" cy="12" r="1.7" />
                                                    <circle cx="12" cy="19" r="1.7" />
                                                </svg>
                                            </button>
                                            <div class="menu-isi" hidden>
                                                @if (Route::has('admin.penugasan.edit'))
                                                    <a href="{{ route('admin.penugasan.edit', $p) }}" class="menu-link">Edit tugas</a>
                                                @endif
                                                @if (Route::has('admin.penugasan.hapus'))
                                                    <form action="{{ route('admin.penugasan.hapus', $p) }}" method="POST"
                                                        onsubmit="return confirm('Hapus tugas ini untuk {{ e($nama) }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit">Hapus tugas</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="kosong">
                                    @if ($q !== '' || $status !== 'semua')
                                        Tidak ada tugas yang cocok dengan pencarian atau filter.
                                    @else
                                        Belum ada tugas yang diberikan.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="paginasi">
                {{ $daftar->links() }}
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (function() {
                const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
                const rp = n => 'Rp ' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                const sumTotal = document.getElementById('sumTotal');
                const sumDibayar = document.getElementById('sumDibayar');
                const sumBelum = document.getElementById('sumBelum');
                const sumPersen = document.getElementById('sumPersen');

                function geserRingkasan(jumlah, dibayar) {
                    const arah = dibayar ? 1 : -1;
                    const total = Number(sumTotal.dataset.nilai);
                    sumDibayar.dataset.nilai = Number(sumDibayar.dataset.nilai) + arah * jumlah;
                    sumBelum.dataset.nilai = Number(sumBelum.dataset.nilai) - arah * jumlah;
                    sumDibayar.textContent = rp(sumDibayar.dataset.nilai);
                    sumBelum.textContent = rp(sumBelum.dataset.nilai);
                    sumPersen.textContent = (total > 0 ? Math.round(Number(sumDibayar.dataset.nilai) / total * 100) : 0) + '%';
                }

                function tampilkan(cek, dibayar, waktu) {
                    const sel = cek.closest('.bayar-cell');
                    const pill = sel.querySelector('.pill');
                    pill.textContent = dibayar ? 'Lunas' : 'Belum';
                    pill.className = 'pill ' + (dibayar ? 'lunas' : 'belum');
                    sel.querySelector('.bayar-waktu').textContent = dibayar && waktu ? waktu : '';
                }

                document.querySelectorAll('.cek-bayar').forEach(cek => {
                    cek.addEventListener('change', async () => {
                        const dibayar = cek.checked;
                        cek.disabled = true;

                        try {
                            const res = await fetch(cek.dataset.url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ dibayar })
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);

                            const hasil = await res.json();
                            tampilkan(cek, hasil.dibayar, hasil.dibayar_pada);
                            geserRingkasan(Number(cek.dataset.jumlah), hasil.dibayar);
                        } catch (e) {
                            cek.checked = !dibayar; // kembalikan jika gagal
                            alert('Status pembayaran gagal disimpan. Coba lagi.');
                        } finally {
                            cek.disabled = false;
                        }
                    });
                });

                /* Menu titik tiga */
                const tutupSemua = () => document.querySelectorAll('.pn .menu-isi').forEach(m => m.hidden = true);

                document.querySelectorAll('.pn .menu-btn').forEach(btn => {
                    btn.addEventListener('click', e => {
                        e.stopPropagation();
                        const isi = btn.parentElement.querySelector('.menu-isi');
                        const sedangBuka = !isi.hidden;
                        tutupSemua();
                        isi.hidden = sedangBuka;
                    });
                });

                document.addEventListener('click', tutupSemua);
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') tutupSemua();
                });
            })();
        </script>
    @endpush
</x-admin.main>
