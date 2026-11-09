@once
    @push('scripts')
        <script>
            (function() {
                const panel = document.getElementById('filterPanel');
                const form = document.getElementById('filterForm');
                const overlay = document.getElementById('filterOverlay');
                const toggle = document.getElementById('filterToggle');
                if (!panel || !form || !overlay || !toggle) return;

                /* ===== Buka / tutup panel ===== */
                function buka() {
                    panel.classList.add('open');
                    overlay.classList.add('open');
                    panel.setAttribute('aria-hidden', 'false');
                    toggle.setAttribute('aria-expanded', 'true');
                }

                function tutup() {
                    panel.classList.remove('open');
                    overlay.classList.remove('open');
                    panel.setAttribute('aria-hidden', 'true');
                    toggle.setAttribute('aria-expanded', 'false');
                }
                toggle.addEventListener('click', buka);
                overlay.addEventListener('click', tutup);
                document.getElementById('filterCancel').addEventListener('click', tutup);
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') tutup();
                });

                /* ===== Slider rentang: digeser atau diketik ===== */
                const angka = v => parseInt(String(v).replace(/\D/g, ''), 10) || 0;
                const titik = n => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                const batas = (n, a, b) => Math.min(Math.max(n, a), b);
                // lebar kolom angka mengikuti isinya
                const ukur = t => {
                    t.style.width = Math.max(2, t.value.length + 1) + 'ch';
                };

                const rentang = [...form.querySelectorAll('[data-range]')].map(el => {
                    const min = Number(el.dataset.min) || 0;
                    const max = Number(el.dataset.max) || 0;
                    const rupiah = el.dataset.format === 'rupiah';
                    const fmt = n => rupiah ? titik(n) : String(n);
                    const slider = el.querySelector('.range-slider');
                    const [rLo, rHi] = el.querySelectorAll('input[type="range"]');
                    const [tLo, tHi] = el.querySelectorAll('.range-pill input');
                    const mode = el.dataset.mode ? document.getElementById(el.dataset.mode) : null;

                    let lo = batas(tLo.value === '' ? min : angka(tLo.value), min, max);
                    let hi = batas(tHi.value === '' ? max : angka(tHi.value), min, max);
                    if (lo > hi) lo = hi;

                    function gambar(tulis) {
                        rLo.value = lo;
                        rHi.value = hi;
                        if (tulis) {
                            tLo.value = fmt(lo);
                            tHi.value = fmt(hi);
                        }
                        ukur(tLo);
                        ukur(tHi);
                        const lebar = (max - min) || 1;
                        const a = (lo - min) / lebar;
                        slider.style.setProperty('--lo-n', a);
                        slider.style.setProperty('--hi-n', (hi - min) / lebar);
                        // jika dua thumb bertumpuk di sisi kanan, thumb kiri harus di atas agar bisa digeser
                        rLo.style.zIndex = a > 0.5 ? 4 : 3;
                        rHi.style.zIndex = 3;
                    }

                    rLo.addEventListener('input', () => {
                        lo = Math.min(Number(rLo.value), hi);
                        gambar(true);
                    });
                    rHi.addEventListener('input', () => {
                        hi = Math.max(Number(rHi.value), lo);
                        gambar(true);
                    });

                    tLo.addEventListener('input', () => {
                        if (tLo.value !== '') tLo.value = fmt(angka(tLo.value));
                        lo = Math.min(batas(tLo.value === '' ? min : angka(tLo.value), min, max), hi);
                        gambar(false);
                    });
                    tHi.addEventListener('input', () => {
                        if (tHi.value !== '') tHi.value = fmt(angka(tHi.value));
                        hi = Math.max(batas(tHi.value === '' ? max : angka(tHi.value), min, max), lo);
                        gambar(false);
                    });
                    // saat selesai mengetik, rapikan teks sesuai batas
                    tLo.addEventListener('change', () => gambar(true));
                    tHi.addEventListener('change', () => gambar(true));
                    [tLo, tHi].forEach(t => t.addEventListener('focus', () => t.select()));

                    gambar(true);

                    return {
                        // Nilai di ujung rentang tidak dikirim (artinya: tanpa batas)
                        siap() {
                            tLo.disabled = lo <= min;
                            tHi.disabled = hi >= max;
                            tLo.value = String(lo);
                            tHi.value = String(hi);
                            if (mode) mode.disabled = tLo.disabled && tHi.disabled;
                        },
                        pulih() {
                            tLo.disabled = false;
                            tHi.disabled = false;
                            if (mode) mode.disabled = false;
                            gambar(true);
                        },
                        reset() {
                            lo = min;
                            hi = max;
                            gambar(true);
                        }
                    };
                });

                /* ===== Chip pilihan tunggal (Wilayah) ===== */
                const kelompokChip = [...form.querySelectorAll('[data-chip-group]')].map(grup => {
                    const input = document.getElementById(grup.dataset.target);

                    function kosongkan() {
                        grup.querySelectorAll('.chip-opt').forEach(c => {
                            c.classList.remove('selected');
                            c.setAttribute('aria-pressed', 'false');
                        });
                        input.value = '';
                    }

                    grup.addEventListener('click', e => {
                        const chip = e.target.closest('.chip-opt');
                        if (!chip) return;
                        const sudah = chip.classList.contains('selected');
                        kosongkan();
                        if (!sudah) {
                            chip.classList.add('selected');
                            chip.setAttribute('aria-pressed', 'true');
                            input.value = chip.dataset.value;
                        }
                    });

                    return {
                        kosongkan
                    };
                });

                /* ===== Cari -> chip (Tugas, Pekerjaan, Koordinator) ===== */
                const kelompokTugas = [...form.querySelectorAll('[data-tugas]')].map(kotak => {
                    const namaField = kotak.dataset.nama;
                    const banyak = kotak.dataset.banyak === '1';
                    let opsi = [];
                    try {
                        opsi = JSON.parse(kotak.dataset.opsi || '[]');
                    } catch (e) {}

                    const cari = kotak.querySelector('.tugas-search input');
                    const saran = kotak.querySelector('.tugas-saran');
                    const chips = kotak.querySelector('.filter-chips');
                    let indeks = -1;

                    const labelDari = v => (opsi.find(o => String(o.v) === String(v)) || {
                        t: v
                    }).t;
                    const dipilih = () => [...chips.querySelectorAll('.filter-chip')].map(c => c.dataset.nilai);

                    function tambahChip(nilai) {
                        nilai = String(nilai || '');
                        if (!nilai || chips.querySelector('[data-nilai="' + CSS.escape(nilai) + '"]')) return;
                        if (!banyak) chips.innerHTML = ''; // pilihan tunggal: ganti chip lama

                        const label = labelDari(nilai);

                        const chip = document.createElement('span');
                        chip.className = 'filter-chip';
                        chip.dataset.nilai = nilai;

                        const teks = document.createElement('span');
                        teks.textContent = label;

                        const hapus = document.createElement('button');
                        hapus.type = 'button';
                        hapus.setAttribute('aria-label', 'Hapus ' + label);
                        hapus.textContent = '×';
                        hapus.addEventListener('click', () => chip.remove());

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = namaField;
                        hidden.value = nilai;

                        chip.append(teks, hapus, hidden);
                        chips.appendChild(chip);
                    }

                    function pilih(nilai) {
                        tambahChip(nilai);
                        cari.value = '';
                        saran.hidden = true;
                    }

                    function isiSaran() {
                        const kata = cari.value.trim().toLowerCase();
                        const sudah = dipilih();
                        const cocok = opsi.filter(o =>
                            !sudah.includes(String(o.v)) && String(o.t).toLowerCase().includes(kata));

                        saran.innerHTML = '';
                        indeks = -1;

                        if (!cocok.length) {
                            const li = document.createElement('li');
                            li.className = 'kosong';
                            li.textContent = opsi.length ? 'Tidak ada yang cocok' : 'Belum ada data';
                            saran.appendChild(li);
                        } else {
                            cocok.forEach(o => {
                                const li = document.createElement('li');
                                const tombol = document.createElement('button');
                                tombol.type = 'button';
                                tombol.textContent = o.t;
                                tombol.dataset.v = o.v;
                                tombol.addEventListener('mousedown', e => {
                                    e.preventDefault();
                                    pilih(o.v);
                                });
                                li.appendChild(tombol);
                                saran.appendChild(li);
                            });
                        }
                        saran.hidden = false;
                    }

                    // jaga fokus tetap di kolom cari saat daftar diklik atau digulir
                    saran.addEventListener('mousedown', e => e.preventDefault());
                    cari.addEventListener('focus', isiSaran);
                    cari.addEventListener('input', isiSaran);
                    cari.addEventListener('blur', () => {
                        saran.hidden = true;
                    });

                    cari.addEventListener('keydown', e => {
                        const item = [...saran.querySelectorAll('button')];

                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                            e.preventDefault();
                            if (!item.length) return;
                            indeks = (indeks + (e.key === 'ArrowDown' ? 1 : -1) + item.length) % item.length;
                            item.forEach((b, i) => b.classList.toggle('aktif', i === indeks));
                            item[indeks].scrollIntoView({
                                block: 'nearest'
                            });
                        } else if (e.key === 'Enter') {
                            e.preventDefault(); // jangan kirim form
                            const target = item[indeks] || item[0];
                            if (target && !saran.hidden) pilih(target.dataset.v);
                        } else if (e.key === 'Escape' && !saran.hidden) {
                            e.stopPropagation(); // tutup daftar saja, panel tetap terbuka
                            saran.hidden = true;
                        }
                    });

                    try {
                        JSON.parse(chips.dataset.selected || '[]').forEach(tambahChip);
                    } catch (e) {}

                    return {
                        kosongkan() {
                            chips.innerHTML = '';
                            cari.value = '';
                            saran.hidden = true;
                        }
                    };
                });

                /* ===== Kirim & reset ===== */
                form.addEventListener('submit', () => {
                    rentang.forEach(r => r.siap());
                    // kembalikan tampilan setelah data diambil (jaga jika halaman dipulihkan dari cache)
                    setTimeout(() => rentang.forEach(r => r.pulih()), 0);
                });
                window.addEventListener('pageshow', e => {
                    if (e.persisted) rentang.forEach(r => r.pulih());
                });

                document.getElementById('filterReset').addEventListener('click', () => {
                    rentang.forEach(r => r.reset());
                    kelompokChip.forEach(c => c.kosongkan());
                    kelompokTugas.forEach(t => t.kosongkan());
                    rentang.forEach(r => r.siap());
                    form.submit();
                });
            })();
        </script>
    @endpush
@endonce
