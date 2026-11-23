@props([
    'judul' => '',
    'subjudul' => null,
    'opsiBulan' => [],
    'bulanKey' => null,
    'labelBulan' => null,
    'notifikasi' => null,
    'notifPenting' => null,
])

<header {{ $attributes->merge(['class' => 'topbar']) }}>
    <div class="topbar-title">
        <h1>{{ $judul }}</h1>
        @if ($subjudul)
            <p>{{ $subjudul }}</p>
        @endif
    </div>

    <div class="topbar-right">
        {{-- PILIH BULAN (muncul otomatis pada halaman yang mengirim $opsiBulan, mis. Dashboard semua role) --}}
        @if (!empty($opsiBulan))
            <div class="dropdown bulan-dd" x-data="{ open: false }" @click.outside="open = false"
                x-init="$watch('open', v => v && $nextTick(() => {
                    const m = $refs.menu,
                        a = m.querySelector('a.active');
                    if (a) m.scrollTop = a.offsetTop - m.clientHeight / 2;
                }))">
                <button type="button" class="pill" @click="open = !open" :aria-expanded="open"
                    aria-haspopup="listbox">
                    <span>{{ $labelBulan }}</span>
                    <svg viewBox="0 0 24 24" :class="{ 'rotate': open }">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </button>

                <ul class="dropdown-menu bulan-menu" x-ref="menu" x-show="open"
                    x-transition.opacity.duration.150ms x-cloak>
                    @foreach ($opsiBulan as $o)
                        <li>
                            <a href="{{ request()->fullUrlWithQuery(['bulan' => $o['k']]) }}"
                                class="{{ $o['k'] === $bulanKey ? 'active' : '' }}">
                                {{ $o['t'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Slot tambahan opsional jika suatu halaman butuh tombol ekstra di header --}}
        {{ $slot }}

        <div class="user">
            <x-notif :notifikasi="$notifikasi" :notif-penting="$notifPenting" />

            <span>{{ auth()->user()->name }}</span>
            <div class="avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </div>
</header>
