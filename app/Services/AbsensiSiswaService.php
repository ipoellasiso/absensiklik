<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\PengajuanIzin;
use App\Models\TahunAjaran;
use App\Support\Notifier;
use Carbon\CarbonPeriod;

class AbsensiSiswaService
{
    /** Hak lihat: admin, kepala sekolah, atau wali kelas. */
    public function pastikanAkses(User $user, Kelas $kelas): void
    {
        if ($user->hasRole(User::ROLE_ADMIN, User::ROLE_KEPALA_SEKOLAH)) {
            return;
        }

        $kelas->loadMissing('waliKelas');
        abort_unless(
            $kelas->waliKelas && $kelas->waliKelas->user_id === $user->id,
            403,
            'Anda bukan wali kelas ini.'
        );
    }

    /** Hak tulis: aturan tanggal untuk semua, aturan wali kelas dan batas koreksi untuk non-admin. */
    public function pastikanBoleh(User $user, Kelas $kelas, CarbonInterface $tanggal): void
    {
        abort_if($tanggal->gt(today()), 422, 'Tanggal tidak boleh di masa depan.');
        abort_unless(HariLibur::hariEfektif($tanggal), 422, 'Tanggal tersebut bukan hari efektif.');
        abort_if($this->semesterTerkunci($tanggal), 422, 'Semester sudah dikunci. Koreksi hanya lewat admin.');

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return;
        }

        $this->pastikanAkses($user, $kelas);

