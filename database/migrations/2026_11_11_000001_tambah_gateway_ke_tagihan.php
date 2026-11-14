<?php
// Simpan di: database/migrations/2026_11_11_000001_tambah_gateway_ke_tagihan.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $t) {
            if (!Schema::hasColumn('tagihan', 'snap_token')) {
                $t->text('snap_token')->nullable()->after('bukti_diunggah_pada');
            }
            if (!Schema::hasColumn('tagihan', 'gateway_ref')) {
                $t->string('gateway_ref', 100)->nullable()->index()->after('snap_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $t) {
            foreach (['snap_token', 'gateway_ref'] as $kolom) {
                if (Schema::hasColumn('tagihan', $kolom)) {
                    $t->dropColumn($kolom);
                }
            }
        });
    }
};