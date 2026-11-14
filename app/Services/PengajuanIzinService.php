<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\PengajuanIzin;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\Notifier;
use App\Models\AbsensiGuru;

class PengajuanIzinService
{
    public function __construct(private AbsensiSiswaService $absensi)
    {
    }

    public function waliKelasDari(User $user, Siswa $siswa): bool
    {
        $guru = $user->guru;
        if (! $guru) {
            return false;
        }

        return Kelas::where('wali_kelas_id', $guru->id)
            ->where('tahun_ajaran_id', TahunAjaran::aktif()?->id)
            ->whereHas('siswa', fn ($q) => $q->where('siswa.id', $siswa->id))
            ->exists();
    }

    public function queryUntuk(User $user): Builder
    {
        $q = PengajuanIzin::with(['siswa:id,nama', 'guru.user:id,name'])->latest();

        if ($user->hasRole(User::ROLE_ORANG_TUA)) {
            $ids = $user->orangTua?->anak()->pluck('id') ?? collect();
            $q->whereIn('siswa_id', $ids);
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $guruId = $user->guru?->id;
            $siswaIds = DB::table('kelas_siswa')
                ->join('kelas', 'kelas.id', '=', 'kelas_siswa.kelas_id')
                ->where('kelas.wali_kelas_id', $guruId)
                ->where('kelas.tahun_ajaran_id', TahunAjaran::aktif()?->id)
                ->pluck('kelas_siswa.siswa_id');
            $q->where(fn ($w) => $w->where('guru_id', $guruId)->orWhereIn('siswa_id', $siswaIds));
        } elseif ($user->hasRole(User::ROLE_KEPALA_SEKOLAH)) {
            $q->whereNotNull('guru_id');
        }

        return $q;
    }

    public function ajukan(User $user, array $data, ?UploadedFile $lampiran): PengajuanIzin
    {
        $p = Pengaturan::current();
        $mulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $selesai = Carbon::parse($data['tanggal_selesai'])->startOfDay();

        abort_if(
            $mulai->lt(today()->subDays($p->batas_pengajuan_mundur_hari)),
            422,
            "Pengajuan mundur maksimal {$p->batas_pengajuan_mundur_hari} hari."
        );
        abort_if($mulai->diffInDays($selesai) > 30, 422, 'Rentang pengajuan maksimal 30 hari.');
        abort_if($this->absensi->semesterTerkunci($mulai, $selesai), 422, 'Rentang tanggal masuk semester yang sudah dikunci.');

        $siswaId = null;
        $guruId = null;
        $autoSetuju = false;

        if ($user->hasRole(User::ROLE_ORANG_TUA)) {
            abort_if(empty($data['siswa_id']), 422, 'siswa_id wajib diisi.');
            $siswa = Siswa::findOrFail($data['siswa_id']);
            abort_unless($user->orangTua && $siswa->orang_tua_id === $user->orangTua->id, 403, 'Bukan data anak Anda.');
            $siswaId = $siswa->id;
        } elseif (! empty($data['siswa_id'])) {
            $siswa = Siswa::findOrFail($data['siswa_id']);
            abort_unless($this->waliKelasDari($user, $siswa), 403, 'Anda bukan wali kelas siswa ini.');
            $siswaId = $siswa->id;
            $autoSetuju = true; // wali kelas input dari surat fisik, langsung disetujui
        } else {
            abort_unless($user->guru, 422, 'Akun ini tidak terhubung ke data guru.');
            $guruId = $user->guru->id;
        }

        abort_if($siswaId && $data['jenis'] === 'dinas_luar', 422, 'Jenis dinas luar hanya untuk guru.');

        $bentrok = PengajuanIzin::where('status', '!=', PengajuanIzin::STATUS_DITOLAK)
            ->when($siswaId, fn ($q) => $q->where('siswa_id', $siswaId), fn ($q) => $q->where('guru_id', $guruId))
            ->whereDate('tanggal_mulai', '<=', $selesai)
            ->whereDate('tanggal_selesai', '>=', $mulai)
            ->exists();
        abort_if($bentrok, 422, 'Sudah ada pengajuan pada rentang tanggal tersebut.');

        $pengajuan = PengajuanIzin::create([
            'siswa_id' => $siswaId,
            'guru_id' => $guruId,
            'jenis' => $data['jenis'],
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'alasan' => $data['alasan'] ?? null,
            'lampiran' => $lampiran?->store('pengajuan-izin', 'public'),
            'status' => PengajuanIzin::STATUS_MENUNGGU,
            'diajukan_oleh' => $user->id,
        ]);

        if ($autoSetuju) {
            $this->setujui($user, $pengajuan);
        }

        return $pengajuan->fresh();
    }

