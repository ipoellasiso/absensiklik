<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use App\Models\Sekolah;
use App\Services\AbsensiGuruService;
use App\Services\AbsensiSiswaService;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TutupHariAbsensi extends Command
{
    protected $signature = 'absensi:tutup-hari
        {--tanggal= : Format Y-m-d. Jika diisi, jam tutup tidak diperiksa}
        {--sekolah= : ID sekolah tertentu (opsional)}';

    protected $description = 'Alpa otomatis siswa dan tanpa keterangan guru, untuk setiap sekolah aktif';

    public function handle(AbsensiSiswaService $siswa, AbsensiGuruService $guru): int
    {
        $manual = $this->option('tanggal')
            ? Carbon::createFromFormat('Y-m-d', $this->option('tanggal'))->startOfDay()
            : null;

        $daftar = Sekolah::where('is_aktif', true)
            ->when($this->option('sekolah'), fn ($q, $id) => $q->where('id', $id))
            ->get();

        foreach ($daftar as $sekolah) {
            try {
                Tenant::sebagai($sekolah, fn () => $this->tutupSatuSekolah($sekolah, $siswa, $guru, $manual));
            } catch (Throwable $e) {
                report($e); // satu sekolah gagal tidak menghentikan sekolah lain
                $this->error("{$sekolah->nama}: gagal ({$e->getMessage()})");
            }
        }

        return self::SUCCESS;
    }

    private function tutupSatuSekolah(Sekolah $sekolah, AbsensiSiswaService $siswa, AbsensiGuruService $guru, ?Carbon $manual): void
    {
        $p = Pengaturan::first();

        if (! $p) {
            $this->warn("{$sekolah->nama}: pengaturan belum diisi.");

            return;
        }

        if ($manual) {
            $tanggal = $manual;
        } else {
            $tanggal = today();

            if (now()->format('H:i:s') < $p->jam_tutup_absensi) {
                return;
            }
            if (! Cache::add("absensi-tutup:{$sekolah->id}:".$tanggal->toDateString(), true, now()->addDay())) {
                return; // hari ini sudah ditutup untuk sekolah ini
            }
        }

        $s = $siswa->tutupHari($tanggal);
        $g = $guru->tutupHari($tanggal);

        if (! empty($s['dilewati'])) {
            $this->info("{$sekolah->nama} {$tanggal->toDateString()}: dilewati (bukan hari efektif, semester terkunci, atau tahun ajaran belum aktif).");

            return;
        }

        $this->info("{$sekolah->nama} {$tanggal->toDateString()}: {$s['alpa']} siswa alpa, {$s['kelas_belum_diabsen']} kelas belum diabsen, {$g['tanpa_keterangan']} guru tanpa keterangan.");
    }
}