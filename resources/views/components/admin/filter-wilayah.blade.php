@props(['judul' => 'Wilayah', 'opsi' => [], 'dipilih' => null, 'nama' => 'wilayah'])

<div class="filter-section">
    <span class="filter-label">{{ $judul }}</span>

    <div class="chip-group" data-chip-group data-target="{{ $nama }}Input">
        @foreach ($opsi as $w)
            @php $aktif = (string) $dipilih === (string) $w; @endphp
            <button type="button" class="chip-opt {{ $aktif ? 'selected' : '' }}" data-value="{{ $w }}"
                aria-pressed="{{ $aktif ? 'true' : 'false' }}">
                <svg viewBox="0 0 24 24">
                    <path d="M5 12.5l4.5 4.5L19 7.5" />
                </svg>
                {{ $w }}
            </button>
        @endforeach
    </div>

    <input type="hidden" name="{{ $nama }}" id="{{ $nama }}Input" value="{{ $dipilih }}">
</div>
