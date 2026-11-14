<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_kehadiran_semester', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semester');
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->unsignedSmallInteger('hari_efektif')->default(0);
            $table->unsignedSmallInteger('hadir')->default(0);
            $table->unsignedSmallInteger('sakit')->default(0);
            $table->unsignedSmallInteger('izin')->default(0);
            $table->unsignedSmallInteger('alpa')->default(0);
            $table->unsignedSmallInteger('terlambat')->default(0);
            $table->decimal('persentase_kehadiran', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['siswa_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_kehadiran_semester');
    }
};
