<x-admin.main>
    <header class="topbar">
        <h1>Dashboard</h1>
        <label class="search"><svg>
                <use href="#i-search" />
            </svg><input placeholder="Apa yang perlu dicari..."></label>
        <div class="user"><span class="bell"><svg>
                    <use href="#i-bell" />
                </svg></span>{{ auth()->user()->name }}<div class="avatar"
                style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    @php
        $today = now('Asia/Makassar');
        $calStart = $today->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $calEnd = $today->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
        $namaBulan = $today->copy()->locale('id')->translatedFormat('F');
        $maksMitra = $aktivitas->max('mitra');
    @endphp

    <div class="dash-top">
        <div class="dash-left">
            <section class="stats">
                {{-- 1. PROGRES PEMBAYARAN --}}
                <div class="card hero">
                    <h2><svg>
                            <use href="#i-ring" />
                        </svg>Progres Pembayaran</h2>
                    <p class="num" style="font-size:28px;font-weight:600;margin:10px 0">{{ $persenBayar }}%</p>
                    <div class="progress"><span style="width: {{ $persenBayar }}%"></span></div>
                </div>

                {{-- 2. TOTAL MITRA --}}
                <div class="card stat">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Total Mitra</h2>
                    <p class="num">{{ $totalMitra }}</p>
                    <span class="badge lime">{{ $mitraTersedia }} Mitra Tersedia</span>
                </div>

                <div class="card stat">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Total Koor</h2>
                    <p class="num">{{ $totalKoor }}</p>
                    <span class="badge">{{ $koorAktif }} Aktif</span>
                </div>
            </section>

            {{-- 4. PROGRES MITRA --}}
            <section class="card" x-data="progresChart(@js($progres))">
                <div class="chart-head">
                    <h2><svg>
                            <use href="#i-chart" />
                        </svg>Progres Mitra</h2>

                    <div class="dropdown" @click.outside="buka = false">
                        <button type="button" class="pill" @click="buka = !buka" :aria-expanded="buka">
                            <span x-text="label"></span>
                            <svg :class="{ 'rotate': buka }">
                                <use href="#i-down" />
                            </svg>
                        </button>

                        <ul class="dropdown-menu" x-show="buka" x-transition.opacity.duration.150ms x-cloak>
                            <template x-for="o in opsi" :key="o.k">
                                <li>
                                    <button type="button" :class="{ 'active': pilih === o.k }"
                                        @click="pilih = o.k; buka = false" x-text="o.t"></button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <p class="chart-note">Tinggi batang: volume tugas bulan ini. Bagian terisi: volume yang sudah
                    selesai. Batang gelap: mitra penuh.</p>

                <div class="chart">
                    <div class="axis">
                        <template x-for="t in ticks" :key="t">
                            <span x-text="t"></span>
                        </template>
                    </div>

                    <template x-for="m in batang" :key="m.id">
                        <div class="bar" :class="{ 'top': m.status === 'penuh' }" :style="`--t:${m.t}%`"
                            :title="`${m.nama}: ${fmt(m.selesai)} dari ${fmt(m.total)} selesai`">
                            <i :style="`--v:${m.v}%`"></i>
                        </div>
                    </template>
                </div>

                <div class="labels">
                    <template x-for="m in batang" :key="m.id">
                        <span x-text="m.label" :title="m.nama"></span>
                    </template>
                </div>

                <p class="chart-note" x-show="batang.length === 0" x-cloak style="margin-top:14px">
                    Belum ada mitra yang bertugas atau penuh pada bulan ini.
                </p>
            </section>
        </div>

        {{-- 3. KALENDER --}}
        <aside class="card calendar-card">
            <h2><svg>
                    <use href="#i-cal" />
                </svg>{{ $namaBulan }}</h2>

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
                        data-label="{{ $d->copy()->locale('id')->translatedFormat('j F') }}"
                        @class([
                            'cal-day',
                            'other' => $d->month !== $today->month,
                            'today' => $d->isSameDay($today),
                            'selected' => $d->isSameDay($today),
                            'has-deadline' => $adaDeadline,
                        ])>{{ $d->day }}</button>
                @endfor
            </div>

            <div class="cal-notes">
                <p id="calNotesTitle">Keterangan Hari ini</p>
                <div id="calNotesList"></div>
            </div>
        </aside>
    </div>

    {{-- RINGKASAN BULAN INI --}}
    {{-- <section class="dash-mini">
        <div class="card stat">
            <h2><svg>
                    <use href="#i-chart" />
                </svg>Honor Bulan Ini</h2>
            <p class="num">Rp {{ number_format($totalHonorBulan, 0, ',', '.') }}</p>
            <span class="badge">{{ $namaBulan }} {{ $today->year }}</span>
        </div>

        <div class="card stat">
            <h2><svg>
                    <use href="#i-ring" />
                </svg>Belum Dibayar</h2>
            <p class="num">Rp {{ number_format($belumBayarBulan, 0, ',', '.') }}</p>
            <span class="badge {{ $belumBayarBulan > 0 ? '' : 'lime' }}">
                {{ $belumBayarBulan > 0 ? 'Honor bulan ini' : 'Semua lunas' }}
            </span>
        </div>

        <div class="card stat">
            <h2><svg>
                    <use href="#i-user" />
                </svg>Mitra Penuh</h2>
            <p class="num">{{ $mitraPenuh }}</p>
            <span class="badge">Batas Rp {{ number_format($batas, 0, ',', '.') }}</span>
        </div>

        <div class="card stat">
            <h2><svg>
                    <use href="#i-list" />
                </svg>Mitra Bertugas</h2>
            <p class="num">{{ $mitraBertugas }}</p>
            <span class="badge lime">dari {{ $jumlahAktif }} mitra aktif</span>
        </div>
    </section> --}}

    <section class="bottom">
        {{-- PERLU PERHATIAN --}}
        <div class="card">
            <h2><svg>
                    <use href="#i-bell" />
                </svg>Perlu Perhatian</h2>
            <ul class="perhatian-list">
                @forelse ($perhatian as $p)
                    @php $tag = $p['url'] ? 'a' : 'div'; @endphp
                    <li>
                        <{{ $tag }} @if ($p['url']) href="{{ $p['url'] }}" @endif
                            class="perhatian {{ $p['level'] }}">
                            <span class="p-ic">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 3l10 18H2L12 3zM12 10v4M12 17.5v.01" />
                                </svg>
                            </span>
                            <span class="p-teks">
                                <strong>{{ $p['judul'] }}</strong>
                                <small>{{ $p['ket'] }}</small>
                            </span>
                            <span class="p-jml">{{ $p['jumlah'] }}</span>
                            </{{ $tag }}>
                    </li>
                @empty
                    <li class="kosong">Semua aman. Tidak ada yang perlu ditindaklanjuti.</li>
                @endforelse
            </ul>
        </div>

        {{-- SEBARAN WILAYAH --}}
        <div class="card">
            <h2><svg>
                    <use href="#i-cal" />
                </svg>Sebaran Wilayah Mitra</h2>
            @php $maksWil = max(1, (int) $sebaran->max('jumlah')); @endphp
            <ul class="wil-list">
                @forelse ($sebaran as $w)
                    <li>
                        <span class="w-nama" title="{{ $w->wilayah }}">{{ $w->wilayah }}</span>
                        <span class="w-track"><span style="width: {{ ($w->jumlah / $maksWil) * 100 }}%"></span></span>
                        <b>{{ $w->jumlah }}</b>
                    </li>
                @empty
                    <li class="kosong">Belum ada data wilayah.</li>
                @endforelse
            </ul>
        </div>
    </section>

    <section class="bottom">
        {{-- 5. STATUS MITRA --}}
        <div class="card">
            <h2><svg>
                    <use href="#i-user" />
                </svg>Status Mitra</h2>
            <ul class="list">
                @forelse ($statusMitra as $m)
                    <li>
                        <span class="dot"></span>
                        <span class="nama">{{ $m['nama'] }}</span>
                        <span class="wil">{{ $m['wilayah'] }}</span>
                        <span class="badge {{ $m['status'] === 'Tersedia' ? 'lime' : '' }}">{{ $m['status'] }}</span>
                    </li>
                @empty
                    <li>Belum ada mitra yang tersedia.</li>
                @endforelse
            </ul>
        </div>

        {{-- 6. AKTIVITAS PENUGASAN --}}
        <div class="card">
            <h2><svg>
                    <use href="#i-list" />
                </svg>Aktivitas Penugasan</h2>
            <ul class="list">
                @forelse ($aktivitas as $a)
                    <li class="task">
                        <span class="judul" title="{{ $a->judul }}">
                            {{ $a->judul }}
                            <small>{{ $a->koordinator ? $a->koordinator . ' · ' : '' }}{{ $a->periode }}</small>
                        </span>
                        <span
                            class="badge {{ $a->mitra == $maksMitra && $maksMitra > 1 ? 'dark' : '' }}">{{ $a->mitra }}
                            Mitra</span>
                    </li>
                @empty
                    <li>Belum ada tugas yang diberikan.</li>
                @endforelse
            </ul>
        </div>
    </section>

    @push('scripts')
        <script>
            /* Grafik progres mitra (Alpine) */
            window.progresChart = function(data) {
                return {
                    data,
                    pilih: 'semua',
                    buka: false,
                    opsi: [{
                            k: 'semua',
                            t: 'Semua'
                        },
                        {
                            k: 'bertugas',
                            t: 'Bertugas'
                        },
                        {
                            k: 'penuh',
                            t: 'Penuh'
                        },
                    ],
                    get label() {
                        return this.opsi.find(o => o.k === this.pilih).t;
                    },
                    // 10 mitra dengan volume terbanyak sesuai pilihan
                    get terpilih() {
                        return this.data
                            .filter(m => this.pilih === 'semua' || m.status === this.pilih)
                            .slice(0, 10);
                    },
                    // Selang sumbu dibulatkan supaya angkanya rapi (1, 2, 2.5, 5, 10, ...)
                    get langkah() {
                        const maks = Math.max(0, ...this.terpilih.map(m => m.total));
                        if (maks <= 0) return 1;
                        const mentah = maks / 4;
                        const pangkat = Math.pow(10, Math.floor(Math.log10(mentah)));
                        const n = mentah / pangkat;
                        const m = n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10;
                        return m * pangkat;
                    },
                    get ticks() {
                        return [0, 1, 2, 3, 4].map(i => this.fmt(i * this.langkah));
                    },
                    get batang() {
                        const puncak = this.langkah * 4;
                        return this.terpilih.map(m => ({
                            ...m,
                            t: Math.max(4, m.total / puncak * 100),
                            v: m.total > 0 ? Math.min(100, m.selesai / m.total * 100) : 0,
                        }));
                    },
                    fmt(v) {
                        return Number(Number(v).toFixed(2)).toLocaleString('id-ID');
                    },
                };
            };

            /* Kalender */
            (function() {
                const tugas = @json($tugas);
                const todayKey = @json($today->format('Y-m-d'));
                const days = document.querySelectorAll('.cal-day');
                const title = document.getElementById('calNotesTitle');
                const list = document.getElementById('calNotesList');

                function tampilkan(key, label) {
                    title.textContent = key === todayKey ? 'Keterangan Hari ini' : 'Keterangan ' + label;
                    list.innerHTML = '';

                    const items = tugas[key] || [];
                    if (!items.length) {
                        const p = document.createElement('p');
                        p.className = 'empty';
                        p.textContent = 'Tidak ada tugas pada tanggal ini.';
                        list.appendChild(p);
                        return;
                    }

                    items.forEach(item => {
                        const p = document.createElement('p');
                        p.textContent = item.judul + (item.mitra ? ' · ' + item.mitra + ' mitra' : '');
                        if (item.deadline) {
                            const tag = document.createElement('span');
                            tag.className = 'tag-deadline';
                            tag.textContent = 'Deadline';
                            p.appendChild(tag);
                        }
                        list.appendChild(p);
                    });
                }

                days.forEach(btn => {
                    btn.addEventListener('click', () => {
                        days.forEach(d => d.classList.remove('selected'));
                        btn.classList.add('selected');
                        tampilkan(btn.dataset.date, btn.dataset.label);
                    });
                });

                tampilkan(todayKey, '');
            })();
        </script>
    @endpush
</x-admin.main>
