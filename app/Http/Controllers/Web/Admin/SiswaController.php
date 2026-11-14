<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\Tenant;
use App\Services\LanggananService;
use App\Services\FotoProfilService;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $ta = TahunAjaran::aktif();
        $q = trim((string) $request->query('q', ''));
        $kelasFilter = (string) $request->query('kelas_id', '');
        $status = in_array($request->query('status'), ['aktif', 'nonaktif', 'semua'], true) ? $request->query('status') : 'aktif';

        $items = Siswa::query()
            ->leftJoin('kelas_siswa as ks', fn ($j) => $j->on('ks.siswa_id', '=', 'siswa.id')->where('ks.tahun_ajaran_id', $ta?->id ?? 0))
            ->leftJoin('kelas as k', 'k.id', '=', 'ks.kelas_id')
            ->leftJoin('orang_tua as ot', 'ot.id', '=', 'siswa.orang_tua_id')
            ->leftJoin('users as u', 'u.id', '=', 'ot.user_id')
            ->select('siswa.*', 'ks.kelas_id as kelas_id', 'k.nama as kelas_nama', 'u.name as ortu_nama')
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('siswa.nama', 'like', "%{$q}%")
                ->orWhere('siswa.nisn', 'like', "%{$q}%")
                ->orWhere('siswa.nis', 'like', "%{$q}%")))
            ->when($kelasFilter === 'none', fn ($x) => $x->whereNull('ks.kelas_id'))
            ->when(ctype_digit($kelasFilter), fn ($x) => $x->where('ks.kelas_id', (int) $kelasFilter))
            ->when($status !== 'semua', fn ($x) => $x->where('siswa.is_aktif', $status === 'aktif'))
            ->orderBy('siswa.nama')
            ->paginate(25)
            ->withQueryString();

        $items->setCollection($items->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'nisn' => $s->nisn,
            'nis' => $s->nis,
            'nama' => $s->nama,
            'jenis_kelamin' => $s->jenis_kelamin,
            'tanggal_lahir' => $s->tanggal_lahir?->toDateString(),
            'orang_tua_id' => $s->orang_tua_id,
            'kelas_id' => $s->kelas_id,
            'kelas_nama' => $s->kelas_nama,
            'ortu_nama' => $s->ortu_nama,
            'is_aktif' => $s->is_aktif,
            'ada_foto' => filled($s->foto),
            'foto_v' => $s->updated_at?->timestamp,
        ]));

        return view('admin.siswa', [
            'title' => 'Siswa',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Siswa',
            'ta' => $ta,
            'items' => $items,
            'q' => $q,
            'kelasFilter' => $kelasFilter,
            'status' => $status,
            'kelasList' => $ta ? Kelas::where('tahun_ajaran_id', $ta->id)->orderBy('tingkat')->orderBy('nama')->get(['id', 'nama']) : collect(),
            'ortu' => OrangTua::with('user:id,name')->get()->map(fn ($o) => ['id' => $o->id, 'nama' => $o->user->name])->sortBy('nama')->values(),
        ]);
    }

    public function store(Request $request)
    {
        app(LanggananService::class)->pastikanKuotaSiswa(auth()->user()->sekolah);
        $ta = $this->tahunAktif();
        $d = $this->validasi($request, $ta);

        DB::transaction(function () use ($d, $ta, $request) {
            $siswa = Siswa::create(collect($d)->except(['kelas_id', 'foto', 'hapus_foto'])->all());
            $this->tempatkan($siswa, $d['kelas_id'] ?? null, $ta);
            app(FotoProfilService::class)->terapkan($siswa, 'siswa', $request);
        });

        return back()->with('sukses', 'Siswa ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $ta = $this->tahunAktif();
        $d = $this->validasi($request, $ta, $siswa);

        DB::transaction(function () use ($d, $siswa, $ta, $request) {
            $siswa->update(collect($d)->except(['kelas_id', 'foto', 'hapus_foto'])->all());
            $this->tempatkan($siswa, $d['kelas_id'] ?? null, $ta);
            app(FotoProfilService::class)->terapkan($siswa, 'siswa', $request);
        });

        return back()->with('sukses', 'Data siswa diperbarui.');
    }

    public function toggle(Siswa $siswa)
    {
        $siswa->update(['is_aktif' => ! $siswa->is_aktif]);

        return back()->with('sukses', $siswa->is_aktif ? 'Siswa diaktifkan.' : 'Siswa dinonaktifkan.');
    }

    public function qrBaru(Siswa $siswa)
    {
        if (! $siswa->is_aktif) {
            app(LanggananService::class)->pastikanKuotaSiswa(auth()->user()->sekolah);
        }
        
        $siswa->regenerateQrToken();

        return back()->with('sukses', "QR {$siswa->nama} diganti. Kartu lama tidak berlaku lagi, silakan cetak kartu baru.");
    }

    private function tahunAktif(): TahunAjaran
    {
        $ta = TahunAjaran::aktif();
        abort_unless($ta, 422, 'Belum ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu.');

        return $ta;
    }

    private function tempatkan(Siswa $siswa, $kelasId, TahunAjaran $ta): void
    {
        $row = DB::table('kelas_siswa')->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $ta->id);

        if (! $kelasId) {
            $row->delete();

            return;
        }

        if ($row->exists()) {
            $row->update(['kelas_id' => $kelasId, 'updated_at' => now()]);
        } else {
            DB::table('kelas_siswa')->insert([
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelasId,
                'tahun_ajaran_id' => $ta->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function foto(Siswa $siswa)
    {
        return app(FotoProfilService::class)->kirim($siswa->foto);
    }

    private function validasi(Request $request, TahunAjaran $ta, ?Siswa $siswa = null): array
    {
        return $request->validate([
            // 'nisn' => ['nullable', 'digits:10', Rule::unique('siswa', 'nisn')->ignore($siswa?->id)],
            'nisn' => ['nullable', 'digits:10', Rule::unique('siswa', 'nisn')->where('sekolah_id', Tenant::id())->ignore($siswa?->id)],
            'orang_tua_id' => ['nullable', Rule::exists('orang_tua', 'id')->where('sekolah_id', Tenant::id())],
            'nis' => 'nullable|string|max:20',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tanggal_lahir' => 'nullable|date_format:Y-m-d|before:today',
            // 'orang_tua_id' => 'nullable|exists:orang_tua,id',
            'kelas_id' => ['nullable', Rule::exists('kelas', 'id')->where('tahun_ajaran_id', $ta->id)],
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'hapus_foto' => 'nullable|boolean',
        ], [
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'nisn.unique' => 'NISN ini sudah terdaftar.',
            'foto.image' => 'File harus berupa foto.',
            'foto.mimes' => 'Foto harus berformat JPG atau PNG.',
            'foto.max' => 'Ukuran foto maksimal 4 MB.',
        ]);
    }
    
}