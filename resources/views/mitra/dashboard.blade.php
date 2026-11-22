<x-mitra.main>
    <div class="dash mitra-dash">
        <header class="topbar">
            <h1>Dashboard</h1>

            {{-- PILIH BULAN --}}
            <div class="dropdown bulan-dd" x-data="{ open: false }" @click.outside="open = false" x-init="$watch('open', v => v && $nextTick(() => {
                const m = $refs.menu,
                    a = m.querySelector('a.active');
                if (a) m.scrollTop = a.offsetTop - m.clientHeight / 2;
            }))">
                <button type="button" class="pill" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox">
                    <span>{{ $labelBulan }}</span>
                    <svg :class="{ 'rotate': open }">
                        <use href="#i-down" />
                    </svg>
                </button>

                <ul class="dropdown-menu bulan-menu" x-ref="menu" x-show="open" x-transition.opacity.duration.150ms
                    x-cloak>
                    @foreach ($opsiBulan as $o)
                        <li>
                            <a href="{{ route('mitra.dashboard', ['bulan' => $o['k']]) }}"
                                class="{{ $o['k'] === $bulanKey ? 'active' : '' }}">{{ $o['t'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="user">
                <div class="notif" x-data="{ open: false }" @click.outside="open = false"
                    @keydown.escape.window="open = false">
                    <button type="button" class="bell" @click="open = !open" :aria-expanded="open"
                        aria-haspopup="dialog" aria-label="Notifikasi">
                        <svg>
                            <use href="#i-bell" />
                        </svg>
                        @if ($notifPenting > 0)
                            <span class="bell-dot"></span>
                        @endif
                    </button>

                    <div class="notif-pop" x-show="open" x-transition.opacity.duration.150ms x-cloak role="dialog"
                        aria-label="Notifikasi">
                        <div class="notif-head">
                            <strong>Notifikasi</strong>
                            @if ($notifPenting > 0)
                                <span>{{ $notifPenting }} perlu perhatian</span>
                            @endif
                        </div>

                        <ul class="notif-list">
                            @forelse ($notifikasi as $n)
                                @php $tag = $n['url'] ? 'a' : 'div'; @endphp
                                <li>
                                    <{{ $tag }}
                                        @if ($n['url']) href="{{ $n['url'] }}" @endif
                                        class="notif-item {{ $n['level'] }}">
                                        <span class="n-ic">
                                            @if ($n['ikon'] === 'tugas')
                                                <svg>
                                                    <use href="#i-list" />
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M12 3l10 18H2L12 3zM12 10v4M12 17.5v.01" />
                                                </svg>
                                            @endif
                                        </span>
                                        <span class="n-teks">
                                            <strong>{{ $n['judul'] }}</strong>
                                            <small>{{ $n['ket'] }}</small>
                                            @if (!empty($n['waktu']))
                                                <em>{{ $n['waktu'] }}</em>
                                            @endif
                                        </span>
                                    </{{ $tag }}>
                                </li>
                            @empty
                                <li class="notif-kosong">Belum ada notifikasi.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                {{ auth()->user()->name }}
                <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- BARIS ATAS: KIRI (STATS + RINCIAN TUGAS & JADWAL PENUGASAN) | KANAN (KALENDER) --}}
        <div class="dash-top">
            <div class="dash-left">
                <section class="stats">
                    <div class="card hero">
                        <h2><svg>
                                <use href="#i-ring" />
                            </svg>Progres Pembayaran</h2>
                        <p class="num" style="font-size:28px;font-weight:600;margin:10px 0">{{ $persenBayar }}%</p>
                        <div class="progress"><span style="width: {{ $persenBayar }}%"></span></div>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-cash" />
                            </svg>
                            Total Honor
                        </h2>
                        <p class="num" style="font-size:20px;line-height:1.3;margin:10px 0 8px">
                            Rp {{ number_format($totalHonor, 0, ',', '.') }}
                        </p>
                        <span class="badge {{ $persenBatas < 100 ? 'lime' : '' }}">
                            {{ $persenBatas }}% dari batas
                        </span>
                    </div>

                    <div class="card stat">
                        <h2><svg>
                                <use href="#i-list" />
                            </svg>Pekerjaan</h2>
                        <p class="num">{{ $totalPekerjaan }}</p>
                        <span class="badge">{{ $kalender['berlangsung'] }} Berlangsung</span>
                    </div>
                </section>

                <div class="pair">
                    {{-- RINCIAN TUGAS & PEMBAYARAN --}}
                    <div class="card">
                        <h2><svg>
                                <use href="#i-list" />
                            </svg>Rincian Tugas
                        </h2>
                        <ul class="list scroll longgar">
                            @forelse ($daftarTugas as $t)
                                <li>
                                    <span class="dot"></span>
                                    <span class="info">
                                        <strong class="nama" title="{{ $t['judul'] }}">{{ $t['judul'] }}</strong>
                                        <small>{{ $t['koordinator'] }} · {{ $t['volume'] }}</small>
                                    </span>
                                    <span class="kanan">
                                        <small>Rp {{ number_format($t['honor'], 0, ',', '.') }}</small>
                                        <span class="badge {{ $t['dibayar'] ? 'lime' : '' }}">
                                            {{ $t['dibayar'] ? 'Dibayar' : 'Belum' }}
                                        </span>
                                    </span>
                                </li>
                            @empty
                                <li>Belum ada tugas pada bulan ini.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- JADWAL PENUGASAN --}}
                    <div class="card">
                        <h2><svg>
                                <use href="#i-cal" />
                            </svg>Jadwal Penugasan</h2>
                        <ul class="list scroll longgar">
                            @forelse ($aktivitas as $a)
                                <li class="task">
                                    <span class="judul" title="{{ $a->judul }}">
                                        <strong>{{ $a->judul }}</strong>
                                        <small>{{ $a->koordinator ? $a->koordinator . ' · ' : '' }}{{ $a->periode }}</small>
                                    </span>
                                    <span class="mt">{{ $a->status_waktu }}</span>
                                </li>
                            @empty
                                <li>Belum ada jadwal tugas pada bulan ini.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- KALENDER --}}
            <aside class="card calendar-card">
                <h2><svg>
                        <use href="#i-cal" />
                    </svg>{{ $bulan->copy()->locale('id')->translatedFormat('F') }}</h2>

                <div class="cal-summary">
                    <div class="box-cal"><small>Deadline</small><strong>{{ $kalender['deadline'] }}</strong></div>
                    <div><small>Selesai</small><strong>{{ $kalender['selesai'] }}</strong></div>
                    <div><small>Berlangsung</small><strong>{{ $kalender['berlangsung'] }}</strong></div>
                </div>

                <div class="cal-grid">
                    @for ($d = $calStart->copy(); $d->lte($calEnd); $d->addDay())
                        @php
                            $key = $d->format('Y-m-d');
                            $adaDeadline = collect($tugas[$key] ?? [])->contains('deadline', true);
                        @endphp
                        <button type="button" data-date="{{ $key }}"
                            @class([
                                'cal-day',
                                'other' => $d->month !== $bulan->month,
                                'today' => $key === $hariIni,
                                'selected' => $key === $hariDipilih,
                                'has-deadline' => $adaDeadline,
                            ])>{{ $d->day }}</button>
                    @endfor
                </div>

                <div class="cal-notes">
                    <div id="calNotesList"></div>
                </div>
            </aside>
        </div>

        {{-- BARIS BAWAH: PERLU PERHATIAN & KUOTA HONOR (FULL WIDTH 50/50) --}}
        <section class="dash-bottom mitra-bottom">
            {{-- PERLU PERHATIAN --}}
            <div class="card perlu">
                <h2><svg>
                        <use href="#i-bell" />
                    </svg>Perlu Perhatian
                    <span class="card-sub">{{ $perhatian->count() }} item</span>
                </h2>

                <div class="perlu-wrap">
                    <ul class="perhatian-list scroll" id="perhatianList">
                        @forelse ($perhatian as $p)
                            @php $tag = $p['url'] ? 'a' : 'div'; @endphp
                            <li>
                                <{{ $tag }}
                                    @if ($p['url']) href="{{ $p['url'] }}" @endif
                                    class="perhatian {{ $p['level'] }}">
                                    <span class="p-teks">
                                        <strong>{{ $p['judul'] }}</strong>
                                        <small>{{ $p['ket'] }}</small>
                                    </span>
                                    <span class="p-tag">{{ $p['tag'] }}</span>
                                </{{ $tag }}>
                            </li>
                        @empty
                            <li class="kosong">Semua aman. Tidak ada yang perlu ditindaklanjuti.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- KOMPOSISI HONOR & KUOTA --}}
            @php $warnaKomp = ['var(--primary)', 'var(--lime)', '#CBD5E1']; @endphp
            <div class="card sebaran">
                <h2>
                    <svg>
                        <use href="#i-cash" />
                    </svg>
                    Kuota &amp; Honor
                </h2>

                @if ($komposisiHonor->isEmpty())
                    <p class="seb-kosong">Belum ada data honor pada bulan ini.</p>
                @else
                    <div class="seb-bar" role="img" aria-label="Proporsi pembayaran dan sisa kuota honor">
                        @foreach ($komposisiHonor as $i => $item)
                            <span style="--c: {{ $warnaKomp[$i % 3] }}; --n: {{ $item->jumlah }}"
                                title="{{ $item->label }}: Rp {{ number_format($item->jumlah, 0, ',', '.') }}"></span>
                        @endforeach
                    </div>

                    <ul class="seb-legend scroll">
                        @foreach ($komposisiHonor as $i => $item)
                            <li style="--c: {{ $warnaKomp[$i % 3] }}">
                                <span class="nm"><i></i><span class="nm-t">{{ $item->label }}</span></span>
                                <span class="vl">Rp {{ number_format($item->jumlah, 0, ',', '.') }}<em>{{ round(($item->jumlah / max(1, $totalKomposisi)) * 100) }}%</em></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            /* Kalender: tugas milik mitra pada tanggal terpilih */
            (function() {
                const tugas = @json($tugas);
                const dipilih = @json($hariDipilih);
                const days = document.querySelectorAll('.cal-day');
                const list = document.getElementById('calNotesList');

                function tampilkan(key) {
                    list.innerHTML = '';
                    const items = tugas[key] || [];

                    if (!items.length) {
                        const kosong = document.createElement('div');
                        kosong.className = 'note-kosong';
                        kosong.textContent = 'Tidak ada tugas pada tanggal ini.';
                        list.appendChild(kosong);
                        return;
                    }

                    items.forEach(item => {
                        const row = document.createElement('div');
                        row.className = 'note' + (item.deadline ? ' deadline' : '');

                        const judul = document.createElement('span');
                        judul.className = 'note-judul';
                        judul.textContent = item.judul;
                        judul.title = item.judul;

                        const meta = document.createElement('span');
                        meta.className = 'note-meta';

                        if (item.koordinator) {
                            const k = document.createElement('span');
                            k.className = 'note-mitra';
                            k.textContent = item.koordinator;
                            meta.appendChild(k);
                        }

                        if (item.deadline) {
                            const t = document.createElement('span');
                            t.className = 'tag-deadline';
                            t.textContent = 'Deadline';
                            meta.appendChild(t);
                        }

                        row.append(judul, meta);
                        list.appendChild(row);
                    });
                }

                days.forEach(btn => {
                    btn.addEventListener('click', () => {
                        days.forEach(d => d.classList.remove('selected'));
                        btn.classList.add('selected');
                        tampilkan(btn.dataset.date);
                    });
                });

                tampilkan(dipilih);
            })();
        </script>
    @endpush
</x-mitra.main>
