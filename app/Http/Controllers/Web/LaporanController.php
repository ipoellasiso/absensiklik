<?php

namespace App\Http\Controllers\Web;

use App\Exports\ViewExport;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiSiswaService;
use App\Services\RekapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Support\Tenant;

class LaporanController extends Controller
{
    public function __construct(private RekapService $rekap, private AbsensiSiswaService $akses)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $tahun = TahunAjaran::orderByDesc('nama')->get();
        $ta = $tahun->firstWhere('id', (int) $request->query('ta'))
            ?? $tahun->firstWhere('is_aktif', true)
            ?? $tahun->first();

        $kelas = collect();
        $semester = collect();
        $bulan = [];

        if ($ta) {
            $q = Kelas::where('tahun_ajaran_id', $ta->id)->orderBy('tingkat')->orderBy('nama');
            if (! $user->hasRole(User::ROLE_ADMIN, User::ROLE_KEPALA_SEKOLAH)) {
                $q->where('wali_kelas_id', $user->guru?->id);
            }
            $kelas = $q->get(['id', 'nama']);
            $semester = $ta->semester()->orderBy('tanggal_mulai')->get();

            $mulai = $ta->tanggal_mulai->copy()->startOfMonth();
            $akhir = $ta->tanggal_selesai->copy()->startOfMonth()->min(today()->startOfMonth());
            if ($akhir->gte($mulai)) {
                foreach (CarbonPeriod::create($mulai, '1 month', $akhir) as $b) {
                    $bulan[$b->format('Y-m')] = $b->translatedFormat('F Y');
                }
            }
        }

