<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\RekapKehadiranSemester;
use App\Models\Semester;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RekapService
{
    /** Hari efektif (bukan akhir pekan dan bukan hari libur) pada rentang tanggal. */
    public function hariEfektif(CarbonInterface $dari, CarbonInterface $sampai): Collection
    {
        if ($sampai->lt($dari)) {
            return collect();
        }

        $libur = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->get()
            ->map(fn ($h) => $h->tanggal->toDateString())
            ->flip();

        $hasil = collect();
        foreach (CarbonPeriod::create($dari->copy()->startOfDay(), $sampai->copy()->startOfDay()) as $d) {
            if (! $d->isWeekend() && ! $libur->has($d->toDateString())) {
                $hasil->push($d->copy());
            }
        }

        return $hasil;
    }

    /** Matriks siswa dikali tanggal untuk satu kelas pada satu bulan. */
    public function matriksKelas(Kelas $kelas, Carbon $bulan): array
    {
        $awal = $bulan->copy()->startOfMonth();
        $sampai = $awal->copy()->endOfMonth()->min(today());
        $hari = $this->hariEfektif($awal, $sampai);
        $siswa = $this->siswaKelas($kelas);

        $absen = AbsensiSiswa::whereIn('siswa_id', $siswa->pluck('id'))
            ->whereBetween('tanggal', [$awal->toDateString(), $sampai->toDateString()])
            ->get(['siswa_id', 'tanggal', 'status'])
            ->groupBy('siswa_id');

        $baris = $siswa->map(function ($s) use ($absen, $hari) {
            $cat = $absen->get($s->id, collect())->mapWithKeys(fn ($a) => [$a->tanggal->toDateString() => $a->status]);
            $sel = $hari->mapWithKeys(fn ($d) => [$d->toDateString() => $cat->get($d->toDateString())])->all();

            $hit = array_fill_keys(['hadir', 'terlambat', 'sakit', 'izin', 'alpa'], 0);
            foreach ($sel as $st) {
                if ($st && isset($hit[$st])) {
                    $hit[$st]++;
                }
            }

            $total = $hari->count();

            return [
                'siswa_id' => $s->id,
                'nama' => $s->nama,
                'nisn' => $s->nisn,
                'jk' => $s->jenis_kelamin,
                'sel' => $sel,
                'hit' => $hit,
                'persen' => $total > 0 ? min(100, round(($hit['hadir'] + $hit['terlambat']) / $total * 100, 1)) : null,
            ];
        });

        return ['hari' => $hari, 'baris' => $baris];
    }

    /** Matriks guru dikali tanggal pada satu bulan. */
    public function matriksGuru(Carbon $bulan): array
    {
        $awal = $bulan->copy()->startOfMonth();
        $sampai = $awal->copy()->endOfMonth()->min(today());
        $hari = $this->hariEfektif($awal, $sampai);

        $absen = AbsensiGuru::whereBetween('tanggal', [$awal->toDateString(), $sampai->toDateString()])
            ->get(['guru_id', 'tanggal', 'status'])
            ->groupBy('guru_id');

        $baris = Guru::with('user')->get()
            ->filter(fn ($g) => $g->user && ($g->user->is_active || $absen->has($g->id)))
            ->sortBy(fn ($g) => $g->user->name)
            ->values()
            ->map(function ($g) use ($absen, $hari) {
                $cat = $absen->get($g->id, collect())->mapWithKeys(fn ($a) => [$a->tanggal->toDateString() => $a->status]);
                $sel = $hari->mapWithKeys(fn ($d) => [$d->toDateString() => $cat->get($d->toDateString())])->all();

                $hit = array_fill_keys(AbsensiGuru::STATUS, 0);
                foreach ($sel as $st) {
                    if ($st && isset($hit[$st])) {
                        $hit[$st]++;
                    }
                }

                $total = $hari->count();

                return [
                    'nama' => $g->user->name,
                    'nip' => $g->nip,
                    'sel' => $sel,
                    'hit' => $hit,
                    'persen' => $total > 0
                        ? min(100, round(($hit['hadir'] + $hit['terlambat'] + $hit['dinas_luar']) / $total * 100, 1))
                        : null,
                ];
            });

        return ['hari' => $hari, 'baris' => $baris];
    }

    /**
     * Rekap semester per siswa dalam satu kelas.
     * Jika semester terkunci, angka diambil dari rekap beku (final).
     */
    public function hitungSemesterKelas(Kelas $kelas, Semester $semester, bool $pakaiBeku = true): array
    {
        $dari = $semester->tanggal_mulai->copy();
        $sampai = $semester->tanggal_selesai->copy()->min(today());
        $hariEfektif = $this->hariEfektif($dari, $sampai)->count();

        $siswa = $this->siswaKelas($kelas);
        $ids = $siswa->pluck('id');

        $hitungan = AbsensiSiswa::whereIn('siswa_id', $ids)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->selectRaw('siswa_id, status, count(*) as total')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id');

        $beku = ($pakaiBeku && $semester->is_terkunci)
            ? RekapKehadiranSemester::where('semester_id', $semester->id)->whereIn('siswa_id', $ids)->get()->keyBy('siswa_id')
            : collect();

        $rows = $siswa->map(function ($s) use ($hitungan, $beku, $hariEfektif) {
            $dasar = ['siswa_id' => $s->id, 'nama' => $s->nama, 'nisn' => $s->nisn, 'jk' => $s->jenis_kelamin];

            if ($beku->has($s->id)) {
                $r = $beku->get($s->id);

                return $dasar + [
                    'hari_efektif' => $r->hari_efektif,
                    'hadir' => $r->hadir, 'terlambat' => $r->terlambat, 'sakit' => $r->sakit,
                    'izin' => $r->izin, 'alpa' => $r->alpa,
                    'persen' => (float) $r->persentase_kehadiran,
                    'sumber' => 'final',
                ];
            }

            $h = $hitungan->get($s->id, collect())->pluck('total', 'status');
            $hadir = (int) ($h['hadir'] ?? 0);
            $terlambat = (int) ($h['terlambat'] ?? 0);

            return $dasar + [
                'hari_efektif' => $hariEfektif,
                'hadir' => $hadir, 'terlambat' => $terlambat,
                'sakit' => (int) ($h['sakit'] ?? 0), 'izin' => (int) ($h['izin'] ?? 0), 'alpa' => (int) ($h['alpa'] ?? 0),
                'persen' => $hariEfektif > 0 ? min(100, round(($hadir + $terlambat) / $hariEfektif * 100, 2)) : 0,
                'sumber' => 'sementara',
            ];
        });

        return [
            'rows' => $rows,
            'hari_efektif' => $hariEfektif,
            'final' => $rows->isNotEmpty() && $rows->every(fn ($r) => $r['sumber'] === 'final'),
        ];
    }

    /** Membekukan rekap seluruh kelas pada tahun ajaran semester tersebut. */
    public function bekukanSemester(Semester $semester): int
    {
        $jumlah = 0;

        foreach (Kelas::where('tahun_ajaran_id', $semester->tahun_ajaran_id)->get() as $kelas) {
            foreach ($this->hitungSemesterKelas($kelas, $semester, false)['rows'] as $r) {
                RekapKehadiranSemester::updateOrCreate(
                    ['siswa_id' => $r['siswa_id'], 'semester_id' => $semester->id],
                    [
                        'kelas_id' => $kelas->id,
                        'hari_efektif' => $r['hari_efektif'],
                        'hadir' => $r['hadir'],
                        'sakit' => $r['sakit'],
                        'izin' => $r['izin'],
                        'alpa' => $r['alpa'],
                        'terlambat' => $r['terlambat'],
                        'persentase_kehadiran' => $r['persen'],
                    ]
                );
                $jumlah++;
            }
        }

        return $jumlah;
    }

    public function hapusSnapshot(Semester $semester): void
    {
        RekapKehadiranSemester::where('semester_id', $semester->id)->delete();
    }

    private function siswaKelas(Kelas $kelas): Collection
    {
        return DB::table('kelas_siswa as ks')
            ->join('siswa as s', 's.id', '=', 'ks.siswa_id')
            ->where('ks.kelas_id', $kelas->id)
            ->orderBy('s.nama')
            ->get(['s.id', 's.nama', 's.nisn', 's.jenis_kelamin']);
    }
}