    public function bolehMemproses(User $user, PengajuanIzin $p): bool
    {
        if ($p->status !== PengajuanIzin::STATUS_MENUNGGU) {
            return false;
        }
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        if ($p->siswa_id) {
            return $this->waliKelasDari($user, $p->siswa);
        }

        return $user->hasRole(User::ROLE_KEPALA_SEKOLAH);
    }

    public function setujui(User $user, PengajuanIzin $p): void
    {
        abort_unless($this->bolehMemproses($user, $p), 403, 'Anda tidak berwenang atau pengajuan sudah diproses.');
        abort_if(
            $this->absensi->semesterTerkunci($p->tanggal_mulai, $p->tanggal_selesai),
            422,
            'Rentang tanggal masuk semester yang sudah dikunci.'
        );

        DB::transaction(function () use ($user, $p) {
            $p->update([
                'status' => PengajuanIzin::STATUS_DISETUJUI,
                'diproses_oleh' => $user->id,
                'diproses_pada' => now(),
            ]);

            // Pengajuan guru: absensi_guru baru ada di Fase 2, di sini cukup status pengajuan.
            if ($p->siswa_id) {
                $this->isiAbsensiSiswa($user, $p);
            } elseif ($p->guru_id) {
                $this->isiAbsensiGuru($user, $p);
            }

            AuditLogger::log('setujui_pengajuan', 'pengajuan_izin', $p->id, ['status' => 'menunggu'], ['status' => 'disetujui']);

            $this->beritahuPemohon(
                $p,
                'izin_disetujui',
                'Pengajuan izin disetujui',
                'Pengajuan '.str_replace('_', ' ', $p->jenis).' '.$this->namaPemohon($p).' tanggal '.$this->rentang($p).' disetujui.'
            );
        });
    }

    private function isiAbsensiGuru(User $user, PengajuanIzin $p): void
    {
        foreach (CarbonPeriod::create($p->tanggal_mulai, $p->tanggal_selesai) as $tgl) {
            if (! HariLibur::hariEfektif($tgl)) {
                continue;
            }

            $old = AbsensiGuru::where('guru_id', $p->guru_id)->where('tanggal', $tgl->toDateString())->first();

            // Hari yang sudah punya absen masuk nyata tidak ditimpa.
            if ($old && $old->jam_masuk) {
                continue;
            }

            $rec = AbsensiGuru::updateOrCreate(
                ['guru_id' => $p->guru_id, 'tanggal' => $tgl->toDateString()],
                [
                    'status' => $p->jenis, // sakit, izin, atau dinas_luar
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'sumber' => 'pengajuan',
                    'pengajuan_izin_id' => $p->id,
                    'catatan' => $p->alasan ? Str::limit($p->alasan, 250) : null,
                    'dicatat_oleh' => $user->id,
                ]
            );

            if ($old && $old->status !== $rec->status) {
                AuditLogger::log('ubah_status', 'absensi_guru', $rec->id,
                    ['status' => $old->status, 'sumber' => $old->sumber],
                    ['status' => $rec->status, 'sumber' => $rec->sumber]);
            }
        }
    }

