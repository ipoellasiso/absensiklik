<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 60);
            $t->unsignedInteger('batas_siswa');
            $t->unsignedBigInteger('harga_per_tahun'); // rupiah
            $t->string('keterangan')->nullable();
            $t->boolean('is_aktif')->default(true);
            $t->timestamps();
        });

        Schema::table('sekolah', function (Blueprint $t) {
            $t->foreignId('paket_id')->nullable()->after('kode')->constrained('paket')->nullOnDelete();
            $t->date('uji_coba_sampai')->nullable()->after('paket_id');
            $t->date('berlangganan_sampai')->nullable()->after('uji_coba_sampai');
        });

        // Sekolah yang sudah ada tidak boleh terkunci. Ubah di panel Platform jika perlu.
        DB::table('sekolah')->update(['berlangganan_sampai' => '2099-12-31']);

        Schema::create('tagihan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sekolah_id')->constrained('sekolah');
            $t->foreignId('paket_id')->nullable()->constrained('paket')->nullOnDelete();
            $t->string('nomor', 30)->unique();
            $t->string('paket_nama', 60);              // salinan, agar riwayat tidak berubah saat paket diubah
            $t->unsignedInteger('batas_siswa');
            $t->unsignedTinyInteger('lama_bulan')->default(12);
            $t->unsignedBigInteger('jumlah');
            $t->date('periode_mulai')->nullable();
            $t->date('periode_selesai')->nullable();
            $t->enum('status', ['menunggu', 'lunas', 'batal'])->default('menunggu');
            $t->string('bukti')->nullable();
            $t->timestamp('bukti_diunggah_pada')->nullable();
            $t->timestamp('dibayar_pada')->nullable();
            $t->foreignId('dikonfirmasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->string('catatan')->nullable();
            $t->timestamps();
            $t->index(['sekolah_id', 'status']);
        });

        Schema::create('libur_nasional', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal')->unique();
            $t->string('keterangan');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libur_nasional');
        Schema::dropIfExists('tagihan');

        Schema::table('sekolah', function (Blueprint $t) {
            $t->dropForeign(['paket_id']);
            $t->dropColumn(['paket_id', 'uji_coba_sampai', 'berlangganan_sampai']);
        });

        Schema::dropIfExists('paket');
    }
};