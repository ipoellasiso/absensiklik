<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->time('jam_tutup_absensi')->default('14:00:00')->after('jam_pulang');
        });

        DB::statement("ALTER TABLE absensi_siswa MODIFY sumber ENUM('manual','qr','pengajuan','otomatis') NOT NULL DEFAULT 'manual'");

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipe', 40);
            $table->string('judul');
            $table->text('isi');
            $table->json('data')->nullable();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'dibaca_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');

        DB::table('absensi_siswa')->where('sumber', 'otomatis')->update(['sumber' => 'manual']);
        DB::statement("ALTER TABLE absensi_siswa MODIFY sumber ENUM('manual','qr','pengajuan') NOT NULL DEFAULT 'manual'");

        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn('jam_tutup_absensi');
        });
    }
};