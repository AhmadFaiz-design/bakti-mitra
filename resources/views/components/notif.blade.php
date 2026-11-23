@props([
    'notifikasi' => null,
    'notifPenting' => null,
])

@php
    $daftarNotif = collect($notifikasi ?? []);
    $jumlahPenting = (int) ($notifPenting ?? 0);

@endphp

<div class="notif" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" class="bell" @click="open = !open" :aria-expanded="open" aria-haspopup="dialog"
        aria-label="Notifikasi">
        <svg>
            <use href="#i-bell" />
        </svg>
        @if ($jumlahPenting > 0)
            <span class="bell-dot"></span>
        @endif
    </button>

    <div class="notif-pop" x-show="open" x-transition.opacity.duration.150ms x-cloak role="dialog"
        aria-label="Notifikasi">
        <div class="notif-head">
            <strong>Notifikasi</strong>
            @if ($jumlahPenting > 0)
                <span>{{ $jumlahPenting }} perlu perhatian</span>
            @endif
        </div>

        <ul class="notif-list">
            @forelse ($daftarNotif as $n)
                @php $tag = !empty($n['url']) ? 'a' : 'div'; @endphp
                <li>
                    <{{ $tag }}
                        @if (!empty($n['url'])) href="{{ $n['url'] }}" @endif
                        class="notif-item {{ $n['level'] ?? '' }}">
                        <span class="n-ic">
                            @if (($n['ikon'] ?? '') === 'tugas')
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
                            <small>{{ $n['ket'] ?? '' }}</small>
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
