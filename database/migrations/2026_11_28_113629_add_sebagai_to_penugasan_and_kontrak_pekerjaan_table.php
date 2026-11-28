<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('penugasan', 'sebagai')) {
            Schema::table('penugasan', function (Blueprint $table) {
                $table->string('sebagai', 20)->nullable()->after('uraian_pekerjaan');
            });
        }

        if (!Schema::hasColumn('kontrak_pekerjaan', 'sebagai')) {
            Schema::table('kontrak_pekerjaan', function (Blueprint $table) {
                $table->string('sebagai', 20)->nullable()->after('uraian_pekerjaan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kontrak_pekerjaan', 'sebagai')) {
            Schema::table('kontrak_pekerjaan', function (Blueprint $table) {
                $table->dropColumn('sebagai');
            });
        }

        if (Schema::hasColumn('penugasan', 'sebagai')) {
            Schema::table('penugasan', function (Blueprint $table) {
                $table->dropColumn('sebagai');
            });
        }
    }
};
