<x-mitra.main>
    <div class="dash mitra-dash">
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
                                <use href="#i-cash" />
                            </svg>
                            Total Honor
                        </h2>
                        <p class="num kecil">Rp {{ number_format($totalHonor, 0, ',', '.') }}</p>
                        <span class="badge">{{ $persenBatas }}% Dari batas</span>
                    </div>

                    <div class="card stat">
                        <h2>
                            <svg>
                                <use href="#i-list" />
                            </svg>
                            Pekerjaan
                        </h2>
                        <p class="num">{{ $totalPekerjaan }}</p>
                    </div>
                </section>

                {{-- PROGRES HONOR --}}
                <section class="card chart-card" x-data="progresHonorChart(@js($progres), @js($batasHonor))">
                    <div class="chart-head">
                        <h2>
                            <svg>
                                <use href="#i-chart" />
                            </svg>
                            Progres Honor
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
                                    <div class="bar" :style="`--t:${m.t}%`"
                                        :title="`${m.nama}: ${fmtRp(m.total)} (${m.status_waktu})`">
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
                            Belum ada tugas pada status ini.
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
                    <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
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
                {{-- DAFTAR TUGAS --}}
                <div class="card">
                    <h2>
                        <svg>
                            <use href="#i-list" />
                        </svg>
                        Daftar Tugas
                    </h2>
                    <ul class="list scroll longgar">
                        @forelse ($daftarTugas as $t)
                            <li>
                                <span class="info">
                                    <strong class="nama" title="{{ $t['judul'] }}">{{ $t['judul'] }}</strong>
                                    <small>{{ $t['tanggal_label'] }}</small>
                                </span>
                                <span class="kanan">
                                    <span class="badge {{ $t['badge_kelas'] }}">{{ $t['badge_teks'] }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="kosong-item">Tidak ada tugas yang sedang berjalan.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- RIWAYAT TUGAS --}}
                <div class="card">
                    <h2>
                        <svg>
                            <use href="#i-list" />
                        </svg>
                        Riwayat Tugas
                    </h2>
                    <ul class="list scroll longgar">
                        @forelse ($riwayatTugas as $r)
                            <li>
                                <span class="info">
                                    <strong class="nama" title="{{ $r['judul'] }}">{{ $r['judul'] }}</strong>
                                    <small>{{ $r['tanggal_label'] }}</small>
                                </span>
                                <span class="kanan">
                                    <span class="badge tersedia">Selesai</span>
                                </span>
                            </li>
                        @empty
                            <li class="kosong-item">Belum ada tugas yang selesai pada bulan ini.</li>
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

                {{-- POSISI (PCL / PML) --}}
                <div class="card sebaran">
                    <h2>
                        <svg>
                            <use href="#i-pin" />
                        </svg>
                        Posisi
                    </h2>
                    <div class="posisi-placeholder">
                        <p class="seb-kosong">Fitur belum tersedia.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            /* Grafik progres honor mitra (Alpine) - filter waktu tugas & sumbu Y menyesuaikan batas honor */
            window.progresHonorChart = function(data, batasHonor) {
                const SLOT = 84;
                const SUMBU = 54;
                const NAV = 76;

                return {
                    data,
                    batasHonor: Number(batasHonor || 0),
                    pilih: 'semua',
                    buka: false,
                    halaman: 0,
                    perHal: 6,
                    paged: false,
                    opsi: [{
                            k: 'semua',
                            t: 'Semua'
                        },
                        {
                            k: 'berlangsung',
                            t: 'Berlangsung'
                        },
                        {
                            k: 'selesai',
                            t: 'Selesai'
                        },
                        {
                            k: 'akan',
                            t: 'Akan Datang'
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
                        return this.data.filter(m => this.pilih === 'semua' || m.waktu === this.pilih);
                    },
                    get jumlahHal() {
                        return Math.max(1, Math.ceil(this.terfilter.length / this.perHal));
                    },
                    get puncak() {
                        const maks = Math.max(0, ...this.terfilter.map(m => m.total));
                        return Math.max(this.batasHonor, maks, 1);
                    },
                    get ticks() {
                        const p = this.puncak;
                        return [0, 0.25, 0.5, 0.75, 1].map(f => this.fmtAxis(p * f));
                    },
                    get batang() {
                        const p = this.puncak;
                        const awal = this.halaman * this.perHal;
                        return this.terfilter.slice(awal, awal + this.perHal).map(m => ({
                            ...m,
                            t: m.total > 0 ? Math.max(8, Math.min(100, (m.total / p) * 100)) : 5,
                        }));
                    },
                    fmtAxis(v) {
                        if (v <= 0) return '0';
                        if (v >= 1000000) {
                            return Number((v / 1000000).toFixed(2)).toLocaleString('id-ID') + ' jt';
                        }
                        if (v >= 1000) {
                            return Math.round(v / 1000).toLocaleString('id-ID') + ' rb';
                        }
                        return String(Math.round(v));
                    },
                    fmtRp(v) {
                        return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
                    },
                };
            };

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
                        row.className = 'note';

                        const rentang = document.createElement('small');
                        rentang.className = 'note-rentang';
                        rentang.textContent = item.rentang;

                        const bawah = document.createElement('div');
                        bawah.className = 'note-row';

                        const judul = document.createElement('strong');
                        judul.className = 'note-judul';
                        judul.textContent = item.judul;
                        judul.title = item.judul;
                        bawah.appendChild(judul);

                        if (item.badge) {
                            const badge = document.createElement('span');
                            badge.className = 'note-badge';
                            badge.textContent = item.badge;
                            bawah.appendChild(badge);
                        }

                        row.append(rentang, bawah);
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
