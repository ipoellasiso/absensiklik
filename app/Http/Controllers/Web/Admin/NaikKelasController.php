<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NaikKelasController extends Controller
{
    public function index(Request $request)
    {
        $tahun = TahunAjaran::orderByDesc('nama')->get();
        $asal = $tahun->firstWhere('id', (int) $request->query('asal'))
            ?? $tahun->firstWhere('is_aktif', true)
            ?? $tahun->first();
        $tujuan = $tahun->firstWhere('id', (int) $request->query('tujuan'))
            ?? ($asal ? $tahun->where('nama', '>', $asal->nama)->sortBy('nama')->first() : null);

        if ($tujuan && $asal && $tujuan->id === $asal->id) {
            $tujuan = null;
        }

        $kelasTujuan = $tujuan
            ? Kelas::where('tahun_ajaran_id', $tujuan->id)->orderBy('tingkat')->orderBy('nama')->get(['id', 'nama', 'tingkat'])
            : collect();

        $rows = ($asal && $tujuan)
            ? Kelas::where('tahun_ajaran_id', $asal->id)
                ->withCount(['siswa as aktif_count' => fn ($q) => $q->where('siswa.is_aktif', true)])
                ->orderBy('tingkat')->orderBy('nama')->get()
                ->map(function ($k) use ($kelasTujuan) {
                    $naik = (int) $k->tingkat + 1;
                    $saran = (int) $k->tingkat >= 6
                        ? 'lulus'
                        : ($kelasTujuan->first(fn ($t) => (int) $t->tingkat === $naik && substr($t->nama, -1) === substr($k->nama, -1))?->id
                            ?? $kelasTujuan->first(fn ($t) => (int) $t->tingkat === $naik)?->id
                            ?? '');

                    return ['id' => $k->id, 'nama' => $k->nama, 'tingkat' => $k->tingkat, 'jumlah' => $k->aktif_count, 'saran' => $saran];
                })
            : collect();

        return view('admin.naik-kelas', [
            'title' => 'Naik Kelas',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Naik Kelas',
            'tahun' => $tahun,
            'asal' => $asal,
            'tujuan' => $tujuan,
            'kelasTujuan' => $kelasTujuan,
            'rows' => $rows,
        ]);
    }

    public function proses(Request $request)
    {
        $d = $request->validate([
            'asal' => 'required|exists:tahun_ajaran,id',
            'tujuan' => 'required|exists:tahun_ajaran,id|different:asal',
            'map' => 'required|array',
            'map.*' => 'nullable|string',
        ]);

        $kelasAsal = Kelas::where('tahun_ajaran_id', $d['asal'])->pluck('id')->all();
        $kelasTujuan = Kelas::where('tahun_ajaran_id', $d['tujuan'])->pluck('id')->all();
        $pindah = $lulus = $lewat = 0;

        DB::transaction(function () use ($d, $kelasAsal, $kelasTujuan, &$pindah, &$lulus, &$lewat) {
            foreach ($d['map'] as $kelasId => $target) {
                if ($target === null || $target === '') {
                    continue;
                }

                abort_unless(in_array((int) $kelasId, $kelasAsal, true), 422, 'Kelas asal tidak valid.');

                $ids = DB::table('kelas_siswa as ks')
                    ->join('siswa as s', 's.id', '=', 'ks.siswa_id')
                    ->where('ks.kelas_id', $kelasId)
                    ->where('ks.tahun_ajaran_id', $d['asal'])
                    ->where('s.is_aktif', true)
                    ->pluck('ks.siswa_id');

                if ($target === 'lulus') {
                    Siswa::whereIn('id', $ids)->update(['is_aktif' => false]);
                    $lulus += $ids->count();

                    continue;
                }

                abort_unless(in_array((int) $target, $kelasTujuan, true), 422, 'Kelas tujuan tidak valid.');

                $sudah = DB::table('kelas_siswa')
                    ->where('tahun_ajaran_id', $d['tujuan'])
                    ->whereIn('siswa_id', $ids)
                    ->pluck('siswa_id');
                $baru = $ids->diff($sudah);
                $lewat += $ids->count() - $baru->count();

                $now = now();
                DB::table('kelas_siswa')->insert($baru->map(fn ($sid) => [
                    'siswa_id' => $sid,
                    'kelas_id' => (int) $target,
                    'tahun_ajaran_id' => (int) $d['tujuan'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all());
                $pindah += $baru->count();
            }
        });

        return redirect()
            ->route('admin.naik-kelas.index', ['asal' => $d['asal'], 'tujuan' => $d['tujuan']])
            ->with('sukses', "Selesai: {$pindah} siswa dipindahkan, {$lulus} siswa lulus, {$lewat} dilewati karena sudah punya kelas di tahun tujuan.");
    }
}