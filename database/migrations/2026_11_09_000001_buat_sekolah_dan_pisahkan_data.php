<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Tabel yang wajib punya sekolah. */
    private const TABEL = [
        'pengaturan', 'tahun_ajaran', 'semester', 'guru', 'orang_tua', 'siswa', 'kelas',
        'hari_libur', 'pengajuan_izin', 'absensi_siswa', 'absensi_guru', 'rekap_kehadiran_semester',
    ];

    /** Boleh kosong: users (super admin) dan audit_logs (aksi tingkat platform). */
    private const BOLEH_KOSONG = ['users', 'audit_logs'];

    public function up(): void
    {
        Schema::create('sekolah', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('npsn', 20)->nullable()->unique();
            $t->string('kode', 60)->unique();
            $t->boolean('is_aktif')->default(true);
            $t->timestamps();
        });

        $semua = array_merge(self::TABEL, self::BOLEH_KOSONG);

        foreach ($semua as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->unsignedBigInteger('sekolah_id')->nullable()->after('id'));
        }

        // Data yang sudah ada menjadi milik "sekolah pertama".
        $nama = DB::table('pengaturan')->value('nama_sekolah') ?: 'Sekolah Utama';
        $npsn = DB::table('pengaturan')->value('npsn') ?: null;

        $sekolahId = DB::table('sekolah')->insertGetId([
            'nama' => $nama,
            'npsn' => $npsn,
            'kode' => Str::slug($nama) ?: 'sekolah-utama',
            'is_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($semua as $tabel) {
            DB::table($tabel)->update(['sekolah_id' => $sekolahId]);
        }

        foreach (self::TABEL as $tabel) {
            DB::statement("ALTER TABLE `{$tabel}` MODIFY `sekolah_id` BIGINT UNSIGNED NOT NULL");
        }

        foreach ($semua as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->foreign('sekolah_id')->references('id')->on('sekolah'));
        }

        // Aturan unik menjadi unik per sekolah.
        Schema::table('pengaturan', fn (Blueprint $t) => $t->unique('sekolah_id'));

        Schema::table('siswa', function (Blueprint $t) {
            $t->dropUnique('siswa_nisn_unique');
            $t->unique(['sekolah_id', 'nisn']);
        });
        Schema::table('guru', function (Blueprint $t) {
            $t->dropUnique('guru_nip_unique');
            $t->unique(['sekolah_id', 'nip']);
        });
        Schema::table('tahun_ajaran', function (Blueprint $t) {
            $t->dropUnique('tahun_ajaran_nama_unique');
            $t->unique(['sekolah_id', 'nama']);
        });
        Schema::table('hari_libur', function (Blueprint $t) {
            $t->dropUnique('hari_libur_tanggal_unique');
            $t->unique(['sekolah_id', 'tanggal']);
        });

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('super_admin','admin','kepala_sekolah','guru','orang_tua') NOT NULL");
    }

    /** Hanya aman jika baru ada satu sekolah dan tidak ada akun super admin. */
    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','kepala_sekolah','guru','orang_tua') NOT NULL");

        Schema::table('hari_libur', function (Blueprint $t) {
            $t->dropUnique(['sekolah_id', 'tanggal']);
            $t->unique('tanggal');
        });
        Schema::table('tahun_ajaran', function (Blueprint $t) {
            $t->dropUnique(['sekolah_id', 'nama']);
            $t->unique('nama');
        });
        Schema::table('guru', function (Blueprint $t) {
            $t->dropUnique(['sekolah_id', 'nip']);
            $t->unique('nip');
        });
        Schema::table('siswa', function (Blueprint $t) {
            $t->dropUnique(['sekolah_id', 'nisn']);
            $t->unique('nisn');
        });
        Schema::table('pengaturan', fn (Blueprint $t) => $t->dropUnique(['sekolah_id']));

        foreach (array_merge(self::TABEL, self::BOLEH_KOSONG) as $tabel) {
            Schema::table($tabel, function (Blueprint $t) {
                $t->dropForeign(['sekolah_id']);
                $t->dropColumn('sekolah_id');
            });
        }

        Schema::dropIfExists('sekolah');
    }
};