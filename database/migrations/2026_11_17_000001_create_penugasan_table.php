<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uraian_pekerjaan');
            $table->string('ro')->nullable();
            $table->string('kro_ro')->nullable();
            $table->string('komponen')->nullable();
            $table->string('satuan');
            $table->unsignedBigInteger('harga_satuan');
            $table->boolean('paket_data_pulsa')->default(false);
            $table->string('ro_paket_data_pulsa')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('kontrak_pekerjaan', function (Blueprint $table) {
            $table->foreignId('penugasan_id')
                ->nullable()
                ->after('kontrak_id')
                ->constrained('penugasan')
                ->cascadeOnDelete();
        });

        // Kelompokkan data lama kontrak_pekerjaan menjadi 1 tugas di tabel penugasan
        $kelompok = DB::table('kontrak_pekerjaan')
            ->whereNull('penugasan_id')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($row) {
                return implode('|', [
                    $row->koordinator_id ?? '',
                    $row->uraian_pekerjaan ?? '',
                    $row->ro ?? '',
                    $row->kro_ro ?? '',
                    $row->komponen ?? '',
                    $row->satuan ?? '',
                    $row->harga_satuan ?? 0,
                    (int) $row->paket_data_pulsa,
                    $row->ro_paket_data_pulsa ?? '',
                    $row->tanggal_mulai ?? '',
                    $row->tanggal_selesai ?? '',
                ]);
            });

        foreach ($kelompok as $rows) {
            $contoh = $rows->first();
            if (!$contoh->tanggal_mulai || !$contoh->tanggal_selesai) {
                continue;
            }

            $penugasanId = DB::table('penugasan')->insertGetId([
                'koordinator_id'      => $contoh->koordinator_id,
                'uraian_pekerjaan'    => $contoh->uraian_pekerjaan,
                'ro'                  => $contoh->ro,
                'kro_ro'              => $contoh->kro_ro,
                'komponen'            => $contoh->komponen,
                'satuan'              => $contoh->satuan,
                'harga_satuan'        => (int) $contoh->harga_satuan,
                'paket_data_pulsa'    => (bool) $contoh->paket_data_pulsa,
                'ro_paket_data_pulsa' => $contoh->ro_paket_data_pulsa,
                'tanggal_mulai'       => $contoh->tanggal_mulai,
                'tanggal_selesai'     => $contoh->tanggal_selesai,
                'created_at'          => $contoh->created_at ?? now(),
                'updated_at'          => $contoh->updated_at ?? now(),
            ]);

            DB::table('kontrak_pekerjaan')
                ->whereIn('id', $rows->pluck('id')->all())
                ->update(['penugasan_id' => $penugasanId]);
        }
    }

    public function down(): void
    {
        Schema::table('kontrak_pekerjaan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penugasan_id');
        });

        Schema::dropIfExists('penugasan');
    }
};
