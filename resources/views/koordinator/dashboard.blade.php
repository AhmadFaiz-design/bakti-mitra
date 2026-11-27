@php
    $bulanSebelumnya = $bulan->copy()->subMonth()->format('Y-m');
    $bulanBerikutnya = $bulan->copy()->addMonth()->format('Y-m');
    $hariHeader = $calStart->dayOfWeek === \Carbon\Carbon::MONDAY
        ? ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
        : ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
@endphp

<x-koordinator.main>
    <div class="dash">
        <x-header judul="Dashboard" :opsi-bulan="$opsiBulan" :bulan-key="$bulanKey" :label-bulan="$labelBulan" :notifikasi="$notifikasi"
            :notif-penting="$notifPenting" />

        <div class="dash-top">
            <div class="dash-left">
                <section class="stats">
                    <div class="card hero">
                        <h2>
                            <svg>
                                <use href="#i-ring" />
                            </svg>
                            Progres Pembayaran
                        </h2>
                        <p class="num">{{ $persenBayar }}%</p>
                        <div class="progress"><span style="width: {{ $persenBayar }}%"></span></div>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-user" />
                            </svg>
                            Total Mitra
                        </h2>
                        <p class="num">{{ $totalMitra }}</p>
                        <span class="badge">{{ $mitraTersedia }} Tersedia</span>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-briefcase" />
                            </svg>
                            Pekerjaan
                        </h2>
                        <p class="num">{{ $totalPekerjaan }}</p>
                    </div>
                </section>

                {{-- PROGRES MITRA --}}
                <section class="card chart-card" x-data="progresChart(@js($progres))">
                    <div class="chart-head">
                        <h2>
                            <svg>
                                <use href="#i-chart" />
                            </svg>
                            Progres Mitra
                        </h2>

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

                    <div class="chart" x-ref="chart" :class="{ 'paged': paged }">
                        <div class="axis">
                            <template x-for="(t, i) in ticks" :key="i">
                                <span x-text="t"></span>
                            </template>
                        </div>

                        <div class="bars">
                            <template x-for="m in batang" :key="m.id">
                                <div class="bar-slot">
                                    <div class="bar" :class="{ 'top': m.status === 'penuh' }"
                                        :style="`--t:${m.t}%`"
                                        :title="`${m.nama}: ${fmt(m.selesai)} dari ${fmt(m.total)} selesai`">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" class="chart-nav prev" x-show="paged" x-cloak :disabled="halaman === 0"
                            @click="halaman--" aria-label="Sebelumnya">
                            <svg>
                                <use href="#i-left" />
                            </svg>
                        </button>
                        <button type="button" class="chart-nav next" x-show="paged" x-cloak
                            :disabled="halaman >= jumlahHal - 1" @click="halaman++" aria-label="Berikutnya">
                            <svg class="flip">
                                <use href="#i-left" />
                            </svg>
                        </button>

                        <p class="chart-empty" x-show="terfilter.length === 0" x-cloak>
                            Belum ada mitra yang bertugas atau penuh pada bulan ini.
                        </p>
                    </div>

                    <div class="chart-labels" :class="{ 'paged': paged }">
                        <div class="lbls">
                            <template x-for="m in batang" :key="m.id">
                                <span class="lbl" x-text="m.label" :title="m.nama"></span>
                            </template>
                        </div>
                    </div>
                </section>
            </div>

            {{-- KALENDER --}}
            <aside class="card calendar-card">
                <div class="cal-head">
                    <h2>
                        <svg>
                            <use href="#i-cal" />
                        </svg>
                        Kalender
                    </h2>

                    <div class="cal-nav">
                        <a href="{{ request()->fullUrlWithQuery(['bulan' => $bulanSebelumnya]) }}"
                            aria-label="Bulan sebelumnya">
                            <svg>
                                <use href="#i-left" />
                            </svg>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['bulan' => $bulanBerikutnya]) }}"
                            aria-label="Bulan berikutnya">
                            <svg class="flip">
                                <use href="#i-left" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="cal-weekdays" aria-hidden="true">
                    @foreach ($hariHeader as $h)
                        <span>{{ $h }}</span>
                    @endforeach
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

                <div class="cal-notes scroll">
                    <div id="calNotesList"></div>
                </div>
            </aside>
        </div>

        {{-- BARIS BAWAH: 4 KARTU --}}
        <section class="dash-bottom">
            <div class="pair">
                {{-- STATUS MITRA --}}
                <div class="card">
                    <h2>
                        <svg>
                            <use href="#i-chat" />
                        </svg>
                        Status Mitra
                    </h2>
                    <ul class="list scroll longgar">
                        @forelse ($statusMitra as $m)
                            <li>
                                <span class="dot"></span>
                                <span class="info">
                                    <strong class="nama">{{ $m['nama'] }}</strong>
                                    <small>{{ $m['sobat'] }}</small>
                                </span>
                                <span class="kanan">
                                    <span @class([
                                        'badge',
                                        'bertugas' => $m['status'] === 'Bertugas',
                                        'tersedia' => $m['status'] === 'Tersedia',
                                        'penuh' => $m['status'] === 'Penuh',
                                    ])>{{ $m['status'] }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="kosong-item">Belum ada mitra yang tersedia.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- AKTIVITAS PENUGASAN --}}
                <div class="card">
                    <h2>
                        <svg>
                            <use href="#i-list" />
                        </svg>
                        Aktivitas Penugasan
                    </h2>
                    <ul class="list scroll longgar">
                        @forelse ($aktivitas as $a)
                            <li class="task">
                                <span class="judul" title="{{ $a->judul }}">
                                    <small class="task-date">{{ $a->periode }}</small>
                                    <strong>{{ $a->judul }}</strong>
                                </span>
                            </li>
                        @empty
                            <li class="kosong-item">Belum ada tugas pada bulan ini.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="dash-side">
                {{-- PERLU PERHATIAN --}}
                <div class="card perlu">
                    <h2>
                        <svg>
                            <use href="#i-alarm" />
                        </svg>
                        Perlu Perhatian
                    </h2>

                    <div class="perlu-wrap">
                        <ul class="perhatian-list scroll" id="perhatianList">
                            @forelse ($perhatian as $p)
                                @php $tag = $p['url'] ? 'a' : 'div'; @endphp
                                <li>
                                    <{{ $tag }}
                                        @if ($p['url']) href="{{ $p['url'] }}" @endif
                                        class="perhatian {{ $p['level'] }}">
                                        <span class="p-bar" aria-hidden="true"></span>
                                        <span class="p-teks">
                                            <strong>{{ $p['judul'] }}</strong>
                                            <small>{{ $p['ket'] }}</small>
                                        </span>
                                    </{{ $tag }}>
                                </li>
                            @empty
                                <li class="kosong">Semua aman. Tidak ada yang perlu ditindaklanjuti.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                {{-- WILAYAH --}}
                @php $warnaSeb = ['#2BB868', '#142419', '#7EF279', '#8A79F1', '#188649', '#657B6B']; @endphp
                <div class="card sebaran">
                    <h2>
                        <svg>
                            <use href="#i-pin" />
                        </svg>
                        Wilayah
                    </h2>

                    @if ($sebaran->isEmpty())
                        <p class="seb-kosong">Belum ada data wilayah.</p>
                    @else
                        <ul class="wilayah-bars scroll">
                            @foreach ($sebaran as $i => $w)
                                @php $persenWil = round(($w->jumlah / max(1, $totalSebaran)) * 100); @endphp
                                <li style="--c: {{ $warnaSeb[$i % 6] }}">
                                    <span class="wil-nama" title="{{ $w->wilayah }}">{{ $w->wilayah }}</span>
                                    <div class="wil-track"
                                        title="{{ $w->wilayah }}: {{ $w->jumlah }} mitra ({{ $persenWil }}%)">
                                        <span style="width: {{ max(6, $persenWil) }}%"></span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <ul class="wilayah-legend">
                            @foreach ($sebaran as $i => $w)
                                <li style="--c: {{ $warnaSeb[$i % 6] }}" title="{{ $w->wilayah }}">
                                    <i></i><span>{{ $w->jumlah }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            /* Grafik progres mitra (Alpine) - 1 batang progres real-time */
            window.progresChart = function(data) {
                const SLOT = 74;
                const SUMBU = 40;
                const NAV = 76;

                return {
                    data,
                    pilih: 'semua',
                    buka: false,
                    halaman: 0,
                    perHal: 8,
                    paged: false,
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
                    init() {
                        this.$nextTick(() => {
                            this.ukur();
                            if ('ResizeObserver' in window) {
                                new ResizeObserver(() => this.ukur()).observe(this.$refs.chart);
                            }
                        });
                        this.$watch('pilih', () => {
                            this.halaman = 0;
                            this.ukur();
                        });
                    },
                    ukur() {
                        const el = this.$refs.chart;
                        if (!el) return;
                        const lebar = Math.max(0, el.clientWidth - SUMBU);
                        const muat = Math.max(1, Math.floor(lebar / SLOT));

                        if (this.terfilter.length <= muat) {
                            this.paged = false;
                            this.perHal = muat;
                        } else {
                            this.paged = true;
                            this.perHal = Math.max(1, Math.floor((lebar - NAV) / SLOT));
                        }
                        this.halaman = Math.min(this.halaman, this.jumlahHal - 1);
                    },
                    get label() {
                        return this.opsi.find(o => o.k === this.pilih).t;
                    },
                    get terfilter() {
                        return this.data.filter(m => this.pilih === 'semua' || m.status === this.pilih);
                    },
                    get jumlahHal() {
                        return Math.max(1, Math.ceil(this.terfilter.length / this.perHal));
                    },
                    get langkah() {
                        const maks = Math.max(0, ...this.terfilter.map(m => m.selesai));
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
                        const puncak = Math.max(4, this.langkah * 4);
                        const awal = this.halaman * this.perHal;
                        return this.terfilter.slice(awal, awal + this.perHal).map(m => ({
                            ...m,
                            t: m.selesai > 0 ? Math.max(8, Math.min(100, m.selesai / puncak * 100)) : 5,
                        }));
                    },
                    fmt(v) {
                        return Number(Number(v).toFixed(2)).toLocaleString('id-ID');
                    },
                };
            };

            /* Kalender: tugas pada tanggal terpilih */
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
                        row.className = 'note';

                        if (item.rentang || item.mitra) {
                            const sub = document.createElement('small');
                            sub.className = 'note-rentang';
                            sub.textContent = item.rentang || (item.mitra + ' mitra');
                            row.appendChild(sub);
                        }

                        const bawah = document.createElement('div');
                        bawah.className = 'note-row';

                        const judul = document.createElement('strong');
                        judul.className = 'note-judul';
                        judul.textContent = item.judul;
                        judul.title = item.judul;
                        bawah.appendChild(judul);

                        if (item.badge || item.deadline) {
                            const badge = document.createElement('span');
                            badge.className = 'note-badge';
                            badge.textContent = item.badge || 'Deadline';
                            bawah.appendChild(badge);
                        }

                        row.appendChild(bawah);
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
</x-koordinator.main>
