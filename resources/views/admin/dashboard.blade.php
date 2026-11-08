<x-admin.main>
    <div class="dash">
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
                            <a href="{{ route('admin.dashboard', ['bulan' => $o['k']]) }}"
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

                {{-- PROGRES MITRA --}}
                <section class="card chart-card" x-data="progresChart(@js($progres))">
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
                                        <i :style="`--v:${m.v}%`"></i>
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

        {{-- BARIS BAWAH: 4 KARTU --}}
        <section class="dash-bottom">
            <div class="pair">
                {{-- STATUS MITRA --}}
                <div class="card">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Status Mitra
                        <span class="card-sub">{{ $statusMitraLabel }}</span>
                    </h2>
                    <ul class="list scroll longgar">
                        @forelse ($statusMitra as $m)
                            <li>
                                <span class="dot"></span>
                                <span class="info">
                                    <strong class="nama">{{ $m['nama'] }}</strong>
                                    <small>{{ $m['sobat'] }}@if ($m['wilayah'] !== '-')
                                            · {{ $m['wilayah'] }}
                                        @endif
                                    </small>
                                </span>
                                <span class="kanan">
                                    <small>Rp {{ number_format($m['honor'], 0, ',', '.') }}</small>
                                    <span
                                        class="badge {{ $m['status'] === 'Tersedia' ? 'lime' : '' }}">{{ $m['status'] }}</span>
                                </span>
                            </li>
                        @empty
                            <li>Belum ada mitra yang tersedia.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- AKTIVITAS PENUGASAN --}}
                <div class="card">
                    <h2><svg>
                            <use href="#i-list" />
                        </svg>Aktivitas Penugasan</h2>
                    <ul class="list scroll longgar">
                        @forelse ($aktivitas as $a)
                            <li class="task">
                                <span class="judul" title="{{ $a->judul }}">
                                    <strong>{{ $a->judul }}</strong>
                                    <small>{{ $a->koordinator ? $a->koordinator . ' · ' : '' }}{{ $a->periode }}</small>
                                </span>
                                <span class="mt">+{{ $a->mitra }} Mitra</span>
                            </li>
                        @empty
                            <li>Belum ada tugas pada bulan ini.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="dash-side">
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

                {{-- SEBARAN WILAYAH --}}
                @php $warnaSeb = ['#8A79F1', '#D6F55B', '#1F1A3D', '#B9AEF8', '#5B49C9', '#9993AD']; @endphp
                <div class="card sebaran">
                    <h2><svg>
                            <use href="#i-cal" />
                        </svg>Sebaran Wilayah
                        <span class="card-sub">{{ $totalSebaran }} mitra</span>
                    </h2>

                    @if ($sebaran->isEmpty())
                        <p class="seb-kosong">Belum ada data wilayah.</p>
                    @else
                        <div class="seb-bar" role="img" aria-label="Proporsi mitra aktif per wilayah">
                            @foreach ($sebaran as $i => $w)
                                <span style="--c: {{ $warnaSeb[$i % 6] }}; --n: {{ $w->jumlah }}"
                                    title="{{ $w->wilayah }}: {{ $w->jumlah }} mitra"></span>
                            @endforeach
                        </div>

                        <ul class="seb-legend scroll">
                            @foreach ($sebaran as $i => $w)
                                <li style="--c: {{ $warnaSeb[$i % 6] }}">
                                    <span class="nm"><i></i><span class="nm-t"
                                            title="{{ $w->wilayah }}">{{ $w->wilayah }}</span></span>
                                    <span
                                        class="vl">{{ $w->jumlah }}<em>{{ round(($w->jumlah / max(1, $totalSebaran)) * 100) }}%</em></span>
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
            /* Grafik progres mitra (Alpine) */
            window.progresChart = function(data) {
                const SLOT = 78; // lebar satu kolom (batang + label)
                const SUMBU = 44; // ruang angka sumbu di kiri
                const NAV = 76; // ruang dua tombol panah (38px kiri + 38px kanan)

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
                    // Hitung berapa batang yang muat; tombol panah hanya muncul jika tidak semua muat
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
                    // Selang sumbu dihitung dari seluruh data terfilter agar skala tidak berubah antar halaman
                    get langkah() {
                        const maks = Math.max(0, ...this.terfilter.map(m => m.total));
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
                        const awal = this.halaman * this.perHal;
                        return this.terfilter.slice(awal, awal + this.perHal).map(m => ({
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

            /* Kalender: tugas yang berjalan pada tanggal terpilih */
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

                        if (item.mitra) {
                            const m = document.createElement('span');
                            m.className = 'note-mitra';
                            m.textContent = item.mitra + ' mitra';
                            meta.appendChild(m);
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

            /* Perlu Perhatian: efek pudar di bawah jika masih ada isi yang bisa di-scroll */
            // (function() {
            //     const l = document.getElementById('perhatianList');
            //     if (!l) return;

            //     const cek = () => {
            //         const lebih = l.scrollHeight > l.clientHeight + 1;
            //         const habis = l.scrollTop + l.clientHeight >= l.scrollHeight - 2;
            //         l.classList.toggle('more', lebih && !habis);
            //     };

            //     l.addEventListener('scroll', cek);
            //     window.addEventListener('resize', cek);
            //     if ('ResizeObserver' in window) new ResizeObserver(cek).observe(l);
            //     cek();
            // })();

            // /* Daftar dipangkas otomatis agar muat di kartu (tanpa scroll, tanpa baris terpotong) */
            // (function() {
            //     const dash = document.querySelector('.dash');
            //     let rafId = null;

            //     function fit(list) {
            //         const items = [...list.children];
            //         items.forEach(li => li.hidden = false);
            //         for (let i = items.length - 1; i > 0 && list.scrollHeight > list.clientHeight + 1; i--) {
            //             items[i].hidden = true;
            //         }
            //     }

            //     function jalan() {
            //         cancelAnimationFrame(rafId);
            //         rafId = requestAnimationFrame(() => document.querySelectorAll('.dash .fit').forEach(fit));
            //     }

            //     jalan();
            //     window.addEventListener('resize', jalan);
            //     if (document.fonts && document.fonts.ready) document.fonts.ready.then(jalan);
            //     if ('ResizeObserver' in window && dash) new ResizeObserver(jalan).observe(dash);
            // })();

            // /* Perlu Perhatian: tombol panah hanya muncul jika isinya tidak muat */
            // (function() {
            //     const list = document.getElementById('perhatianList');
            //     const btn = document.getElementById('perhatianNext');
            //     if (!list || !btn) return;

            //     const perlu = () => {
            //         btn.hidden = !(list.scrollHeight > list.clientHeight + 1);
            //     };

            //     btn.addEventListener('click', () => {
            //         const bawah = list.scrollTop + list.clientHeight;
            //         const target = [...list.children].find(li => li.offsetTop + li.offsetHeight > bawah + 1);
            //         list.scrollTo({
            //             top: target ? target.offsetTop : 0,
            //             behavior: 'smooth'
            //         });
            //     });

            //     window.addEventListener('resize', perlu);
            //     if ('ResizeObserver' in window) new ResizeObserver(perlu).observe(list);
            //     perlu();
            // })();
        </script>
    @endpush
</x-admin.main>
