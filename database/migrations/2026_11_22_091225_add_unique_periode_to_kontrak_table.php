<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gabungkan jika ada kontrak duplikat di (mitra_id, periode_tahun, periode_bulan)
        $duplikat = DB::table('kontrak')
            ->select('mitra_id', 'periode_tahun', 'periode_bulan', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as jml'))
            ->groupBy('mitra_id', 'periode_tahun', 'periode_bulan')
            ->having('jml', '>', 1)
            ->get();

        foreach ($duplikat as $d) {
            $hapusIds = DB::table('kontrak')
                ->where('mitra_id', $d->mitra_id)
                ->where('periode_tahun', $d->periode_tahun)
                ->where('periode_bulan', $d->periode_bulan)
                ->where('id', '!=', $d->keep_id)
                ->pluck('id');

            if ($hapusIds->isNotEmpty()) {
                DB::table('kontrak_pekerjaan')
                    ->whereIn('kontrak_id', $hapusIds)
                    ->update(['kontrak_id' => $d->keep_id]);

                DB::table('kontrak')
                    ->whereIn('id', $hapusIds)
                    ->delete();
            }
        }

        $sudahUnique = collect(Schema::getIndexes('kontrak'))->contains(
            fn($idx) => ($idx['unique'] ?? false) && $idx['columns'] === ['mitra_id', 'periode_tahun', 'periode_bulan']
        );

        if (!$sudahUnique) {
            Schema::table('kontrak', function (Blueprint $table) {
                $table->unique(['mitra_id', 'periode_tahun', 'periode_bulan'], 'kontrak_mitra_periode_unique');
            });
        }
    }

    public function down(): void
    {
        $adaIndex = collect(Schema::getIndexes('kontrak'))->contains(
            fn($idx) => ($idx['name'] ?? '') === 'kontrak_mitra_periode_unique'
        );

        if ($adaIndex) {
            Schema::table('kontrak', function (Blueprint $table) {
                $table->dropUnique('kontrak_mitra_periode_unique');
            });
        }
    }
};
