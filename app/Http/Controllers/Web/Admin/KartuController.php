<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Tenant;

class KartuController extends Controller
{
    public function cetak(Request $request)
    {
        $request->validate(['kelas_id' => 'nullable|integer', 'siswa_id' => 'nullable|integer']);
        abort_unless($request->filled('kelas_id') || $request->filled('siswa_id'), 422, 'Pilih kelas atau siswa yang akan dicetak.');

        $ta = TahunAjaran::aktif();

        $q = DB::table('siswa')
            ->leftJoin('kelas_siswa as ks', fn ($j) => $j->on('ks.siswa_id', '=', 'siswa.id')->where('ks.tahun_ajaran_id', $ta?->id ?? 0))
            ->leftJoin('kelas as k', 'k.id', '=', 'ks.kelas_id')
            ->where('siswa.sekolah_id', Tenant::id())
            ->where('siswa.is_aktif', true)
            // ->select('siswa.id', 'siswa.nama', 'siswa.nisn', 'siswa.qr_token', 'k.nama as kelas')
            ->select('siswa.id', 'siswa.nama', 'siswa.nisn', 'siswa.qr_token', 'siswa.foto', 'siswa.updated_at', 'k.nama as kelas')
            ->orderBy('k.tingkat')->orderBy('k.nama')->orderBy('siswa.nama');

        if ($request->filled('siswa_id')) {
            $q->where('siswa.id', $request->integer('siswa_id'));
        } else {
            $q->where('ks.kelas_id', $request->integer('kelas_id'));
        }

        return view('admin.kartu', [
            'siswa' => $q->get(),
            'sekolah' => Pengaturan::first()?->nama_sekolah ?? 'Sekolah Dasar',
            'tahun' => $ta?->nama,
        ]);
    }
}