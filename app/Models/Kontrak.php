<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Kontrak extends Model
{
    protected $table = 'kontrak';

    protected $fillable = [
        'mitra_id',
        'periode_tahun',
        'periode_bulan',
        'nomor_urut_kontrak',
        'nomor_kontrak',
        'tanggal_spk',
        'cek_kontrak',
        'cek_spj',
        'dibuat_oleh',
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

    public function scopePeriode(Builder $query, int $tahun, int $bulan): Builder
    {
        return $query->where('periode_tahun', $tahun)->where('periode_bulan', $bulan);
    }

    public function terkunci(): bool
    {
        return (bool) ($this->cek_kontrak || $this->cek_spj);
    }

    public function getPeriodeLabelAttribute(): string
    {
        if (!$this->periode_tahun || !$this->periode_bulan) {
            return '-';
        }

        return Carbon::create((int) $this->periode_tahun, (int) $this->periode_bulan, 1)
            ->locale('id')
            ->translatedFormat('F Y');
    }

    public static function formatNomorUrut(string $kodeX, int $urutY): string
    {
        $y = str_pad((string) max(1, $urutY), 2, '0', STR_PAD_LEFT);

        return trim($kodeX) . '.' . $y;
    }

    public static function formatNomorKontrak(
        string $kodeX,
        int $urutY,
        int $tahun,
        string $kodeUnit = '74710/PL-300'
    ): string {
        return 'B-' . self::formatNomorUrut($kodeX, $urutY) . '/' . trim($kodeUnit, '/') . '/' . $tahun;
    }
}
