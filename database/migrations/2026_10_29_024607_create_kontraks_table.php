<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra')->restrictOnDelete();
            $table->smallInteger('periode_tahun');
            $table->tinyInteger('periode_bulan');
            $table->string('nomor_urut_kontrak')->nullable();
            $table->string('nomor_kontrak')->nullable()->unique();
            $table->date('tanggal_spk')->nullable();
            $table->boolean('cek_kontrak')->default(false);
            $table->boolean('cek_spj')->default(false);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mitra_id', 'periode_tahun', 'periode_bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak');
    }
};
