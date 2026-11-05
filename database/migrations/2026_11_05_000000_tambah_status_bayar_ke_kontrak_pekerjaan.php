<?php

use App\Models\KontrakPekerjaan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function tabel(): string
    {
        // Mengikuti nama tabel yang dipakai model, jadi tidak perlu ditebak.
        return (new KontrakPekerjaan())->getTable();
    }

    public function up(): void
    {
        Schema::table($this->tabel(), function (Blueprint $table) {
            $table->boolean('honor_dibayar')->default(false);
            $table->timestamp('dibayar_pada')->nullable();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table($this->tabel(), function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibayar_oleh');
            $table->dropColumn(['honor_dibayar', 'dibayar_pada']);
        });
    }
};