    public function tolak(User $user, PengajuanIzin $p, string $catatan): void
    {
        abort_unless($this->bolehMemproses($user, $p), 403, 'Anda tidak berwenang atau pengajuan sudah diproses.');

        DB::transaction(function () use ($user, $p, $catatan) {
            $p->update([
                'status' => PengajuanIzin::STATUS_DITOLAK,
                'diproses_oleh' => $user->id,
                'diproses_pada' => now(),
                'catatan_penolakan' => $catatan,
            ]);

            AuditLogger::log('tolak_pengajuan', 'pengajuan_izin', $p->id, ['status' => 'menunggu'], ['status' => 'ditolak']);

            $isi = 'Pengajuan '.str_replace('_', ' ', $p->jenis).' '.$this->namaPemohon($p).' tanggal '.$this->rentang($p)
                .' ditolak. Alasan: '.$catatan.'.';

            if ($p->siswa_id) {
                $alpa = $this->absensi->alpaUntukSiswa($p->siswa, $p->tanggal_mulai, $p->tanggal_selesai);
                $jamTutup = substr(Pengaturan::current()->jam_tutup_absensi, 0, 5);

                if ($alpa > 0) {
                    $isi .= ' Karena tanggal tersebut sudah lewat, anak tercatat alpa.';
                }
                $isi .= " Jika sakit atau izin memang terjadi, ajukan ulang dengan keterangan yang sesuai."
                    ." Tanpa pengajuan baru sampai pukul {$jamTutup} pada hari yang bersangkutan, anak tercatat alpa.";
            }

            $this->beritahuPemohon($p, 'izin_ditolak', 'Pengajuan izin ditolak', $isi);
        });
    }

    private function beritahuPemohon(PengajuanIzin $p, string $tipe, string $judul, string $isi): void
    {
        $userId = $p->siswa_id ? $p->siswa?->orangTua?->user_id : $p->guru?->user_id;

        if ($userId) {
            Notifier::kirim($userId, $tipe, $judul, $isi, ['pengajuan_id' => $p->id]);
        }
    }

    private function namaPemohon(PengajuanIzin $p): string
    {
        return $p->siswa?->nama ?? ($p->guru?->user->name ?? '');
    }

    private function rentang(PengajuanIzin $p): string
    {
        return $p->tanggal_mulai->isSameDay($p->tanggal_selesai)
            ? $p->tanggal_mulai->format('d/m/Y')
            : $p->tanggal_mulai->format('d/m/Y').' - '.$p->tanggal_selesai->format('d/m/Y');
    }

    private function isiAbsensiSiswa(User $user, PengajuanIzin $p): void
    {
        $kelasId = DB::table('kelas_siswa')
            ->where('siswa_id', $p->siswa_id)
            ->where('tahun_ajaran_id', TahunAjaran::aktif()?->id)
            ->value('kelas_id');
        abort_unless($kelasId, 422, 'Siswa belum terdaftar di kelas pada tahun ajaran aktif.');

        foreach (CarbonPeriod::create($p->tanggal_mulai, $p->tanggal_selesai) as $tgl) {
            if (! HariLibur::hariEfektif($tgl)) {
                continue;
            }

            $old = AbsensiSiswa::where('siswa_id', $p->siswa_id)->where('tanggal', $tgl->toDateString())->first();

            $rec = AbsensiSiswa::updateOrCreate(
                ['siswa_id' => $p->siswa_id, 'tanggal' => $tgl->toDateString()],
                [
                    'kelas_id' => $kelasId,
                    'status' => $p->jenis, // sakit atau izin
                    'jam_masuk' => null,
                    'catatan' => $p->alasan ? Str::limit($p->alasan, 250) : null,
                    'sumber' => AbsensiSiswa::SUMBER_PENGAJUAN,
                    'pengajuan_izin_id' => $p->id,
                    'dicatat_oleh' => $user->id,
                ]
            );

            if ($old && $old->status !== $rec->status) {
                AuditLogger::log('ubah_status', 'absensi_siswa', $rec->id,
                    ['status' => $old->status, 'sumber' => $old->sumber],
                    ['status' => $rec->status, 'sumber' => $rec->sumber]);
            }
        }
    }
}
