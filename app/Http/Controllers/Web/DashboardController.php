<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\PengajuanIzinService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, PengajuanIzinService $pengajuan)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return redirect()->route('platform.sekolah.index');
        }

        if ($user->hasRole('kepala_sekolah', 'admin')) {
            return app(DashboardKepsekController::class)->index($request);
        }

        if ($user->hasRole('orang_tua')) {
            $anak = $user->orangTua?->anak()->where('is_aktif', true)->get() ?? collect();
            $ringkasan = AbsensiSiswa::whereIn('siswa_id', $anak->pluck('id'))
                ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->selectRaw('siswa_id, status, count(*) as total')
                ->groupBy('siswa_id', 'status')
                ->get()
                ->groupBy('siswa_id')
                ->map(fn ($rows) => $rows->pluck('total', 'status'));

            return view('dashboard', ['title' => 'Dashboard', 'breadcumd' => 'Dashboard', 'mode' => 'orang_tua', 'anak' => $anak, 'ringkasan' => $ringkasan]);
        }

        $kelasQ = Kelas::where('tahun_ajaran_id', TahunAjaran::aktif()?->id);
        if ($user->hasRole('guru')) {
            $kelasQ->where('wali_kelas_id', $user->guru?->id);
        }

        $hari = today()->toDateString();
        $perKelas = $kelasQ->withCount('siswa')->orderBy('tingkat')->orderBy('nama')->get()->map(function ($k) use ($hari) {
            $hitung = AbsensiSiswa::whereIn('siswa_id', $k->siswa()->pluck('siswa.id'))
                ->where('tanggal', $hari)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return ['kelas' => $k, 'hitung' => $hitung];
        });

        $hitung = [];
        foreach ($perKelas as $row) {
            foreach ($row['hitung'] as $status => $total) {
                $hitung[$status] = ($hitung[$status] ?? 0) + $total;
            }
        }

        return view('dashboard', [
            'title' => 'Dashboard',
            'breadcumd' => 'Dashboard',
            'mode' => 'staf',
            'perKelas' => $perKelas,
            'hitung' => $hitung,
            'total' => $perKelas->sum(fn ($r) => $r['kelas']->siswa_count),
            'tercatat' => array_sum($hitung),
            'menunggu' => $pengajuan->queryUntuk($user)->where('status', 'menunggu')->count(),
        ]);
    }
}
