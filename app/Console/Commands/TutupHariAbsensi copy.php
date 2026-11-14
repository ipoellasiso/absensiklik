<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use App\Services\AbsensiGuruService;
use App\Services\AbsensiSiswaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TutupHariAbsensi extends Command
{
    protected $signature = 'absensi:tutup-hari {--tanggal= : Format Y-m-d. Jika diisi, jam tutup tidak diperiksa}';

    protected $description = 'Alpa otomatis untuk siswa dan tanpa keterangan untuk guru yang belum tercatat';

    public function handle(AbsensiSiswaService $siswa, AbsensiGuruService $guru): int
    {
        if ($this->option('tanggal')) {
            $tanggal = Carbon::createFromFormat('Y-m-d', $this->option('tanggal'))->startOfDay();
        } else {
            $tanggal = today();

            if (now()->format('H:i:s') < (Pengaturan::first()?->jam_tutup_absensi ?? '14:00:00')) {
                return self::SUCCESS;
            }
            if (! Cache::add('absensi-tutup:'.$tanggal->toDateString(), true, now()->addDay())) {
                return self::SUCCESS; // hari ini sudah ditutup
            }
        }

        if (! Pengaturan::first()) {
            $this->warn('Pengaturan sekolah belum diisi.');

            return self::FAILURE;
        }

        $s = $siswa->tutupHari($tanggal);
        $g = $guru->tutupHari($tanggal);

        if (! empty($s['dilewati'])) {
            $this->info($tanggal->toDateString().': dilewati (bukan hari efektif, semester terkunci, atau tahun ajaran belum aktif).');

            return self::SUCCESS;
        }

        $this->info("{$tanggal->toDateString()}: {$s['alpa']} siswa alpa, {$s['kelas_belum_diabsen']} kelas belum diabsen, {$g['tanpa_keterangan']} guru tanpa keterangan.");

        return self::SUCCESS;
    }
}