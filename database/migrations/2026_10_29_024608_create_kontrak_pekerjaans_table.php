<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_id')->constrained('kontrak')->cascadeOnDelete();
            $table->foreignId('koordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('urutan');
            $table->string('uraian_pekerjaan')->nullable();
            $table->string('ro')->nullable();
            $table->string('kro_ro')->nullable();
            $table->string('komponen')->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->string('satuan')->nullable();
            $table->unsignedBigInteger('harga_satuan')->nullable();
            $table->unsignedBigInteger('jumlah')->nullable();
            $table->boolean('paket_data_pulsa')->default(false);
            $table->string('ro_paket_data_pulsa')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();

            $table->unique(['kontrak_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak_pekerjaan');
    }
};
