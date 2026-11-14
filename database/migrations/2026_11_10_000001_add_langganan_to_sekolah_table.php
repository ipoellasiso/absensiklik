<?php
// Simpan di: database/migrations/2026_11_10_000001_add_langganan_to_sekolah_table.php
// ASUMSI: nama tabel 'sekolah'. Jika tabel Bang bernama 'sekolahs', ubah variabel $tabel.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tabel = 'sekolah';

    public function up(): void
    {
        Schema::table($this->tabel, function (Blueprint $t) {
            if (!Schema::hasColumn($this->tabel, 'npsn')) {
                $t->string('npsn', 8)->nullable()->unique();
            }
            if (!Schema::hasColumn($this->tabel, 'email_kontak')) {
                $t->string('email_kontak', 150)->nullable();
            }
            if (!Schema::hasColumn($this->tabel, 'no_wa')) {
                $t->string('no_wa', 20)->nullable();
            }
            if (!Schema::hasColumn($this->tabel, 'status_langganan')) {
                // trial | aktif | ditangguhkan  (kadaluarsa dihitung dari tanggal, bukan disimpan)
                $t->string('status_langganan', 20)->default('trial');
            }
            if (!Schema::hasColumn($this->tabel, 'trial_berakhir_at')) {
                $t->dateTime('trial_berakhir_at')->nullable();
            }
            if (!Schema::hasColumn($this->tabel, 'langganan_berakhir_at')) {
                $t->dateTime('langganan_berakhir_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->tabel, function (Blueprint $t) {
            foreach (['npsn', 'email_kontak', 'no_wa', 'status_langganan', 'trial_berakhir_at', 'langganan_berakhir_at'] as $kolom) {
                if (Schema::hasColumn($this->tabel, $kolom)) {
                    if ($kolom === 'npsn') {
                        $t->dropUnique([$kolom]);
                    }
                    $t->dropColumn($kolom);
                }
            }
        });
    }
};