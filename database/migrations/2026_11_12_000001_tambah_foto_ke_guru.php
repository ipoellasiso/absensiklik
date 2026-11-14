<?php
// Simpan di: database/migrations/2026_11_12_000001_tambah_foto_ke_guru.php
// Tabel siswa SUDAH punya kolom foto, jadi hanya tabel guru yang perlu ditambah.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('guru', 'foto')) {
            Schema::table('guru', function (Blueprint $t) {
                $t->string('foto')->nullable()->after('jabatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guru', 'foto')) {
            Schema::table('guru', function (Blueprint $t) {
                $t->dropColumn('foto');
            });
        }
    }
};