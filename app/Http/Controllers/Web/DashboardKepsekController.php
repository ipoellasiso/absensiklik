<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Tenant;

class DashboardKepsekController extends Controller
{
    private const ALPA_SISWA = 3;       // dalam 30 hari terakhir
    private const TERLAMBAT_SISWA = 5;  // dalam 30 hari terakhir
    private const TERLAMBAT_GURU = 3;   // dalam bulan terpilih

    public function index(Request $request)
    {
        $ta = TahunAjaran::aktif();

        if (! $ta) {
            return view('dashboard-kepsek', ['title' => 'Dashboard', 'breadcumd' => 'Dashboard', 'ta' => null]);
        }

        $pct = fn (int $n, int $t) => $t > 0 ? round($n / $t * 100, 1) : null;

        // Daftar bulan pada tahun ajaran aktif (sampai bulan berjalan)
        $mulaiTa = $ta->tanggal_mulai->copy()->startOfMonth();
        $akhirTa = $ta->tanggal_selesai->copy()->startOfMonth();
        $sekarang = today()->startOfMonth();
        $ujung = $sekarang->lt($mulaiTa) ? $mulaiTa : ($sekarang->gt($akhirTa) ? $akhirTa : $sekarang);

        $bulanTersedia = [];
        foreach (CarbonPeriod::create($mulaiTa, '1 month', $ujung) as $b) {
            $bulanTersedia[$b->format('Y-m')] = $b->translatedFormat('F Y');
        }

        $bulan = (string) $request->query('bulan', '');
        if (! isset($bulanTersedia[$bulan])) {
            $bulan = $ujung->format('Y-m');
        }

        $awal = Carbon::parse($bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();
        if ($akhir->gt(today())) {
            $akhir = today();
        }
        $awalStr = $awal->toDateString();
        $akhirStr = $akhir->toDateString();
        $hariIni = today()->toDateString();

        // ── Hari ini ──
        $totalSiswa = DB::table('kelas_siswa as ks')
            ->join('siswa as s', 's.id', '=', 'ks.siswa_id')
            ->where('ks.tahun_ajaran_id', $ta->id)
            ->where('s.is_aktif', true)
            ->count();

        $siswaHariIni = DB::table('absensi_siswa as a')
            ->join('kelas as k', 'k.id', '=', 'a.kelas_id')
            ->where('k.tahun_ajaran_id', $ta->id)
            ->where('a.tanggal', $hariIni)
            ->selectRaw('a.status, count(*) as total')
            ->groupBy('a.status')
            ->pluck('total', 'status');

        $totalGuru = Guru::whereHas('user', fn ($q) => $q->where('is_active', true))->count();

        $guruHariIni = AbsensiGuru::where('tanggal', $hariIni)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $kelasBelum = HariLibur::hariEfektif(today())
            ? Kelas::where('tahun_ajaran_id', $ta->id)
                ->whereHas('siswa', fn ($q) => $q->where('siswa.is_aktif', true))
                ->whereDoesntHave('absensiSiswa', fn ($q) => $q->where('tanggal', $hariIni))
                ->with('waliKelas.user:id,name')
                ->orderBy('tingkat')->orderBy('nama')->get()
            : collect();

        // ── Grafik harian (bulan terpilih) ──
        $harian = DB::table('absensi_siswa as a')
            ->join('kelas as k', 'k.id', '=', 'a.kelas_id')
            ->where('k.tahun_ajaran_id', $ta->id)
            ->whereBetween('a.tanggal', [$awalStr, $akhirStr])
            ->selectRaw('a.tanggal, a.status, count(*) as total')
            ->groupBy('a.tanggal', 'a.status')
            ->orderBy('a.tanggal')
            ->get();

        $tanggalList = $harian->pluck('tanggal')->unique()->values();
        $labelHarian = $tanggalList->map(fn ($t) => Carbon::parse($t)->format('d/m'))->all();
        $seriesHarian = collect(['hadir', 'terlambat', 'sakit', 'izin', 'alpa'])->map(fn ($st) => [
            'name' => ucfirst($st),
            'data' => $tanggalList->map(fn ($t) => (int) $harian->where('tanggal', $t)->where('status', $st)->sum('total'))->all(),
        ])->all();

        // ── Grafik bulanan (sepanjang tahun ajaran) ──
        $bulanSiswa = DB::table('absensi_siswa as a')
            ->join('kelas as k', 'k.id', '=', 'a.kelas_id')
            ->where('k.tahun_ajaran_id', $ta->id)
            ->selectRaw("DATE_FORMAT(a.tanggal, '%Y-%m') as bulan, sum(a.status in ('hadir','terlambat')) as hadir, count(*) as total")
            ->groupBy('bulan')
            ->get()->keyBy('bulan');

        $bulanGuru = DB::table('absensi_guru as g')
            ->where('g.sekolah_id', Tenant::id())
            ->whereBetween('g.tanggal', [$ta->tanggal_mulai->toDateString(), $ta->tanggal_selesai->toDateString()])
            ->selectRaw("DATE_FORMAT(g.tanggal, '%Y-%m') as bulan, sum(g.status in ('hadir','terlambat','dinas_luar')) as hadir, count(*) as total")
            ->groupBy('bulan')
            ->get()->keyBy('bulan');

        $labelBulan = [];
        $persenSiswaBulan = [];
        $persenGuruBulan = [];
        foreach ($bulanTersedia as $kunci => $nama) {
            $labelBulan[] = Carbon::parse($kunci.'-01')->translatedFormat('M Y');
            $s = $bulanSiswa->get($kunci);
            $g = $bulanGuru->get($kunci);
            $persenSiswaBulan[] = $s ? $pct((int) $s->hadir, (int) $s->total) : null;
            $persenGuruBulan[] = $g ? $pct((int) $g->hadir, (int) $g->total) : null;
        }

        // ── Ringkasan per kelas (bulan terpilih) ──
        $rekapKelas = DB::table('absensi_siswa as a')
            ->join('kelas as k', 'k.id', '=', 'a.kelas_id')
            ->where('k.tahun_ajaran_id', $ta->id)
            ->whereBetween('a.tanggal', [$awalStr, $akhirStr])
            ->selectRaw('a.kelas_id, a.status, count(*) as total')
            ->groupBy('a.kelas_id', 'a.status')
            ->get()->groupBy('kelas_id');

        $kelasRows = Kelas::where('tahun_ajaran_id', $ta->id)
            ->with('waliKelas.user:id,name')
            ->withCount(['siswa as aktif_count' => fn ($q) => $q->where('siswa.is_aktif', true)])
            ->orderBy('tingkat')->orderBy('nama')->get()
            ->map(function ($k) use ($rekapKelas, $pct) {
                $hit = $rekapKelas->get($k->id, collect())->pluck('total', 'status');
                $total = (int) $hit->sum();

                return [
                    'nama' => $k->nama,
                    'wali' => $k->waliKelas?->user?->name,
                    'siswa' => $k->aktif_count,
                    'persen' => $pct((int) ($hit['hadir'] ?? 0) + (int) ($hit['terlambat'] ?? 0), $total),
                    'terlambat' => (int) ($hit['terlambat'] ?? 0),
                    'sakit' => (int) ($hit['sakit'] ?? 0),
                    'izin' => (int) ($hit['izin'] ?? 0),
                    'alpa' => (int) ($hit['alpa'] ?? 0),
                ];
            });

        // ── Perlu perhatian ──
        $siswaMasalah = DB::table('absensi_siswa as a')
            ->join('siswa as s', 's.id', '=', 'a.siswa_id')
            ->join('kelas as k', 'k.id', '=', 'a.kelas_id')
            ->where('k.tahun_ajaran_id', $ta->id)
            ->where('s.is_aktif', true)
            ->whereBetween('a.tanggal', [today()->subDays(30)->toDateString(), $hariIni])
            ->selectRaw("s.id, s.nama, k.nama as kelas, sum(a.status = 'alpa') as alpa, sum(a.status = 'terlambat') as terlambat, sum(a.status in ('sakit','izin')) as sakit_izin")
            ->groupBy('s.id', 's.nama', 'k.nama')
            ->havingRaw("sum(a.status = 'alpa') >= ? or sum(a.status = 'terlambat') >= ?", [self::ALPA_SISWA, self::TERLAMBAT_SISWA])
            ->orderByDesc('alpa')->orderByDesc('terlambat')
            ->limit(10)->get();

        $guruMasalah = DB::table('absensi_guru as g')
            ->join('guru as gr', 'gr.id', '=', 'g.guru_id')
            ->join('users as u', 'u.id', '=', 'gr.user_id')
            ->where('g.sekolah_id', Tenant::id())
            ->whereBetween('g.tanggal', [$awalStr, $akhirStr])
            ->selectRaw("gr.id, u.name, sum(g.status = 'terlambat') as terlambat, sum(g.status = 'tanpa_keterangan') as tanpa_ket")
            ->groupBy('gr.id', 'u.name')
            ->havingRaw("sum(g.status = 'terlambat') >= ? or sum(g.status = 'tanpa_keterangan') >= 1", [self::TERLAMBAT_GURU])
            ->orderByDesc('tanpa_ket')->orderByDesc('terlambat')
            ->limit(10)->get();

        return view('dashboard-kepsek', [
            'title' => 'Dashboard',
            'breadcumd' => 'Dashboard',
            'ta' => $ta,
            'bulan' => $bulan,
            'bulanTersedia' => $bulanTersedia,
            'totalSiswa' => $totalSiswa,
            'siswaHariIni' => $siswaHariIni,
            'totalGuru' => $totalGuru,
            'guruHariIni' => $guruHariIni,
            'kelasBelum' => $kelasBelum,
            'izinGuruMenunggu' => PengajuanIzin::whereNotNull('guru_id')->where('status', 'menunggu')->count(),
            'izinSiswaMenunggu' => PengajuanIzin::whereNotNull('siswa_id')->where('status', 'menunggu')->count(),
            'labelHarian' => $labelHarian,
            'seriesHarian' => $seriesHarian,
            'labelBulan' => $labelBulan,
            'persenSiswaBulan' => $persenSiswaBulan,
            'persenGuruBulan' => $persenGuruBulan,
            'kelasRows' => $kelasRows,
            'siswaMasalah' => $siswaMasalah,
            'guruMasalah' => $guruMasalah,
            'batas' => [
                'alpa' => self::ALPA_SISWA,
                'terlambat' => self::TERLAMBAT_SISWA,
                'terlambat_guru' => self::TERLAMBAT_GURU,
            ],
        ]);
    }
}