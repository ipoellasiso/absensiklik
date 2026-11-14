<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah');
            $table->string('npsn', 20)->nullable();
            $table->time('jam_masuk')->default('07:30:00');
            $table->time('jam_pulang')->default('12:30:00');
            $table->unsignedSmallInteger('batas_terlambat_menit')->default(15);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('radius_meter')->default(100);
            $table->unsignedTinyInteger('batas_koreksi_hari')->default(1);
            $table->unsignedTinyInteger('batas_pengajuan_mundur_hari')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
