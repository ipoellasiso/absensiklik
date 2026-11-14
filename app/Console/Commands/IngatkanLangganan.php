<?php

namespace App\Console\Commands;

use App\Models\Sekolah;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class IngatkanLangganan extends Command
{
    protected $signature = 'langganan:ingatkan';

    protected $description = 'Kirim pengingat masa uji coba dan langganan ke admin sekolah';

    public function handle(): int
    {
        $jumlah = 0;

        foreach (Sekolah::where('is_aktif', true)->get() as $sekolah) {
            $p = $this->peringatan($sekolah->langganan());

            if (! $p) {
                continue;
            }

            [$kode, $judul, $isi] = $p;

            // satu peringatan sekali per hari per jenis
            if (! Cache::add("langganan-ingat:{$sekolah->id}:{$kode}:".today()->toDateString(), true, now()->addDays(2))) {
                continue;
            }

            foreach (User::where('sekolah_id', $sekolah->id)->where('role', User::ROLE_ADMIN)->where('is_active', true)->get() as $admin) {
                Notifier::kirim($admin->id, 'langganan', $judul, $isi);
                $jumlah++;
            }
        }

        $this->info("{$jumlah} pengingat dikirim.");

        return self::SUCCESS;
    }

    private function peringatan(array $l): ?array
    {
        $sisa = $l['sisa_hari'];

        if ($sisa === null) {
            return null;
        }

        $hari = config('langganan.pengingat_hari', [14, 7, 3, 1]);
        $tenggang = (int) config('langganan.masa_tenggang_hari', 7);

        switch ($l['status']) {
            case 'uji_coba':
                return in_array($sisa, $hari, true)
                    ? ["uji{$sisa}", 'Masa uji coba hampir habis', "Masa uji coba berakhir {$sisa} hari lagi. Pilih paket di menu Langganan agar absensi tetap bisa diisi."]
                    : null;

            case 'aktif':
                return in_array($sisa, $hari, true)
                    ? ["aktif{$sisa}", 'Langganan hampir habis', "Langganan berakhir {$sisa} hari lagi ({$l['sampai']->format('d/m/Y')}). Perpanjang di menu Langganan."]
                    : null;

            case 'tenggang':
                if ($sisa === $tenggang - 1) {
                    return ['tenggang_awal', 'Langganan berakhir, masa tenggang dimulai', "Masa tenggang sampai {$l['sampai']->format('d/m/Y')}. Setelah itu aplikasi hanya bisa melihat data."];
                }

                return $sisa === 0
                    ? ['tenggang_akhir', 'Hari terakhir masa tenggang', 'Mulai besok aplikasi hanya bisa melihat data. Perpanjang langganan hari ini agar absensi tetap berjalan.']
                    : null;

            default:
                return $sisa === -1
                    ? ['berakhir', 'Mode baca saja aktif', 'Langganan sekolah berakhir. Absensi baru tidak bisa diisi sampai langganan diperpanjang. Data Anda tetap aman.']
                    : null;
        }
    }
}