<?php

namespace App\Imports;

use App\Models\Mitra;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MitraImport
{
    public array $berhasil = [];
    public array $gagal = [];

    public function jalankan(string $path, string $ekstensi): void
    {
        $tipe   = strtolower($ekstensi) === 'xls' ? 'Xls' : 'Xlsx';
        $reader = IOFactory::createReader($tipe);
        $reader->setReadDataOnly(true);

        // hanya sheet pertama yang dibaca
        $data = $reader->load($path)->getSheet(0)->toArray(null, false, false, false);

        // baris pertama = judul kolom
        $judul = array_map(
            fn ($h) => str_replace(' ', '_', strtolower(trim((string) $h))),
            array_shift($data) ?? []
        );

        foreach ($data as $i => $cells) {
            $baris = $i + 2; // baris 1 adalah judul kolom

            $row = [];
            foreach ($judul as $k => $nama) {
                if ($nama !== '') {
                    $row[$nama] = $cells[$k] ?? null;
                }
            }

            $nama     = $this->teks($row['nama'] ?? null);
            $username = $this->teks($row['sobat_id'] ?? null);
            $nik      = $this->teks($row['nik'] ?? null);

            // lewati baris yang benar-benar kosong
            if ($nama === '' && $username === '' && $nik === '') {
                continue;
            }

            if ($nama === '' || $username === '') {
                $this->gagal[] = [$baris, $nama ?: $username, 'Nama dan Sobat ID wajib diisi'];
                continue;
            }

            if (!preg_match('/^\d{16}$/', $nik)) {
                $this->gagal[] = [$baris, $nama, 'NIK harus 16 digit angka (pastikan kolom berformat Text)'];
                continue;
            }

            if (User::where('username', $username)->exists()) {
                $this->gagal[] = [$baris, $nama, 'Sobat ID sudah terdaftar'];
                continue;
            }

            if (Mitra::where('nik', $nik)->exists()) {
                $this->gagal[] = [$baris, $nama, 'NIK sudah terdaftar'];
                continue;
            }

            $jenisKelamin = match (strtoupper($this->teks($row['jenis_kelamin'] ?? null))) {
                'LK', 'L', 'LAKI-LAKI' => 'Lk',
                'PR', 'P', 'PEREMPUAN' => 'Pr',
                default => null,
            };

            $password = $username;

            DB::transaction(function () use ($nama, $username, $nik, $row, $jenisKelamin, $password) {
                $user = User::create([
                    'name'      => $nama,
                    'username'  => $username,
                    'password'  => $password, // otomatis di-hash oleh model
                    'role'      => 'mitra',
                    'is_active' => true,
                ]);

                Mitra::create([
                    'user_id'       => $user->id,
                    'nik'           => $nik,
                    'alamat'        => $this->teks($row['alamat'] ?? null) ?: null,
                    'pekerjaan'     => $this->teks($row['pekerjaan'] ?? null) ?: null,
                    'jenis_kelamin' => $jenisKelamin,
                    'wilayah'       => $this->teks($row['wilayah'] ?? null) ?: null,
                ]);
            });

            $this->berhasil[] = [$nama, $username, $password];
        }
    }

    private function teks($nilai): string
    {
        if ($nilai === null) {
            return '';
        }
        if (is_float($nilai) && floor($nilai) == $nilai) {
            return number_format($nilai, 0, '', '');
        }
        return trim((string) $nilai);
    }
}
