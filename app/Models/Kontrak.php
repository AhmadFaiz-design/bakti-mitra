<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kontrak extends Model
{
    protected $table = 'kontrak';

    protected $fillable = [
        'mitra_id', 'periode_tahun', 'periode_bulan', 'nomor_urut_kontrak',
        'nomor_kontrak', 'tanggal_spk', 'cek_kontrak', 'cek_spj', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_spk' => 'date',
        'cek_kontrak' => 'boolean',
        'cek_spj'     => 'boolean',
    ];

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    public function pekerjaan()
    {
        return $this->hasMany(KontrakPekerjaan::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
