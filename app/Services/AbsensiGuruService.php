<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Notifier;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use App\Support\Tenant;

class AbsensiGuruService
{
    public function __construct(private AbsensiSiswaService $siswa)
    {
    }

    public function masuk(User $user, array $d, ?UploadedFile $foto = null): AbsensiGuru
    {
        $guru = $this->guru($user);
        $p = $this->pastikanBolehAbsen($d, $foto);
        $tgl = today()->toDateString();

        $rec = AbsensiGuru::where('guru_id', $guru->id)->where('tanggal', $tgl)->first();
        if ($rec) {
            abort_if($rec->jam_masuk, 422, 'Anda sudah absen masuk pukul '.substr($rec->jam_masuk, 0, 5).'.');
            abort_if(in_array($rec->status, ['sakit', 'izin', 'dinas_luar'], true), 422,
                'Hari ini tercatat '.str_replace('_', ' ', $rec->status).'. Hubungi kepala sekolah jika keliru.');
        }

        $jarak = $this->jarak($p, $d);
        $batas = Carbon::today()->setTimeFromTimeString($p->jam_masuk)->addMinutes($p->batas_terlambat_menit);

        return AbsensiGuru::updateOrCreate(
            ['guru_id' => $guru->id, 'tanggal' => $tgl],
            [
                'jam_masuk' => now()->format('H:i:s'),
                'status' => now()->gt($batas) ? 'terlambat' : 'hadir',
                'lat_masuk' => $d['lat'],
                'lng_masuk' => $d['lng'],
                'jarak_masuk_m' => $jarak,
                'foto_masuk' => $this->simpanFoto($foto, $guru->id),
                'sumber' => 'aplikasi',
                'pengajuan_izin_id' => null,
                'catatan' => null,
                'dicatat_oleh' => $user->id,
            ]
        );
    }

    public function pulang(User $user, array $d, ?UploadedFile $foto = null): AbsensiGuru
    {
        $guru = $this->guru($user);
        $p = $this->pastikanBolehAbsen($d, $foto);

        $rec = AbsensiGuru::where('guru_id', $guru->id)->where('tanggal', today()->toDateString())->first();
        abort_unless($rec && $rec->jam_masuk, 422, 'Anda belum absen masuk hari ini.');
        abort_if($rec->jam_pulang, 422, 'Anda sudah absen pulang pukul '.substr($rec->jam_pulang, 0, 5).'.');

        $jarak = $this->jarak($p, $d);

        $rec->update([
            'jam_pulang' => now()->format('H:i:s'),
            'lat_pulang' => $d['lat'],
            'lng_pulang' => $d['lng'],
            'jarak_pulang_m' => $jarak,
            'foto_pulang' => $this->simpanFoto($foto, $guru->id),
        ]);

        return $rec;
    }

    /**
     * Penutupan hari untuk guru: yang belum punya catatan menjadi tanpa keterangan.
     */
    public function tutupHari(CarbonInterface $tanggal): array
    {
        if (! HariLibur::hariEfektif($tanggal) || $this->siswa->semesterTerkunci($tanggal)) {
            return ['tanpa_keterangan' => 0, 'dilewati' => true];
        }

        $tgl = $tanggal->toDateString();
        $batasMundur = Pengaturan::current()->batas_pengajuan_mundur_hari;
        $sudah = AbsensiGuru::where('tanggal', $tgl)->pluck('guru_id');
        $nama = [];

        $target = Guru::with('user')
            ->whereNotIn('id', $sudah)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        foreach ($target as $g) {
            $rec = AbsensiGuru::firstOrCreate(
                ['guru_id' => $g->id, 'tanggal' => $tgl],
                ['status' => 'tanpa_keterangan', 'sumber' => 'otomatis', 'catatan' => 'Tidak absen dan tidak ada keterangan']
            );

            if (! $rec->wasRecentlyCreated) {
                continue;
            }

            AuditLogger::log('tanpa_keterangan_otomatis', 'absensi_guru', $rec->id, null, ['tanggal' => $tgl]);
            $nama[] = $g->user->name;

            Notifier::kirim(
                $g->user_id,
                'guru_tanpa_keterangan',
                'Absensi hari ini kosong',
                "Anda tercatat tanpa keterangan pada {$tanggal->format('d/m/Y')}. Ajukan izin susulan dalam {$batasMundur} hari atau hubungi kepala sekolah.",
                ['tanggal' => $tgl]
            );
        }

        if ($nama) {
            $daftar = implode(', ', array_slice($nama, 0, 10)).(count($nama) > 10 ? ' dan lainnya' : '');

            // foreach (User::where('role', User::ROLE_KEPALA_SEKOLAH)->where('is_active', true)->get() as $kepsek) {
            foreach (User::where('role', User::ROLE_KEPALA_SEKOLAH)->where('sekolah_id', Tenant::id())->where('is_active', true)->get() as $kepsek) {
                Notifier::kirim(
                    $kepsek->id,
                    'guru_tanpa_keterangan_ringkasan',
                    count($nama).' guru tanpa keterangan',
                    "Tanggal {$tanggal->format('d/m/Y')}: {$daftar}.",
                    ['tanggal' => $tgl]
                );
            }
        }

        return ['tanpa_keterangan' => count($nama)];
    }

    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round(6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function guru(User $user): Guru
    {
        abort_unless($user->guru, 403, 'Akun ini tidak terhubung ke data guru.');

        return $user->guru;
    }

    private function pastikanBolehAbsen(array $d, ?UploadedFile $foto = null): Pengaturan
    {
        $p = Pengaturan::current();
        $hari = today();

        abort_unless(HariLibur::hariEfektif($hari), 422, 'Hari ini bukan hari efektif.');
        abort_if($this->siswa->semesterTerkunci($hari), 422, 'Semester sudah dikunci.');
        abort_if($p->latitude === null || $p->longitude === null, 422, 'Lokasi sekolah belum diatur. Hubungi admin.');
        abort_if($p->wajib_foto_guru && ! $foto, 422, 'Foto wajah wajib diambil untuk absen.');
        abort_if(! empty($d['mocked']), 422, 'Lokasi palsu (mock location) terdeteksi. Matikan aplikasi lokasi palsu lalu coba lagi.');
        abort_if(
            isset($d['akurasi']) && $d['akurasi'] > 150,
            422,
            'Akurasi GPS rendah (sekitar '.round($d['akurasi']).' m). Coba lagi di tempat terbuka.'
        );

        return $p;
    }

    private function simpanFoto(?UploadedFile $foto, int $guruId): ?string
    {
        // Disk "local" = storage/app, tidak bisa diakses lewat URL publik.
        // return $foto?->store('absensi-guru/'.now()->format('Y/m').'/'.$guruId, 'local');
        return $foto?->store('absensi-guru/'.Tenant::id().'/'.now()->format('Y/m').'/'.$guruId, 'local');
    }

    private function jarak(Pengaturan $p, array $d): int
    {
        $jarak = self::jarakMeter((float) $p->latitude, (float) $p->longitude, (float) $d['lat'], (float) $d['lng']);

        abort_if($jarak > $p->radius_meter, 422, "Anda berada sekitar {$jarak} m dari sekolah, di luar radius {$p->radius_meter} m.");

        return $jarak;
    }
}