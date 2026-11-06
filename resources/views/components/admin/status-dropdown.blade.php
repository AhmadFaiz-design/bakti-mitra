@props(['options', 'current', 'param' => 'status', 'label' => 'Status'])

@php
    $labelAktif = $options[$current] ?? collect($options)->first();
@endphp

<div class="dd" data-dd>
    <button type="button" class="outline-btn dd-btn" aria-haspopup="listbox" aria-expanded="false">
        <span class="dd-label">{{ $label }}:</span>
        <strong>{{ $labelAktif }}</strong>
        <svg class="dd-chev"><use href="#i-down" /></svg>
    </button>

    <div class="dd-menu" role="listbox" hidden>
        @foreach ($options as $kode => $teks)
            <a href="{{ request()->fullUrlWithQuery([$param => $kode, 'page' => null]) }}" role="option"
                aria-selected="{{ (string) $current === (string) $kode ? 'true' : 'false' }}"
                class="dd-item {{ (string) $current === (string) $kode ? 'active' : '' }}">
                {{ $teks }}
            </a>
        @endforeach
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function() {
                const semua = () => document.querySelectorAll('[data-dd]');

                function tutupSemua(kecuali) {
                    semua().forEach(dd => {
                        if (dd === kecuali) return;
                        dd.querySelector('.dd-menu').hidden = true;
                        dd.querySelector('.dd-btn').setAttribute('aria-expanded', 'false');
                    });
                }

                semua().forEach(dd => {
                    const btn = dd.querySelector('.dd-btn');
                    const menu = dd.querySelector('.dd-menu');
                    btn.addEventListener('click', e => {
                        e.stopPropagation();
                        tutupSemua(dd);
                        const buka = menu.hidden;
                        menu.hidden = !buka;
                        btn.setAttribute('aria-expanded', String(buka));
                    });
                });

                document.addEventListener('click', () => tutupSemua(null));
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') tutupSemua(null);
                });
            })();
        </script>
    @endpush
@endonce
