<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\Tenant;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $tahun = TahunAjaran::orderByDesc('nama')->get();
        $dipilih = $tahun->firstWhere('id', (int) $request->query('ta'))
            ?? $tahun->firstWhere('is_aktif', true)
            ?? $tahun->first();

        $rows = $dipilih
            ? Kelas::where('tahun_ajaran_id', $dipilih->id)
                ->with('waliKelas.user')->withCount('siswa')
                ->orderBy('tingkat')->orderBy('nama')->get()
                ->map(fn ($k) => [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'tingkat' => $k->tingkat,
                    'wali_kelas_id' => $k->wali_kelas_id,
                    'wali' => $k->waliKelas?->user->name,
                    'jumlah' => $k->siswa_count,
                ])
            : collect();

        $guru = Guru::with('user')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($g) => $g->user->name)
            ->values()
            ->map(fn ($g) => ['id' => $g->id, 'nama' => $g->user->name]);

        return view('admin.kelas', [
            'title' => 'Kelas',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Kelas',
            'tahun' => $tahun,
            'dipilih' => $dipilih,
            'rows' => $rows,
            'guru' => $guru,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['tahun_ajaran_id' => 'required|exists:tahun_ajaran,id']);
        $d = $this->validasi($request, (int) $request->tahun_ajaran_id);

        Kelas::create($d + ['tahun_ajaran_id' => (int) $request->tahun_ajaran_id]);

        return back()->with('sukses', 'Kelas ditambahkan.');
    }

    public function update(Request $request, Kelas $kelas)
    {
        $kelas->update($this->validasi($request, $kelas->tahun_ajaran_id, $kelas));

        return back()->with('sukses', 'Kelas diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        $terpakai = DB::table('kelas_siswa')->where('kelas_id', $kelas->id)->exists()
            || AbsensiSiswa::where('kelas_id', $kelas->id)->exists();

        if ($terpakai) {
            return back()->withErrors(['msg' => 'Kelas tidak bisa dihapus karena sudah berisi siswa atau data absensi.']);
        }

        $kelas->delete();

        return back()->with('sukses', 'Kelas dihapus.');
    }

    private function validasi(Request $request, int $tahunId, ?Kelas $kelas = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:20',
                Rule::unique('kelas', 'nama')->where('tahun_ajaran_id', $tahunId)->ignore($kelas?->id)],
            'tingkat' => 'required|integer|between:1,6',
            // 'wali_kelas_id' => ['nullable', 'exists:guru,id',
                // Rule::unique('kelas', 'wali_kelas_id')->where('tahun_ajaran_id', $tahunId)->ignore($kelas?->id)],
            'wali_kelas_id' => ['nullable', Rule::exists('guru', 'id')->where('sekolah_id', Tenant::id()),
                Rule::unique('kelas', 'wali_kelas_id')->where('tahun_ajaran_id', $tahunId)->ignore($kelas?->id)],
        ], [
            'nama.unique' => 'Nama kelas sudah dipakai pada tahun ajaran ini.',
            'wali_kelas_id.unique' => 'Guru ini sudah menjadi wali kelas lain pada tahun ajaran ini.',
        ]);
    }
}