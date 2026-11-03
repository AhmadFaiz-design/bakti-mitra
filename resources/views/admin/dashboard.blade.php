        <!-- KONTEN -->
        <x-admin.main>
            <header class="topbar">
                <h1>Dashboard</h1>
                {{-- <button class="pill">Bulan <svg>
                        <use href="#i-down" />
                    </svg></button> --}}
                <label class="search"><svg>
                        <use href="#i-search" />
                    </svg><input placeholder="Apa yang perlu dicari..."></label>
                <div class="user"><span class="bell"><svg>
                            <use href="#i-bell" />
                        </svg></span>Alyssa<div class="avatar"></div>
                </div>
            </header>

            @php
                $today = now();
                $calStart = $today->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
                $calEnd = $today->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
                $namaBulan = $today->copy()->locale('id')->translatedFormat('F');

                // CONTOH DATA: ganti dengan data dari database.
                // Format: 'Y-m-d' => [ ['judul' => '...', 'deadline' => true/false], ... ]
                $tugas = [
                    $today->format('Y-m-d') => [
                        ['judul' => 'Pengolahan Dokumen Pencacahan Susenas September', 'deadline' => false],
                        ['judul' => 'Pengolahan Dokumen Pencacahan Susenas September', 'deadline' => false],
                    ],
                    $today->copy()->addDays(4)->format('Y-m-d') => [
                        ['judul' => 'Rekap Data Sakernas', 'deadline' => true],
                    ],
                    $today->copy()->addDays(9)->format('Y-m-d') => [
                        ['judul' => 'Validasi Dokumen Susenas', 'deadline' => true],
                        ['judul' => 'Entri Data Survei Harga', 'deadline' => false],
                    ],
                ];
            @endphp

            <div class="dash-top">
                <div class="dash-left">
                    <section class="stats">
                        <div class="card hero">
                            <h2><svg>
                                    <use href="#i-ring" />
                                </svg>Progres Tugas Mitra</h2>
                            <p class="num" style="font-size:34px;font-weight:600;margin:10px 0">32 %</p>
                            <div class="progress"><span></span></div>
                        </div>
                        <div class="card stat">
                            <h2><svg>
                                    <use href="#i-user" />
                                </svg>Total Mitra</h2>
                            <p class="num">123</p><span class="badge lime">120 Mitra Aktif</span>
                        </div>
                        <div class="card stat">
                            <h2><svg>
                                    <use href="#i-user" />
                                </svg>Total Koor</h2>
                            <p class="num">13</p><span class="badge">2 Aktif</span>
                        </div>
                    </section>

                    <section class="card">
                        <div class="chart-head">
                            <h2><svg>
                                    <use href="#i-chart" />
                                </svg>Progres Mitra</h2>

                            <div class="dropdown" x-data="{ open: false, selected: 'Semua' }" @click.outside="open = false">
                                <button type="button" class="pill" @click="open = !open" :aria-expanded="open">
                                    <span x-text="selected"></span>
                                    <svg :class="{ 'rotate': open }">
                                        <use href="#i-down" />
                                    </svg>
                                </button>

                                <ul class="dropdown-menu" x-show="open" x-transition.opacity.duration.150ms x-cloak>
                                    @foreach (['Semua', 'Aktif', 'Tidak Aktif', 'Selesai', 'Berlangsung'] as $opsi)
                                        <li>
                                            <button type="button"
                                                :class="{ 'active': selected === '{{ $opsi }}' }"
                                                @click="selected = '{{ $opsi }}'; open = false">
                                                {{ $opsi }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="chart">
                            <div class="axis"><span>10</span><span>20</span><span>30</span><span>40</span></div>
                            <div class="bar" style="--t:90%"><i style="--v:62%"></i></div>
                            <div class="bar" style="--t:90%"><i style="--v:36%"></i></div>
                            <div class="bar top" style="--t:90%"><i style="--v:82%"></i></div>
                            <div class="bar" style="--t:90%"><i style="--v:78%"></i></div>
                            <div class="bar top" style="--t:95%"><i style="--v:92%"></i></div>
                            <div class="bar" style="--t:90%"><i style="--v:78%"></i></div>
                            <div class="bar" style="--t:90%"><i style="--v:58%"></i></div>
                            <div class="bar top" style="--t:90%"><i style="--v:78%"></i></div>
                            <div class="bar" style="--t:95%"><i style="--v:20%"></i></div>
                            <div class="bar" style="--t:90%"><i style="--v:45%"></i></div>
                        </div>
                        <div class="labels">
                            <span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span>
                            <span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span><span>Malik</span>
                        </div>
                    </section>
                </div>

                {{-- KALENDER --}}
                <aside class="card calendar-card">
                    <h2><svg>
                            <use href="#i-cal" />
                        </svg>{{ $namaBulan }}</h2>

                    <div class="cal-summary">
                        <div class="box-cal" ><small>Deadline</small><strong>10</strong></div>
                        <div><small>Selesai</small><strong>15</strong></div>
                        <div><small>Berlangsung</small><strong>13</strong></div>
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

            <section class="bottom">
                <div class="card">
                    <h2><svg>
                            <use href="#i-user" />
                        </svg>Status Mitra</h2>
                    <ul class="list">
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge lime">Aktif</span></li>
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge">Tidak Aktif</span></li>
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge lime">Aktif</span></li>
                        <li><span class="dot"></span>Nicholas Saputra<span class="badge lime">Aktif</span></li>
                    </ul>
                </div>
                <div class="card">
                    <h2><svg>
                            <use href="#i-list" />
                        </svg>Aktivitas Penugasan</h2>
                    <ul class="list">
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge">2
                                Mitra</span></li>
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge">2
                                Mitra</span></li>
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge dark">10
                                Mitra</span></li>
                        <li class="task">Pengolahan Dokumen Pencacahan Susenas September<span class="badge">2
                                Mitra</span></li>
                    </ul>
                </div>
            </section>
            @push('scripts')
                <script>
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
                                p.textContent = item.judul;
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
