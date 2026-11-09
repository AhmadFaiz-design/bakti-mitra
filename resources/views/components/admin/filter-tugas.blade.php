@props([
    'judul' => 'Tugas',
    'placeholder' => 'Cari Tugas',
    'nama' => 'tugas',
    'banyak' => true,
    'opsi' => [],
    'dipilih' => [],
])

@php
    // Opsi boleh berupa teks biasa atau ['v' => nilai, 't' => label]
    $daftar = collect($opsi)
        ->map(
            fn($o) => is_array($o) || is_object($o)
                ? ['v' => (string) data_get($o, 'v'), 't' => (string) data_get($o, 't')]
                : ['v' => (string) $o, 't' => (string) $o],
        )
        ->values();
    $terpilih = collect($dipilih)->map(fn($v) => (string) $v)->values();
@endphp

<div class="filter-section" data-tugas data-nama="{{ $banyak ? $nama . '[]' : $nama }}"
    data-banyak="{{ $banyak ? '1' : '0' }}" data-opsi="{{ $daftar->toJson() }}">
    <span class="filter-label">{{ $judul }}</span>

    <label class="tugas-search">
        <svg>
            <use href="#i-search" />
        </svg>
        <input type="text" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}" autocomplete="off">
    </label>

    <ul class="tugas-saran" hidden></ul>

    <div class="filter-chips" data-selected="{{ $terpilih->toJson() }}"></div>
</div>
