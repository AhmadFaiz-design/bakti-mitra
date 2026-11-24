@once

    <style>
        /* ========================================
                   GLOBAL POPUP (ADMIN, KOORDINATOR, MITRA)
                ======================================== */

        .app-popup-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(31, 26, 61, .42);
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease;
        }

        .app-popup-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        .app-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 2010;
            width: min(420px, calc(100vw - 32px));
            padding: 28px 24px 24px;
            border: 1px solid var(--tint);
            border-radius: 26px;
            background: var(--white);
            color: var(--ink);
            text-align: center;
            box-shadow: 0 20px 50px rgba(31, 26, 61, .22);
            transform: translate(-50%, -46%) scale(.96);
            opacity: 0;
            visibility: hidden;
            transition: opacity .18s ease, transform .18s ease, visibility .18s;
        }

        .app-popup.open {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .app-popup-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 14px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--tint);
            color: var(--primary-dark);
        }

        .app-popup-icon.merah {
            background: #FDE8E9;
            color: #E5484D;
        }

        .app-popup-icon.hijau {
            background: #E7F7ED;
            color: #218653;
        }

        .app-popup-icon.kuning {
            background: #FFF6DB;
            color: #A86400;
        }

        .app-popup-icon svg {
            width: 28px;
            height: 28px;
            stroke-width: 2;
        }

        .app-popup-icon svg[hidden] {
            display: none;
        }

        .app-popup-title {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.35;
            color: var(--ink);
        }

        .app-popup-msg {
            margin: 0 0 22px;
            font-size: 13.5px;
            line-height: 1.55;
            color: var(--muted);
            white-space: pre-line;
        }

        .app-popup-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .app-popup-btn {
            flex: 1;
            min-width: 115px;
            max-width: 180px;
            height: 42px;
            padding: 0 18px;
            border-radius: 99px;
            font: 500 13px 'Poppins', sans-serif;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }

        .app-popup-btn[hidden] {
            display: none;
        }

        .app-popup-batal {
            border: 1px solid #D5D0E5;
            background: var(--white);
            color: var(--ink);
        }

        .app-popup-batal:hover {
            background: var(--tint);
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        .app-popup-ok {
            border: 0;
            background: var(--primary);
            color: var(--white);
        }

        .app-popup-ok:hover {
            background: var(--primary-dark);
        }

        .app-popup-ok.merah {
            background: #E5484D;
            color: var(--white);
        }

        .app-popup-ok.merah:hover {
            background: #C93A3F;
        }

        .app-popup-btn:focus-visible {
            outline: 2px solid var(--primary-dark);
            outline-offset: 2px;
        }
    </style>
    <div class="app-popup-overlay" id="appPopupOverlay"></div>

    <div class="app-popup" id="appPopup" role="alertdialog" aria-modal="true" aria-labelledby="appPopupTitle"
        aria-describedby="appPopupMsg" aria-hidden="true">

        <div class="app-popup-icon" id="appPopupIcon" aria-hidden="true">
            <svg data-icon="hapus" viewBox="0 0 24 24" hidden>
                <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
            </svg>
            <svg data-icon="gagal" viewBox="0 0 24 24" hidden>
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v5M12 16.5h.01" />
            </svg>
            <svg data-icon="sukses" viewBox="0 0 24 24" hidden>
                <circle cx="12" cy="12" r="9" />
                <path d="M8.5 12.5l2.5 2.5 4.5-5" />
            </svg>
            <svg data-icon="konfirmasi" viewBox="0 0 24 24" hidden>
                <path d="M12 3l10 18H2L12 3zM12 10v4M12 17.5h.01" />
            </svg>
            <svg data-icon="info" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 11v5M12 8h.01" />
            </svg>
            <svg data-icon="logout" viewBox="0 0 24 24" hidden>
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
            </svg>
        </div>

        <h3 class="app-popup-title" id="appPopupTitle"></h3>
        <p class="app-popup-msg" id="appPopupMsg"></p>

        <div class="app-popup-actions">
            <button type="button" class="app-popup-btn app-popup-batal" id="appPopupBatal">Batal</button>
            <button type="button" class="app-popup-btn app-popup-ok" id="appPopupOk">OK</button>
        </div>
    </div>

    <script>
        (function() {
            if (window.__appPopupReady) return;
            window.__appPopupReady = true;

            const overlay = document.getElementById('appPopupOverlay');
            const modal = document.getElementById('appPopup');
            const iconBox = document.getElementById('appPopupIcon');
            const titleEl = document.getElementById('appPopupTitle');
            const msgEl = document.getElementById('appPopupMsg');
            const btnBatal = document.getElementById('appPopupBatal');
            const btnOk = document.getElementById('appPopupOk');

            const KONFIG = {
                logout: {
                    ikon: 'logout',
                    warnaIcon: 'merah',
                    warnaOk: 'merah',
                    judul: 'Yakin ingin logout?',
                    ok: 'Ya, logout',
                    batal: 'Batal'
                },
                hapus: {
                    ikon: 'hapus',
                    warnaIcon: 'merah',
                    warnaOk: 'merah',
                    judul: 'Hapus data?',
                    ok: 'Ya, hapus',
                    batal: 'Batal'
                },
                konfirmasi: {
                    ikon: 'konfirmasi',
                    warnaIcon: 'kuning',
                    warnaOk: 'ungu',
                    judul: 'Konfirmasi',
                    ok: 'Ya, lanjutkan',
                    batal: 'Batal'
                },
                peringatan: {
                    ikon: 'konfirmasi',
                    warnaIcon: 'kuning',
                    warnaOk: 'ungu',
                    judul: 'Perhatian',
                    ok: 'Ya, lanjutkan',
                    batal: 'Batal'
                },
                gagal: {
                    ikon: 'gagal',
                    warnaIcon: 'merah',
                    warnaOk: 'merah',
                    judul: 'Tidak berhasil',
                    ok: 'OK',
                    batal: null
                },
                error: {
                    ikon: 'gagal',
                    warnaIcon: 'merah',
                    warnaOk: 'merah',
                    judul: 'Tidak berhasil',
                    ok: 'OK',
                    batal: null
                },
                sukses: {
                    ikon: 'sukses',
                    warnaIcon: 'hijau',
                    warnaOk: 'ungu',
                    judul: 'Berhasil',
                    ok: 'OK',
                    batal: null
                },
                success: {
                    ikon: 'sukses',
                    warnaIcon: 'hijau',
                    warnaOk: 'ungu',
                    judul: 'Berhasil',
                    ok: 'OK',
                    batal: null
                },
                info: {
                    ikon: 'info',
                    warnaIcon: 'ungu',
                    warnaOk: 'ungu',
                    judul: 'Informasi',
                    ok: 'OK',
                    batal: null
                }
            };

            const antrean = [];
            let terbuka = false;
            let aktif = null;
            let timerAntre = null;

            function tampilkanBerikutnya() {
                if (terbuka || antrean.length === 0) return;

                const {
                    opsi,
                    resolve
                } = antrean.shift();
                const raw = typeof opsi === 'string' ? {
                    pesan: opsi
                } : (opsi || {});
                const jenis = String(raw.jenis || 'info').toLowerCase();
                const cfg = KONFIG[jenis] || KONFIG.info;

                const teksBatal = raw.tombolBatal !== undefined ? raw.tombolBatal : cfg.batal;
                const adaBatal = Boolean(teksBatal);

                iconBox.className = 'app-popup-icon ' + cfg.warnaIcon;
                iconBox.querySelectorAll('svg[data-icon]').forEach(svg => {
                    svg.hidden = svg.dataset.icon !== cfg.ikon;
                });

                titleEl.textContent = raw.judul || cfg.judul;
                msgEl.textContent = raw.pesan || '';

                btnOk.textContent = raw.tombolOk || cfg.ok;
                btnOk.className = 'app-popup-btn app-popup-ok' + (cfg.warnaOk === 'merah' ? ' merah' : '');

                btnBatal.hidden = !adaBatal;
                if (adaBatal) btnBatal.textContent = teksBatal;

                const fokusSebelum = document.activeElement;
                terbuka = true;
                aktif = {
                    resolve,
                    fokusSebelum
                };

                overlay.classList.add('open');
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');

                requestAnimationFrame(() => {
                    ((jenis === 'hapus' || jenis === 'logout') && adaBatal ? btnBatal : btnOk).focus();
                });
            }

            function tutup(hasil) {
                if (!terbuka || !aktif) return;
                const {
                    resolve,
                    fokusSebelum
                } = aktif;

                terbuka = false;
                aktif = null;

                overlay.classList.remove('open');
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');

                if (fokusSebelum && typeof fokusSebelum.focus === 'function') {
                    fokusSebelum.focus();
                }

                resolve(hasil);

                if (antrean.length > 0) {
                    clearTimeout(timerAntre);
                    timerAntre = setTimeout(tampilkanBerikutnya, 190);
                }
            }

            btnOk.addEventListener('click', () => tutup(true));
            btnBatal.addEventListener('click', () => tutup(false));
            overlay.addEventListener('click', () => tutup(false));

            document.addEventListener('keydown', e => {
                if (!terbuka) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    tutup(false);
                } else if (e.key === 'Tab') {
                    const tombol = [btnBatal, btnOk].filter(el => !el.hidden);
                    if (tombol.length < 2) {
                        e.preventDefault();
                        tombol[0]?.focus();
                        return;
                    }
                    const pertama = tombol[0];
                    const terakhir = tombol[tombol.length - 1];
                    if (e.shiftKey && document.activeElement === pertama) {
                        e.preventDefault();
                        terakhir.focus();
                    } else if (!e.shiftKey && document.activeElement === terakhir) {
                        e.preventDefault();
                        pertama.focus();
                    }
                }
            });

            window.popup = function(opsi) {
                return new Promise(resolve => {
                    antrean.push({
                        opsi,
                        resolve
                    });
                    if (!terbuka) tampilkanBerikutnya();
                });
            };
        })
        ();
    </script>
@endonce

@php
    $flashPopup = null;
    if (session('sukses') || session('success')) {
        $flashPopup = [
            'jenis' => 'sukses',
            'judul' => 'Berhasil',
            'pesan' => session('sukses') ?? session('success'),
        ];
    } elseif (session('gagal') || session('error')) {
        $flashPopup = [
            'jenis' => 'gagal',
            'judul' => 'Tidak berhasil',
            'pesan' => session('gagal') ?? session('error'),
        ];
    } elseif (session('info')) {
        $flashPopup = [
            'jenis' => 'info',
            'judul' => 'Informasi',
            'pesan' => session('info'),
        ];
    }
@endphp

@if ($flashPopup)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!document.getElementById('popupGagal')) {
                window.popup(@json($flashPopup));
            }
        });
    </script>
@endif
