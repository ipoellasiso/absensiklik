<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->string('foto_masuk')->nullable()->after('jarak_pulang_m');
            $table->string('foto_pulang')->nullable()->after('foto_masuk');
        });

        Schema::table('pengaturan', function (Blueprint $table) {
            $table->boolean('wajib_foto_guru')->default(true)->after('radius_meter');
            $table->unsignedSmallInteger('simpan_foto_hari')->default(90)->after('wajib_foto_guru');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn(['wajib_foto_guru', 'simpan_foto_hari']);
        });

        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->dropColumn(['foto_masuk', 'foto_pulang']);
        });
    }
};