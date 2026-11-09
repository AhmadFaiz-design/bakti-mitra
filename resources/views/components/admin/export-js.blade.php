@props(['button'])

@push('scripts')
    <script>
        (function() {
            const tombol = document.getElementById(@json($button));
            const modal = document.getElementById('exportModal');
            const overlay = document.getElementById('exportOverlay');
            const form = document.getElementById('exportForm');
            const batal = document.getElementById('exportCancel');
            if (!tombol || !modal || !overlay || !form) return;

            function buka() {
                modal.classList.add('open');
                overlay.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function tutup() {
                modal.classList.remove('open');
                overlay.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }

            tombol.addEventListener('click', buka);
            overlay.addEventListener('click', tutup);
            batal?.addEventListener('click', tutup);
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') tutup();
            });

            // File diunduh oleh browser, halaman tetap di tempat. Tutup modal setelahnya.
            form.addEventListener('submit', () => setTimeout(tutup, 400));
        })();
    </script>
@endpush
