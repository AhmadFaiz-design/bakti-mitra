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
}