        return view('laporan.index', [
            'title' => 'Laporan',
            'breadcumd' => 'Laporan',
            'tahun' => $tahun,
            'ta' => $ta,
            'kelas' => $kelas,
            'semester' => $semester,
            'bulan' => $bulan,
            'bulanDefault' => array_key_last($bulan),
            'lihatGuru' => $user->hasRole(User::ROLE_ADMIN, User::ROLE_KEPALA_SEKOLAH),
        ]);
    }

    public function rekapBulanan(Request $request)
    {
        $d = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'bulan' => 'required|date_format:Y-m',
            'format' => 'required|in:excel,pdf',
        ]);

        $kelas = Kelas::with('waliKelas.user', 'tahunAjaran')->findOrFail($d['kelas_id']);
        $this->akses->pastikanAkses($request->user(), $kelas);

        $bulan = Carbon::parse($d['bulan'].'-01');
        $data = $this->rekap->matriksKelas($kelas, $bulan) + $this->kop() + [
            'judul' => 'REKAP KEHADIRAN SISWA',
            'info' => ['Kelas '.$kelas->nama.', Tahun Ajaran '.$kelas->tahunAjaran->nama, 'Bulan '.$bulan->translatedFormat('F Y')],
            'waliKelas' => $kelas->waliKelas?->user?->name,
            'waliNip' => $kelas->waliKelas?->nip,
        ];

        return $this->keluarkan($d['format'], 'laporan.rekap-bulanan', $data,
            Str::slug("rekap-absensi-kelas-{$kelas->nama}-{$d['bulan']}"), 'landscape');
    }

    public function rekapSemester(Request $request)
    {
        $d = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'semester_id' => 'required|exists:semester,id',
            'format' => 'nullable|in:html,excel,pdf',
        ]);

        $kelas = Kelas::with('waliKelas.user', 'tahunAjaran')->findOrFail($d['kelas_id']);
        $semester = Semester::findOrFail($d['semester_id']);
        abort_unless($semester->tahun_ajaran_id === $kelas->tahun_ajaran_id, 422, 'Semester tidak sesuai dengan tahun ajaran kelas.');
        $this->akses->pastikanAkses($request->user(), $kelas);

        $hitung = $this->rekap->hitungSemesterKelas($kelas, $semester);
        $format = $d['format'] ?? 'html';

        $umum = [
            'kelas' => $kelas,
            'semester' => $semester,
            'rows' => $hitung['rows'],
            'hariEfektif' => $hitung['hari_efektif'],
            'final' => $hitung['final'],
        ];

        if ($format === 'html') {
            return view('laporan.semester-preview', $umum + [
                'title' => 'Rekap Semester',
                'breadcumd' => 'Laporan',
                'breadcumd1' => 'Rekap semester',
            ]);
        }

        $data = $umum + $this->kop() + [
            'judul' => 'REKAP KEHADIRAN SISWA PER SEMESTER',
            'info' => [
                'Kelas '.$kelas->nama.', Tahun Ajaran '.$kelas->tahunAjaran->nama.', Semester '.ucfirst($semester->nama),
                'Hari efektif: '.$hitung['hari_efektif'].' hari ('.($hitung['final'] ? 'data final' : 'data sementara').')',
            ],
            'waliKelas' => $kelas->waliKelas?->user?->name,
            'waliNip' => $kelas->waliKelas?->nip,
        ];

        return $this->keluarkan($format, 'laporan.rekap-semester', $data,
            Str::slug("rekap-semester-kelas-{$kelas->nama}-{$semester->nama}-{$kelas->tahunAjaran->nama}"), 'portrait');
    }

    public function rapor(Request $request, Siswa $siswa)
    {
        $d = $request->validate(['semester_id' => 'required|exists:semester,id']);
        $semester = Semester::with('tahunAjaran')->findOrFail($d['semester_id']);

        $ks = DB::table('kelas_siswa')
            ->where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $semester->tahun_ajaran_id)
            ->first();
        abort_unless($ks, 404, 'Siswa tidak terdaftar di kelas pada tahun ajaran ini.');

        $kelas = Kelas::with('waliKelas.user')->findOrFail($ks->kelas_id);
        $this->akses->pastikanAkses($request->user(), $kelas);

        $hitung = $this->rekap->hitungSemesterKelas($kelas, $semester);
        $row = $hitung['rows']->firstWhere('siswa_id', $siswa->id);

        $data = $this->kop() + [
            'siswa' => $siswa,
            'kelas' => $kelas,
            'semester' => $semester,
            'row' => $row,
            'waliKelas' => $kelas->waliKelas?->user?->name,
            'waliNip' => $kelas->waliKelas?->nip,
            'untukExcel' => false,
        ];

        return Pdf::loadView('laporan.rapor', $data)
            ->setPaper('a4', 'portrait')
            ->stream(Str::slug("kehadiran-{$siswa->nama}-{$semester->nama}").'.pdf');
    }

    public function guruBulanan(Request $request)
    {
        $d = $request->validate([
            'bulan' => 'required|date_format:Y-m',
            'format' => 'required|in:excel,pdf',
        ]);

        $bulan = Carbon::parse($d['bulan'].'-01');
        $data = $this->rekap->matriksGuru($bulan) + $this->kop() + [
            'judul' => 'REKAP KEHADIRAN GURU DAN TENAGA KEPENDIDIKAN',
            'info' => ['Bulan '.$bulan->translatedFormat('F Y')],
        ];

        return $this->keluarkan($d['format'], 'laporan.guru-bulanan', $data,
            Str::slug("rekap-kehadiran-guru-{$d['bulan']}"), 'landscape');
    }

    private function keluarkan(string $format, string $view, array $data, string $nama, string $orientasi)
    {
        if ($format === 'excel') {
            return Excel::download(new ViewExport($view, $data + ['untukExcel' => true], $nama), $nama.'.xlsx');
        }

        return Pdf::loadView($view, $data + ['untukExcel' => false])
            ->setPaper('a4', $orientasi)
            ->stream($nama.'.pdf');
    }

    /** Data kop dan penandatangan yang dipakai semua laporan. */
    private function kop(): array
    {
        $kepsek = User::where('role', User::ROLE_KEPALA_SEKOLAH)
            ->where('sekolah_id', Tenant::id())
            ->where('is_active', true)
            ->with('guru')
            ->first();

        return [
            'sekolah' => Pengaturan::first()?->nama_sekolah ?? 'Sekolah Dasar',
            'kepsek' => $kepsek?->name ?? '..............................',
            'kepsekNip' => $kepsek?->guru?->nip,
            'dicetak' => now()->translatedFormat('d F Y'),
        ];
    }
}