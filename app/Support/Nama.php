<?php

namespace App\Support;

class Nama
{
    /**
     * Seragamkan huruf nama: kata yang semuanya kapital atau semuanya kecil
     * diubah menjadi huruf awal kapital saja. Inisial (1 huruf) tidak diubah.
     */
    public static function rapikan(?string $nama): string
    {
        $nama = trim((string) preg_replace('/\s+/u', ' ', (string) $nama));
        if ($nama === '') {
            return '';
        }

        $kata = array_map(function ($k) {
            $huruf = preg_replace('/[^\p{L}]/u', '', $k);

            if (mb_strlen($huruf) >= 2
                && ($huruf === mb_strtoupper($huruf) || $huruf === mb_strtolower($huruf))) {
                return mb_convert_case($k, MB_CASE_TITLE, 'UTF-8');
            }

            return $k;
        }, explode(' ', $nama));

        return implode(' ', $kata);
    }
}