        $batas = Pengaturan::current()->batas_koreksi_hari;
        abort_if(
            $tanggal->lt(today()->subDays($batas)),
            403,
            'Batas waktu koreksi sudah lewat. Hubungi admin.'
        );
    }

    public function semesterTerkunci(CarbonInterface $dari, ?CarbonInterface $sampai = null): bool
    {
        $sampai ??= $dari;

        return Semester::where('is_terkunci', true)
            ->whereDate('tanggal_mulai', '<=', $sampai)
            ->whereDate('tanggal_selesai', '>=', $dari)
            ->exists();
    }

    public function simpanBulk(User $user, Kelas $kelas, Carbon $tanggal, array $items): array
    {
        $this->pastikanBoleh($user, $kelas, $tanggal);

        $ids = collect($items)->pluck('siswa_id')->map(fn ($v) => (int) $v);
        $anggota = DB::table('kelas_siswa')
            ->where('kelas_id', $kelas->id)
            ->whereIn('siswa_id', $ids)
            ->pluck('siswa_id');

        if ($ids->diff($anggota)->isNotEmpty()) {
            throw ValidationException::withMessages(['data' => ['Ada siswa yang bukan anggota kelas ini.']]);
        }

        $tgl = $tanggal->toDateString();
        $lama = AbsensiSiswa::where('tanggal', $tgl)->whereIn('siswa_id', $ids)->get()->keyBy('siswa_id');
        $berubah = 0;

        DB::transaction(function () use ($items, $kelas, $tgl, $user, $lama, &$berubah) {
            foreach ($items as $item) {
                $old = $lama->get($item['siswa_id']);
                $masuk = in_array($item['status'], [AbsensiSiswa::HADIR, AbsensiSiswa::TERLAMBAT], true);

                $rec = AbsensiSiswa::updateOrCreate(
                    ['siswa_id' => $item['siswa_id'], 'tanggal' => $tgl],
                    [
                        'kelas_id' => $kelas->id,
                        'status' => $item['status'],
                        'jam_masuk' => $masuk ? $old?->jam_masuk : null,
                        'catatan' => $item['catatan'] ?? null,
                        'sumber' => AbsensiSiswa::SUMBER_MANUAL,
                        'pengajuan_izin_id' => null,
                        'dicatat_oleh' => $user->id,
                    ]
                );

                if ($old && $old->status !== $rec->status) {
                    $berubah++;
                    AuditLogger::log('ubah_status', 'absensi_siswa', $rec->id,
                        ['status' => $old->status, 'sumber' => $old->sumber],
                        ['status' => $rec->status, 'sumber' => $rec->sumber]);
                }
            }
        });

        return ['tersimpan' => count($items), 'berubah' => $berubah];
    }

    public function scan(User $user, Kelas $kelas, string $qrToken): array
    {
        $tanggal = today();
        $this->pastikanBoleh($user, $kelas, $tanggal);

        $siswa = Siswa::where('qr_token', $qrToken)->where('is_aktif', true)->first();
        abort_unless($siswa, 404, 'Kartu tidak dikenali.');

        $anggota = DB::table('kelas_siswa')
            ->where('kelas_id', $kelas->id)
            ->where('siswa_id', $siswa->id)
            ->exists();
        abort_unless($anggota, 422, "{$siswa->nama} bukan siswa kelas ini.");

        $p = Pengaturan::current();
        $batas = Carbon::today()->setTimeFromTimeString($p->jam_masuk)->addMinutes($p->batas_terlambat_menit);
        $status = now()->gt($batas) ? AbsensiSiswa::TERLAMBAT : AbsensiSiswa::HADIR;

        $rec = AbsensiSiswa::firstOrCreate(
            ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()],
            [
                'kelas_id' => $kelas->id,
                'status' => $status,
                'jam_masuk' => now()->format('H:i:s'),
                'sumber' => AbsensiSiswa::SUMBER_QR,
                'dicatat_oleh' => $user->id,
            ]
        );

        return [
            'baru' => $rec->wasRecentlyCreated,
            'hasil' => $rec->wasRecentlyCreated ? 'tercatat' : 'sudah_tercatat',
            'siswa' => ['id' => $siswa->id, 'nama' => $siswa->nama],
            'status' => $rec->status,
            'jam_masuk' => $rec->jam_masuk,
        ];
    }

    /**
     * Penutupan hari: siswa yang belum tercatat menjadi alpa.
     * Kelas yang belum diabsen sama sekali dilewati (wali kelas diberi tahu), dan
     * siswa dengan pengajuan izin yang masih menunggu tidak di-alpa-kan.
     */
    public function tutupHari(CarbonInterface $tanggal): array
    {
        $hasil = ['alpa' => 0, 'kelas_belum_diabsen' => 0];
        $ta = TahunAjaran::aktif();

        if (! $ta || ! HariLibur::hariEfektif($tanggal) || $this->semesterTerkunci($tanggal)) {
            return $hasil + ['dilewati' => true];
        }

        $tgl = $tanggal->toDateString();
        $p = Pengaturan::current();

        foreach (Kelas::where('tahun_ajaran_id', $ta->id)->with('waliKelas')->get() as $kelas) {
            $siswaIds = DB::table('kelas_siswa as ks')
                ->join('siswa as s', 's.id', '=', 'ks.siswa_id')
                ->where('ks.kelas_id', $kelas->id)
                ->where('s.is_aktif', true)
                ->pluck('ks.siswa_id');

            if ($siswaIds->isEmpty()) {
                continue;
            }

            $tercatat = AbsensiSiswa::where('tanggal', $tgl)->whereIn('siswa_id', $siswaIds)->pluck('siswa_id');

            if ($tercatat->isEmpty()) {
                $hasil['kelas_belum_diabsen']++;
                if ($kelas->waliKelas) {
                    Notifier::kirim(
                        $kelas->waliKelas->user_id,
                        'kelas_belum_diabsen',
                        "Kelas {$kelas->nama} belum diabsen",
                        "Absensi tanggal {$tanggal->format('d/m/Y')} belum diisi. Segera isi (batas koreksi {$p->batas_koreksi_hari} hari).",
                        ['kelas_id' => $kelas->id, 'tanggal' => $tgl]
                    );
                }

                continue;
            }

            $menunggu = PengajuanIzin::where('status', PengajuanIzin::STATUS_MENUNGGU)
                ->whereIn('siswa_id', $siswaIds)
                ->whereDate('tanggal_mulai', '<=', $tgl)
                ->whereDate('tanggal_selesai', '>=', $tgl)
                ->pluck('siswa_id');

            $target = $siswaIds->diff($tercatat)->diff($menunggu);

            foreach (Siswa::with('orangTua')->whereIn('id', $target)->get() as $siswa) {
                $rec = AbsensiSiswa::firstOrCreate(
                    ['siswa_id' => $siswa->id, 'tanggal' => $tgl],
                    [
                        'kelas_id' => $kelas->id,
                        'status' => AbsensiSiswa::ALPA,
                        'catatan' => 'Alpa otomatis: tidak ada keterangan',
                        'sumber' => AbsensiSiswa::SUMBER_OTOMATIS,
                    ]
                );

                if (! $rec->wasRecentlyCreated) {
                    continue;
                }

                $hasil['alpa']++;
                AuditLogger::log('alpa_otomatis', 'absensi_siswa', $rec->id, null, ['tanggal' => $tgl, 'status' => 'alpa']);
                $this->beritahuAlpa($siswa, $tanggal, $p->batas_pengajuan_mundur_hari);
            }
        }

        return $hasil;
    }

    /**
     * Dipakai saat pengajuan izin ditolak: tanggal yang sudah lewat jam tutup
     * dan belum punya catatan langsung menjadi alpa.
     */
    public function alpaUntukSiswa(Siswa $siswa, CarbonInterface $dari, CarbonInterface $sampai): int
    {
        $ta = TahunAjaran::aktif();
        $kelasId = $ta
            ? DB::table('kelas_siswa')->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $ta->id)->value('kelas_id')
            : null;

        if (! $kelasId) {
            return 0;
        }

        $p = Pengaturan::current();
        $dibuat = 0;

        foreach (CarbonPeriod::create($dari, $sampai) as $tgl) {
            $sudahTutup = $tgl->lt(today()) || ($tgl->isToday() && now()->format('H:i:s') >= $p->jam_tutup_absensi);

            if (! $sudahTutup || ! HariLibur::hariEfektif($tgl) || $this->semesterTerkunci($tgl)) {
                continue;
            }

            $rec = AbsensiSiswa::firstOrCreate(
                ['siswa_id' => $siswa->id, 'tanggal' => $tgl->toDateString()],
                [
                    'kelas_id' => $kelasId,
                    'status' => AbsensiSiswa::ALPA,
                    'catatan' => 'Alpa otomatis: pengajuan izin ditolak',
                    'sumber' => AbsensiSiswa::SUMBER_OTOMATIS,
                ]
            );

            if ($rec->wasRecentlyCreated) {
                $dibuat++;
                AuditLogger::log('alpa_otomatis', 'absensi_siswa', $rec->id, null, ['tanggal' => $tgl->toDateString(), 'status' => 'alpa']);
            }
        }

        return $dibuat;
    }

    private function beritahuAlpa(Siswa $siswa, CarbonInterface $tanggal, int $batasMundur): void
    {
        $userId = $siswa->orangTua?->user_id;
        if (! $userId) {
            return;
        }

        Notifier::kirim(
            $userId,
            'alpa_otomatis',
            "{$siswa->nama} tercatat alpa",
            "{$siswa->nama} tercatat alpa pada {$tanggal->format('d/m/Y')}. Jika anak sakit atau izin, ajukan izin dalam {$batasMundur} hari agar status diperbaiki.",
            ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()]
        );
    }

}
