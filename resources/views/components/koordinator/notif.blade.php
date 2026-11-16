@php
    $n = \App\Support\NotifKoordinator::untuk((int) \Illuminate\Support\Facades\Auth::id());
    $notifikasi = $n['notifikasi'];
    $notifPenting = $n['penting'];
@endphp

<div class="notif" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" class="bell" @click="open = !open" :aria-expanded="open" aria-haspopup="dialog"
        aria-label="Notifikasi">
        <svg>
            <use href="#i-bell" />
        </svg>
        @if ($notifPenting > 0)
            <span class="bell-dot"></span>
        @endif
    </button>

    <div class="notif-pop" x-show="open" x-transition.opacity.duration.150ms x-cloak role="dialog"
        aria-label="Notifikasi">
        <div class="notif-head">
            <strong>Notifikasi</strong>
            @if ($notifPenting > 0)
                <span>{{ $notifPenting }} perlu perhatian</span>
            @endif
        </div>

        <ul class="notif-list">
            @forelse ($notifikasi as $item)
                @php $tag = $item['url'] ? 'a' : 'div'; @endphp
                <li>
                    <{{ $tag }} @if ($item['url']) href="{{ $item['url'] }}" @endif
                        class="notif-item {{ $item['level'] }}">
                        <span class="n-ic">
                            @if ($item['ikon'] === 'tugas')
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
                            <strong>{{ $item['judul'] }}</strong>
                            <small>{{ $item['ket'] }}</small>
                            @if (!empty($item['waktu']))
                                <em>{{ $item['waktu'] }}</em>
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
