@props(['action', 'body', 'noun', 'catatan' => ''])

<div class="bulk-bar" id="bulkBar" hidden>
    <div class="bulk-info">
        <strong><span id="bulkCount">0</span> {{ $noun }} dipilih</strong>
    </div>
    <div class="bulk-actions">
        <button type="button" class="danger-btn" id="bulkHapus">
            <svg viewBox="0 0 24 24">
                <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
            </svg>
            Hapus
        </button>
        <button type="button" class="outline-btn" id="bulkReset">Batal pilih</button>
    </div>
</div>

<form id="bulkHapusForm" method="POST" action="{{ $action }}" style="display:none">
    @csrf
    @method('DELETE')
    <div id="bulkIds"></div>
</form>

@push('scripts')
    <script>
        (function() {
            const NOUN = @json($noun);
            const CATATAN = @json($catatan);

            const semua = document.getElementById('selectAll');
            const bar = document.getElementById('bulkBar');
            const hitung = document.getElementById('bulkCount');
            const form = document.getElementById('bulkHapusForm');
            const badan = document.getElementById(@json($body));
            if (!semua || !badan) return;

            const baris = () => [...badan.querySelectorAll('.row-check')];

            function segarkan() {
                const cek = baris();
                const n = cek.filter(c => c.checked).length;
                semua.checked = cek.length > 0 && n === cek.length;
                semua.indeterminate = n > 0 && n < cek.length;
                bar.hidden = n === 0;
                hitung.textContent = n;
            }

            semua.addEventListener('change', () => {
                baris().forEach(c => c.checked = semua.checked);
                segarkan();
            });

            badan.addEventListener('change', e => {
                if (e.target.classList.contains('row-check')) segarkan();
            });

            document.getElementById('bulkReset').addEventListener('click', () => {
                baris().forEach(c => c.checked = false);
                segarkan();
            });

            document.getElementById('bulkHapus').addEventListener('click', async () => {
                const dipilih = baris().filter(c => c.checked);
                if (dipilih.length === 0) return;

                const teks = dipilih.length + ' ' + NOUN +
                    ' akan dihapus permanen dan tidak bisa dikembalikan.' +
                    (CATATAN ? ' ' + CATATAN : '');

                const lanjut = typeof popup === 'function' ?
                    await popup({
                        jenis: 'hapus',
                        judul: 'Hapus ' + dipilih.length + ' ' + NOUN + '?',
                        pesan: teks,
                        tombolOk: 'Ya, hapus',
                        tombolBatal: 'Batal'
                    }) :
                    confirm(teks);

                if (!lanjut) return;

                const wadah = document.getElementById('bulkIds');
                wadah.innerHTML = '';
                dipilih.forEach(c => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'ids[]';
                    i.value = c.value;
                    wadah.appendChild(i);
                });
                form.submit();
            });

            segarkan();
        })();
    </script>
@endpush
