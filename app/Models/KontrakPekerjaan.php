<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontrakPekerjaan extends Model
{
    protected $table = 'kontrak_pekerjaan';

    protected $fillable = [
        'kontrak_id', 'koordinator_id', 'urutan', 'uraian_pekerjaan', 'ro',
        'kro_ro', 'komponen', 'volume', 'satuan', 'harga_satuan', 'jumlah',
        'paket_data_pulsa', 'ro_paket_data_pulsa', 'tanggal_mulai', 'tanggal_selesai',
    ];

    protected $casts = [
        'paket_data_pulsa' => 'boolean',
        'tanggal_mulai'    => 'date',
        'tanggal_selesai'  => 'date',
    ];

    public function kontrak()
    {
        return $this->belongsTo(Kontrak::class);
    }

    public function koordinator()
    {
        return $this->belongsTo(User::class, 'koordinator_id');
    }
}
