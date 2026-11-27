<?php

namespace App\Support;

class BatasHonor
{
    public const PENDATAAN_KENDARI  = 3740000;
    public const PENDATAAN_KONKEP   = 3568000;
    public const PENGOLAHAN_KENDARI = 3460000;
    public const PENGOLAHAN_KONKEP  = 3460000;

    public static function jenisWilayah(?string $wilayah): string
    {
        $w = strtolower(trim((string) $wilayah));

        if ($w !== '' && (str_contains($w, 'konkep') || str_contains($w, 'konawe kepulauan'))) {
            return 'konkep';
        }

        return 'kendari';
    }

    public static function untukPeran(?string $wilayah, ?string $peran): int
    {
        $wil = self::jenisWilayah($wilayah);
        $p   = strtolower(trim((string) $peran));

        if ($p === 'pengolah' || $p === 'pengolahan') {
            return $wil === 'konkep' ? self::PENGOLAHAN_KONKEP : self::PENGOLAHAN_KENDARI;
        }

        if ($p === 'pendata' || $p === 'pendataan') {
            return $wil === 'konkep' ? self::PENDATAAN_KONKEP : self::PENDATAAN_KENDARI;
        }

        // Default jika peran belum ditentukan / belum ada tugas
        return $wil === 'konkep' ? self::PENDATAAN_KONKEP : self::PENDATAAN_KENDARI;
    }

    /**
     * Hitung batas honor efektif mitra pada satu bulan.
     * Jika memiliki > 1 peran (misal Pendata dan Pengolah), ambil batas tertinggi.
     */
    public static function untukMitra(?string $wilayah, array $daftarPeran = []): int
    {
        $peranValid = array_values(array_filter(array_map(
            fn($v) => trim((string) $v),
            $daftarPeran
        )));

        if (empty($peranValid)) {
            return self::untukPeran($wilayah, null);
        }

        $maks = 0;
        foreach ($peranValid as $peran) {
            $maks = max($maks, self::untukPeran($wilayah, $peran));
        }

        return $maks;
    }
}
