@props([
    'current' => 10,
    'options' => [10, 25, 50, 100],
])

<div class="dropdown per-page-dd" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" class="per-page-btn" @click="open = !open" :aria-expanded="open"
        aria-haspopup="listbox" aria-label="Jumlah data per halaman">
        <span>{{ $current }}</span>
        <svg viewBox="0 0 24 24" :class="{ 'rotate': open }">
            <path d="M6 9l6 6 6-6" />
        </svg>
    </button>

    <ul class="dropdown-menu dd-menu-up per-page-menu" x-show="open" x-transition.opacity.duration.150ms x-cloak>
        @foreach ($options as $opt)
            <li>
                <a href="{{ request()->fullUrlWithQuery(['per_page' => $opt, 'page' => null]) }}"
                    class="{{ (int) $current === (int) $opt ? 'active' : '' }}">
                    {{ $opt }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
