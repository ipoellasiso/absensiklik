<?php

namespace App\Http\Controllers\Web\Platform;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Pengaturan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Paket;

class SekolahController extends Controller
{
    public function index()
    {
        $siswa = Siswa::withoutGlobalScopes()->where('is_aktif', true)
            ->selectRaw('sekolah_id, count(*) as total')->groupBy('sekolah_id')->pluck('total', 'sekolah_id');
        $guru = Guru::withoutGlobalScopes()
            ->selectRaw('sekolah_id, count(*) as total')->groupBy('sekolah_id')->pluck('total', 'sekolah_id');
        $admin = User::where('role', User::ROLE_ADMIN)->orderBy('id')->get()->groupBy('sekolah_id');

        $rows = Sekolah::orderBy('nama')->get()->map(fn ($s) => [
            'id' => $s->id,
            'nama' => $s->nama,
            'npsn' => $s->npsn,
            'kode' => $s->kode,
            'is_aktif' => $s->is_aktif,
            'siswa' => (int) ($siswa[$s->id] ?? 0),
            'guru' => (int) ($guru[$s->id] ?? 0),
            'admin' => optional($admin->get($s->id)?->first())->username,
            'dibuat' => $s->created_at?->format('d/m/Y'),
            'paket_id' => $s->paket_id,
            'uji_coba_sampai' => $s->uji_coba_sampai?->toDateString(),
            'berlangganan_sampai' => $s->berlangganan_sampai?->toDateString(),
            'langganan' => $s->langganan(),
        ]);

        return view('platform.sekolah', [
            'title' => 'Sekolah',
            'breadcumd' => 'Platform',
            'breadcumd1' => 'Sekolah',
            'rows' => $rows,
            'paket' => Paket::orderBy('batas_siswa')->get(['id', 'nama', 'batas_siswa']),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'nama' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:20|unique:sekolah,npsn',
            'admin_nama' => 'required|string|max:255',
            'admin_username' => 'required|alpha_dash|max:50|unique:users,username',
            'admin_password' => 'required|string|min:8',
        ]);

        DB::transaction(function () use ($d) {
            $sekolah = Sekolah::create([
                'nama' => $d['nama'],
                'npsn' => $d['npsn'] ?? null,
                'kode' => $this->buatKode($d['nama']),
                'uji_coba_sampai' => today()->addDays((int) config('langganan.masa_uji_coba_hari', 30)),
            ]);

            User::create([
                'sekolah_id' => $sekolah->id,
                'name' => $d['admin_nama'],
                'username' => $d['admin_username'],
                'password' => $d['admin_password'],
                'role' => User::ROLE_ADMIN,
            ]);

            Tenant::sebagai($sekolah, fn () => Pengaturan::create([
                'nama_sekolah' => $sekolah->nama,
                'npsn' => $sekolah->npsn,
            ]));
        });

        return back()->with('sukses', 'Sekolah dan akun admin ditambahkan. Berikan username dan password ke admin sekolah.');
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        $d = $request->validate([
            'nama' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:20|unique:sekolah,npsn,'.$sekolah->id,
            'paket_id' => 'nullable|exists:paket,id',
            'uji_coba_sampai' => 'nullable|date_format:Y-m-d',
            'berlangganan_sampai' => 'nullable|date_format:Y-m-d',
        ]);

        $sekolah->update($d);

        return back()->with('sukses', 'Data sekolah diperbarui.');
    }

    public function toggle(Sekolah $sekolah)
    {
        $sekolah->update(['is_aktif' => ! $sekolah->is_aktif]);

        if (! $sekolah->is_aktif) {
            // cabut semua token API aplikasi HP milik sekolah ini
            User::where('sekolah_id', $sekolah->id)->get()->each(fn ($u) => $u->tokens()->delete());
        }

        return back()->with('sukses', $sekolah->is_aktif ? 'Sekolah diaktifkan.' : 'Sekolah dinonaktifkan. Semua pengguna sekolah ini tidak bisa masuk.');
    }

    public function resetPassword(Request $request, Sekolah $sekolah)
    {
        $d = $request->validate(['password' => 'required|string|min:8']);

        $admin = User::where('sekolah_id', $sekolah->id)->where('role', User::ROLE_ADMIN)->orderBy('id')->firstOrFail();
        $admin->update(['password' => $d['password']]);
        $admin->tokens()->delete();

        return back()->with('sukses', "Password admin ({$admin->username}) diganti.");
    }

    private function buatKode(string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'sekolah';
        $kode = $dasar;
        $i = 2;

        while (Sekolah::where('kode', $kode)->exists()) {
            $kode = $dasar.'-'.$i++;
        }

        return $kode;
    }
}