<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penugasan extends Model
{
    protected $table = 'penugasan';

    protected $fillable = [
        'koordinator_id',
        'uraian_pekerjaan',
        'sebagai',
        'ro',
        'kro_ro',
        'komponen',
        'satuan',
        'harga_satuan',
        'paket_data_pulsa',
        'ro_paket_data_pulsa',
        'tanggal_mulai',
        'tanggal_selesai',
        'dibuat_oleh',
    ];

    protected $casts = [
        'paket_data_pulsa' => 'boolean',
        'tanggal_mulai'    => 'date',
        'tanggal_selesai'  => 'date',
        'harga_satuan'     => 'integer',
    ];

    public function koordinator()
    {
        return $this->belongsTo(User::class, 'koordinator_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pekerjaan()
    {
        return $this->hasMany(KontrakPekerjaan::class, 'penugasan_id');
    }
}
