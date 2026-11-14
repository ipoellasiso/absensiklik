<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnakController extends Controller
{
    public function index(Request $request)
    {
        $ortu = $request->user()->orangTua;
        abort_unless($ortu, 403, 'Akun ini tidak terhubung ke data orang tua.');

        $tahunId = TahunAjaran::aktif()?->id;

        $anak = $ortu->anak()
            ->where('is_aktif', true)
            ->with(['kelas' => fn ($q) => $q->where('kelas.tahun_ajaran_id', $tahunId)->select('kelas.id', 'kelas.nama')])
            ->get(['id', 'nama', 'jenis_kelamin']);

        return response()->json(['data' => $anak]);
    }

    public function absensi(Request $request, Siswa $siswa)
    {
        $ortu = $request->user()->orangTua;
        abort_unless($ortu && $siswa->orang_tua_id === $ortu->id, 403, 'Bukan data anak Anda.');

        $data = $request->validate(['bulan' => 'nullable|date_format:Y-m']);
        $awal = Carbon::parse(($data['bulan'] ?? now()->format('Y-m')).'-01')->startOfMonth();

        $rows = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()])
            ->orderBy('tanggal')
            ->get(['tanggal', 'status', 'jam_masuk', 'catatan']);

        return response()->json([
            'bulan' => $awal->format('Y-m'),
            'ringkasan' => $rows->countBy('status'),
            'data' => $rows,
        ]);
    }
}
