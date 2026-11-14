<?php

namespace App\Console\Commands;

use App\Models\AbsensiGuru;
use App\Models\Pengaturan;
use App\Models\Sekolah;
use App\Support\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class HapusFotoAbsensiLama extends Command
{
    protected $signature = 'absensi:hapus-foto-lama';

    protected $description = 'Hapus foto absensi guru yang melewati masa simpan, untuk setiap sekolah';

    public function handle(): int
    {
        foreach (Sekolah::all() as $sekolah) {
            Tenant::sebagai($sekolah, function () use ($sekolah) {
                $hari = Pengaturan::first()?->simpan_foto_hari ?? 90;
                $batas = today()->subDays($hari)->toDateString();
                $jumlah = 0;

                AbsensiGuru::where('tanggal', '<', $batas)
                    ->where(fn ($q) => $q->whereNotNull('foto_masuk')->orWhereNotNull('foto_pulang'))
                    ->chunkById(200, function ($rows) use (&$jumlah) {
                        foreach ($rows as $r) {
                            foreach (['foto_masuk', 'foto_pulang'] as $kolom) {
                                if ($r->$kolom) {
                                    Storage::disk('local')->delete($r->$kolom);
                                    $r->$kolom = null;
                                    $jumlah++;
                                }
                            }
                            $r->save();
                        }
                    });

                $this->info("{$sekolah->nama}: {$jumlah} foto dihapus (lebih dari {$hari} hari).");
            });
        }

        return self::SUCCESS;
    }
}