@props([
    'judul',
    'min' => 0,
    'max',
    'dari' => 0,
    'sampai' => null,
    'namaDari',
    'namaSampai',
    'step' => 1,
    'rupiah' => false,
    'modeId' => null,
])

@php
    $sampai = $sampai ?? $max;
    $tampil = fn($n) => $rupiah ? number_format((int) $n, 0, ',', '.') : (int) $n;
@endphp

<div class="filter-section">
    <span class="filter-label">{{ $judul }}</span>

    <div class="range" data-range data-min="{{ $min }}" data-max="{{ $max }}"
        @if ($rupiah) data-format="rupiah" @endif
        @if ($modeId) data-mode="{{ $modeId }}" @endif>
        <div class="range-slider">
            <div class="range-fill"></div>
            <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                value="{{ $dari }}" aria-label="{{ $judul }} minimum">
            <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                value="{{ $sampai }}" aria-label="{{ $judul }} maksimum">
        </div>

        <div class="range-values">
            <label class="range-pill">
                @if ($rupiah)
                    <span>Rp</span>
                @endif
                <input type="text" inputmode="numeric" name="{{ $namaDari }}" value="{{ $tampil($dari) }}"
                    aria-label="{{ $judul }} dari" autocomplete="off">
            </label>
            <label class="range-pill">
                @if ($rupiah)
                    <span>Rp</span>
                @endif
                <input type="text" inputmode="numeric" name="{{ $namaSampai }}" value="{{ $tampil($sampai) }}"
                    aria-label="{{ $judul }} sampai" autocomplete="off">
            </label>
        </div>
    </div>
</div>
