<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    protected $table = 'mitra';

    protected $fillable = [
        'user_id',
        'nik',
        'alamat',
        'pekerjaan',
        'jenis_kelamin',
        'wilayah',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pekerjaan()
    {
        return $this->hasManyThrough(
            KontrakPekerjaan::class, // tabel tujuan
            Kontrak::class,          // tabel perantara
            'mitra_id',              // kunci di tabel kontrak
            'kontrak_id'             // kunci di tabel kontrak_pekerjaan
        );
    }

    public function kontrak()
    {
        return $this->hasMany(Kontrak::class);
    }

    public function getStatusKetersediaanAttribute(): array
    {
        // Akun nonaktif tetap ditandai terpisah
        if (! ($this->user?->is_active ?? false)) {
            return ['key' => 'inactive', 'label' => 'Tidak Aktif'];
        }

        $honorBulanIni = (int) ($this->honor_bulan_ini ?? 0);
        $tugasBulanIni = (int) ($this->tugas_bulan_ini ?? 0);

        if ($honorBulanIni >= (int) config('mitra.batas_honor')) {
            return ['key' => 'penuh', 'label' => 'Penuh'];
        }

        if ($tugasBulanIni > 0) {
            return ['key' => 'bertugas', 'label' => 'Bertugas'];
        }

        return ['key' => 'tersedia', 'label' => 'Tersedia'];
    }
}
