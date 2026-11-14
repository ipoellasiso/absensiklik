<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiSiswaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsensiSiswaController extends Controller
{
    public function __construct(private AbsensiSiswaService $service)
    {
    }

    public function kelasSaya(Request $request)
    {
        $user = $request->user();
        $q = Kelas::where('tahun_ajaran_id', TahunAjaran::aktif()?->id);

        if (! $user->hasRole(User::ROLE_ADMIN)) {
            abort_unless($user->guru, 403, 'Akun ini tidak terhubung ke data guru.');
            $q->where('wali_kelas_id', $user->guru->id);
        }

        return response()->json(['data' => $q->orderBy('tingkat')->orderBy('nama')->get(['id', 'nama', 'tingkat'])]);
    }

    public function siswa(Request $request, Kelas $kelas)
    {
        $request->validate(['tanggal' => 'nullable|date_format:Y-m-d']);
        $tanggal = Carbon::parse($request->query('tanggal', today()->toDateString()))->startOfDay();

        $this->service->pastikanAkses($request->user(), $kelas);

        $siswa = $kelas->siswa()
            ->where('siswa.is_aktif', true)
            ->orderBy('siswa.nama')
            ->get(['siswa.id', 'siswa.nama', 'siswa.jenis_kelamin']);

        $absensi = AbsensiSiswa::whereIn('siswa_id', $siswa->pluck('id'))
            ->where('tanggal', $tanggal->toDateString())
            ->get()
            ->keyBy('siswa_id');

        return response()->json([
            'tanggal' => $tanggal->toDateString(),
            'data' => $siswa->map(fn ($s) => [
                'id' => $s->id,
                'nama' => $s->nama,
                'jenis_kelamin' => $s->jenis_kelamin,
                'status' => $absensi->get($s->id)?->status,   // null = belum diabsen, klien default "hadir"
                'sumber' => $absensi->get($s->id)?->sumber,
                'catatan' => $absensi->get($s->id)?->catatan,
            ]),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'tanggal' => 'required|date_format:Y-m-d',
            'data' => 'required|array|min:1',
            'data.*.siswa_id' => 'required|integer|distinct|exists:siswa,id',
            'data.*.status' => ['required', Rule::in([
                AbsensiSiswa::HADIR, AbsensiSiswa::SAKIT, AbsensiSiswa::IZIN,
                AbsensiSiswa::ALPA, AbsensiSiswa::TERLAMBAT,
            ])],
            'data.*.catatan' => 'nullable|string|max:255',
        ]);

        $kelas = Kelas::with('waliKelas')->findOrFail($data['kelas_id']);
        $hasil = $this->service->simpanBulk(
            $request->user(),
            $kelas,
            Carbon::createFromFormat('Y-m-d', $data['tanggal'])->startOfDay(),
            $data['data']
        );

        return response()->json(['message' => 'Absensi tersimpan.', 'data' => $hasil]);
    }

    public function scan(Request $request)
    {
        $data = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'qr_token' => 'required|string|max:64',
        ]);

        $kelas = Kelas::with('waliKelas')->findOrFail($data['kelas_id']);
        $hasil = $this->service->scan($request->user(), $kelas, $data['qr_token']);

        return response()->json($hasil, $hasil['baru'] ? 201 : 200);
    }
}
