<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiSiswaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AbsensiController extends Controller
{
    public function __construct(private AbsensiSiswaService $service)
    {
    }

    public function index(Request $request)
    {
        $request->validate(['tanggal' => 'nullable|date_format:Y-m-d', 'kelas_id' => 'nullable|integer']);

        $kelasList = $this->kelasUser($request->user());
        $kelas = $kelasList->firstWhere('id', (int) $request->query('kelas_id')) ?? $kelasList->first();
        $tanggal = Carbon::parse($request->query('tanggal', today()->toDateString()))->startOfDay();

        $siswa = collect();
        $absensi = collect();
        if ($kelas) {
            $siswa = $kelas->siswa()->where('siswa.is_aktif', true)->orderBy('siswa.nama')
                ->get(['siswa.id', 'siswa.nama', 'siswa.jenis_kelamin']);
            $absensi = AbsensiSiswa::whereIn('siswa_id', $siswa->pluck('id'))
                ->where('tanggal', $tanggal->toDateString())->get()->keyBy('siswa_id');
        }

        return view('absensi.index', [
            'title' => 'Absensi Siswa',
            'breadcumd' => 'Absensi Siswa',
            'kelasList' => $kelasList,
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'siswa' => $siswa,
            'absensi' => $absensi,
            'hariIni' => $tanggal->isToday(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'tanggal' => 'required|date_format:Y-m-d',
            'status' => 'required|array|min:1',
            'status.*' => ['required', Rule::in(['hadir', 'sakit', 'izin', 'alpa', 'terlambat'])],
            'catatan' => 'nullable|array',
            'catatan.*' => 'nullable|string|max:255',
        ]);

        $items = collect($data['status'])->map(fn ($st, $id) => [
            'siswa_id' => (int) $id,
            'status' => $st,
            'catatan' => $data['catatan'][$id] ?? null,
        ])->values()->all();

        try {
            $hasil = $this->service->simpanBulk(
                $request->user(),
                Kelas::with('waliKelas')->findOrFail($data['kelas_id']),
                Carbon::createFromFormat('Y-m-d', $data['tanggal'])->startOfDay(),
                $items
            );
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->withErrors(['msg' => $e->getMessage()]);
        }

        return redirect()
            ->route('absensi.index', ['kelas_id' => $data['kelas_id'], 'tanggal' => $data['tanggal']])
            ->with('sukses', "Absensi tersimpan ({$hasil['tersimpan']} siswa, {$hasil['berubah']} status berubah).");
    }

    public function scan(Request $request)
    {
        $data = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'qr_token' => 'required|string|max:64',
        ]);

        $tujuan = redirect()->route('absensi.index', ['kelas_id' => $data['kelas_id']]);

        try {
            $h = $this->service->scan(
                $request->user(),
                Kelas::with('waliKelas')->findOrFail($data['kelas_id']),
                trim($data['qr_token'])
            );
        } catch (HttpExceptionInterface $e) {
            return $tujuan->withErrors(['msg' => $e->getMessage()]);
        }

        return $h['baru']
            ? $tujuan->with('sukses', "{$h['siswa']['nama']} tercatat {$h['status']} pukul {$h['jam_masuk']}.")
            : $tujuan->with('info', "{$h['siswa']['nama']} sudah tercatat hari ini ({$h['status']}).");
    }

    private function kelasUser(User $user)
    {
        $q = Kelas::where('tahun_ajaran_id', TahunAjaran::aktif()?->id);
        if (! $user->hasRole(User::ROLE_ADMIN)) {
            $q->where('wali_kelas_id', $user->guru?->id);
        }

        return $q->orderBy('tingkat')->orderBy('nama')->get(['id', 'nama', 'tingkat']);
    }
}
