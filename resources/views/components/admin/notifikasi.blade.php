{{--
    Popup tengah layar untuk pesan sukses, gagal, dan konfirmasi.
    Dipasang sekali di layout admin:  <x-admin.notifikasi />

    Dari controller:   ->with('sukses', '...')   atau   ->with('gagal', '...')
    Dari JavaScript:   popup({ jenis, judul, pesan, tombolOk, tombolBatal })  -> mengembalikan Promise (true/false)
        jenis: 'sukses' | 'gagal' | 'hapus' | 'info'
        tombolBatal: diisi = tampil tombol Batal; kosong = hanya tombol OK
--}}
<style>
    .dlg-latar {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(0, 0, 0, .45);
    }

    .dlg-latar[hidden] {
        display: none;
    }

    .dlg {
        background: #fff;
        color: #17261f;
        border-radius: 16px;
        max-width: 380px;
        width: 100%;
        padding: 28px 24px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .25);
        animation: dlg-masuk .15s ease-out;
    }

    .dlg-ikon {
        width: 56px;
        height: 56px;
        margin: 0 auto 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        font-weight: 700;
        background: #e6f4ee;
        color: #0f6b4f;
    }

    .dlg-judul {
        margin: 0 0 8px;
        font-size: 18px;
    }

    .dlg-pesan {
        margin: 0 0 20px;
        font-size: 14px;
        line-height: 1.5;
        color: #5f7068;
    }

    .dlg-aksi {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .dlg-btn {
        padding: 10px 28px;
        font: inherit;
        font-size: 15px;
        font-weight: 600;
        color: #fff;
        background: #0f6b4f;
        border: 1px solid #0f6b4f;
        border-radius: 10px;
        cursor: pointer;
    }

    .dlg-btn[hidden] {
        display: none;
    }

    .dlg-btn:focus-visible {
        outline: 2px solid #17261f;
        outline-offset: 2px;
    }

    .dlg-btn-batal {
        background: #fff;
        color: #17261f;
        border-color: #d5e0da;
    }

    /* Warna merah untuk pesan gagal dan konfirmasi hapus */
    .dlg-latar[data-jenis="gagal"] .dlg-ikon,
    .dlg-latar[data-jenis="hapus"] .dlg-ikon {
        background: #fdecea;
        color: #b3261e;
    }

    .dlg-latar[data-jenis="gagal"] .dlg-btn:not(.dlg-btn-batal),
    .dlg-latar[data-jenis="hapus"] .dlg-btn:not(.dlg-btn-batal) {
        background: #b3261e;
        border-color: #b3261e;
    }

    @keyframes dlg-masuk {
        from {
            opacity: 0;
            transform: scale(.96);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .dlg {
            animation: none;
        }
    }
</style>

<div class="dlg-latar" id="dlgLatar" data-jenis="info" hidden>
    <div class="dlg" role="dialog" aria-modal="true" aria-labelledby="dlgJudul" aria-describedby="dlgPesan">
        <div class="dlg-ikon" id="dlgIkon" aria-hidden="true"></div>
        <h3 class="dlg-judul" id="dlgJudul"></h3>
        <p class="dlg-pesan" id="dlgPesan"></p>
        <div class="dlg-aksi">
            <button type="button" class="dlg-btn dlg-btn-batal" id="dlgBatal" hidden>Batal</button>
            <button type="button" class="dlg-btn" id="dlgOk">OK</button>
        </div>
    </div>
</div>

<script>
    (function() {
        const latar = document.getElementById('dlgLatar');
        const ikon = document.getElementById('dlgIkon');
        const judul = document.getElementById('dlgJudul');
        const pesan = document.getElementById('dlgPesan');
        const tombolOk = document.getElementById('dlgOk');
        const tombolBatal = document.getElementById('dlgBatal');
        const simbol = {
            sukses: '✓',
            gagal: '!',
            hapus: '!',
            info: 'i'
        };

        let selesai = null;
        let fokusSebelumnya = null;

        function tutup(hasil) {
            latar.hidden = true;
            document.removeEventListener('keydown', tombolKeyboard);
            if (fokusSebelumnya && fokusSebelumnya.focus) fokusSebelumnya.focus();
            const lanjut = selesai;
            selesai = null;
            if (lanjut) lanjut(hasil);
        }

        function tombolKeyboard(e) {
            if (e.key === 'Escape') tutup(false);
        }

        tombolOk.addEventListener('click', () => tutup(true));
        tombolBatal.addEventListener('click', () => tutup(false));
        latar.addEventListener('click', e => {
            if (e.target === latar) tutup(false);
        });

        window.popup = function(opsi) {
            return new Promise(resolve => {
                if (selesai) selesai(false); // tutup popup sebelumnya jika masih terbuka
                selesai = resolve;

                const jenis = opsi.jenis || 'info';
                latar.dataset.jenis = jenis;
                ikon.textContent = simbol[jenis] || 'i';
                judul.textContent = opsi.judul || '';
                pesan.textContent = opsi.pesan || '';
                tombolOk.textContent = opsi.tombolOk || 'OK';
                tombolBatal.textContent = opsi.tombolBatal || 'Batal';
                tombolBatal.hidden = !opsi.tombolBatal;

                fokusSebelumnya = document.activeElement;
                latar.hidden = false;
                document.addEventListener('keydown', tombolKeyboard);

                // Pada konfirmasi, fokus awal di Batal agar tidak terhapus tanpa sengaja
                (opsi.tombolBatal ? tombolBatal : tombolOk).focus();
            });
        }

        /* Pesan dari controller */
        const antrean = [];
        @if (session('sukses'))
            antrean.push({
                jenis: 'sukses',
                judul: 'Berhasil',
                pesan: @json(session('sukses'))
            });
            /* Hapus draf form Tambah Mitra karena data sudah tersimpan */
            try {
                sessionStorage.removeItem('draftTambahMitra');
            } catch (e) {}
        @endif
        @if (session('gagal'))
            antrean.push({
                jenis: 'gagal',
                judul: 'Tidak berhasil',
                pesan: @json(session('gagal'))
            });
        @endif
        antrean.reduce((p, o) => p.then(() => window.popup(o)), Promise.resolve());
    })();
</script>
