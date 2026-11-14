<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\RekapService;
use App\Support\Tenant; 

class TahunAjaranController extends Controller
{
    public function index()
    {
        return view('admin.tahun-ajaran', [
            'title' => 'Tahun Ajaran',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Tahun Ajaran',
            'items' => TahunAjaran::with('semester')->orderByDesc('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->validasi($request);

        DB::transaction(function () use ($d) {
            $ta = TahunAjaran::create([
                'nama' => $d['nama'],
                'tanggal_mulai' => $d['tanggal_mulai'],
                'tanggal_selesai' => $d['tanggal_selesai'],
            ]);
            $ta->semester()->createMany($this->dataSemester($d));
        });

        return back()->with('sukses', 'Tahun ajaran ditambahkan. Klik "Aktifkan" agar dipakai.');
    }

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $d = $this->validasi($request, $tahunAjaran);

        DB::transaction(function () use ($d, $tahunAjaran) {
            $tahunAjaran->update([
                'nama' => $d['nama'],
                'tanggal_mulai' => $d['tanggal_mulai'],
                'tanggal_selesai' => $d['tanggal_selesai'],
            ]);
            foreach ($this->dataSemester($d) as $s) {
                $tahunAjaran->semester()->where('nama', $s['nama'])->update([
                    'tanggal_mulai' => $s['tanggal_mulai'],
                    'tanggal_selesai' => $s['tanggal_selesai'],
                ]);
            }
        });

        return back()->with('sukses', 'Tahun ajaran diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        DB::transaction(function () use ($tahunAjaran) {
            TahunAjaran::query()->update(['is_aktif' => false]);
            Semester::query()->update(['is_aktif' => false]);
            $tahunAjaran->update(['is_aktif' => true]);

            $semester = $tahunAjaran->semester()
                ->whereDate('tanggal_mulai', '<=', today())
                ->whereDate('tanggal_selesai', '>=', today())
                ->first() ?? $tahunAjaran->semester()->orderBy('tanggal_mulai')->first();
            $semester?->update(['is_aktif' => true]);
        });

        return back()->with('sukses', "Tahun ajaran {$tahunAjaran->nama} sekarang aktif.");
    }

    public function aktifkanSemester(Semester $semester)
    {
        if (! $semester->tahunAjaran->is_aktif) {
            return back()->withErrors(['msg' => 'Aktifkan tahun ajarannya terlebih dahulu.']);
        }

        DB::transaction(function () use ($semester) {
            Semester::query()->update(['is_aktif' => false]);
            $semester->update(['is_aktif' => true]);
        });

        return back()->with('sukses', 'Semester '.$semester->nama.' sekarang aktif.');
    }

    public function kunciSemester(Semester $semester, RekapService $rekap)
    {
        $pesan = DB::transaction(function () use ($semester, $rekap) {
            $semester->update(['is_terkunci' => ! $semester->is_terkunci]);

            if ($semester->is_terkunci) {
                $n = $rekap->bekukanSemester($semester);

                return "Semester dikunci. Rekap kehadiran {$n} siswa dibekukan untuk rapor. Absensi dan pengajuan pada rentang ini tidak bisa diubah lagi.";
            }

            $rekap->hapusSnapshot($semester);

            return 'Kunci semester dibuka. Rekap beku dihapus dan akan dibuat ulang saat dikunci lagi.';
        });

        return back()->with('sukses', $pesan);
    }

    private function validasi(Request $request, ?TahunAjaran $ta = null): array
    {
        return $request->validate([
            // 'nama' => ['required', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('tahun_ajaran', 'nama')->ignore($ta?->id)],
            'nama' => ['required', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('tahun_ajaran', 'nama')->where('sekolah_id', Tenant::id())->ignore($ta?->id)],
            'tanggal_mulai' => 'required|date_format:Y-m-d',
            'selesai_ganjil' => 'required|date_format:Y-m-d|after:tanggal_mulai',
            'tanggal_selesai' => 'required|date_format:Y-m-d|after:selesai_ganjil',
        ], [
            'nama.regex' => 'Format nama tahun ajaran harus seperti 2026/2027.',
            'nama.unique' => 'Tahun ajaran ini sudah ada.',
        ]);
    }

    private function dataSemester(array $d): array
    {
        return [
            ['nama' => 'ganjil', 'tanggal_mulai' => $d['tanggal_mulai'], 'tanggal_selesai' => $d['selesai_ganjil']],
            ['nama' => 'genap', 'tanggal_mulai' => Carbon::parse($d['selesai_ganjil'])->addDay()->toDateString(), 'tanggal_selesai' => $d['tanggal_selesai']],
        ];
    }
}