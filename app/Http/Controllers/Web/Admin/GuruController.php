<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\Tenant;
use App\Services\FotoProfilService;

class GuruController extends Controller
{
    public function index()
    {
        $rows = Guru::with('user')->get()
            ->sortBy(fn ($g) => $g->user->name)
            ->values()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->user->name,
                'username' => $g->user->username,
                'email' => $g->user->email,
                'no_hp' => $g->user->no_hp,
                'role' => $g->user->role,
                'is_active' => $g->user->is_active,
                'nip' => $g->nip,
                'jabatan' => $g->jabatan,
                'ada_foto' => filled($g->foto),
                'foto_v' => $g->updated_at?->timestamp,
            ]);

        return view('admin.guru', [
            'title' => 'Guru dan Staf',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Guru dan Staf',
            'rows' => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->validasi($request);

        DB::transaction(function () use ($d, $request) {
            $user = User::create(Arr::only($d, ['name', 'username', 'email', 'no_hp', 'role', 'password']));
            $guru = Guru::create(['user_id' => $user->id, 'nip' => $d['nip'] ?? null, 'jabatan' => $d['jabatan'] ?? null]);
            app(FotoProfilService::class)->terapkan($guru, 'guru', $request);
        });

        return back()->with('sukses', 'Akun guru ditambahkan.');
    }

    public function update(Request $request, Guru $guru)
    {
        $d = $this->validasi($request, $guru);

        // DB::transaction(function () use ($d, $guru) {
        DB::transaction(function () use ($d, $guru, $request) {
            $user = $guru->user;
            $user->fill(Arr::only($d, ['name', 'username', 'email', 'no_hp', 'role']));
            if (! empty($d['password'])) {
                $user->password = $d['password'];
            }
            $user->save();
            $guru->update(['nip' => $d['nip'] ?? null, 'jabatan' => $d['jabatan'] ?? null]);
            app(FotoProfilService::class)->terapkan($guru, 'guru', $request);
        });

        return back()->with('sukses', 'Data guru diperbarui.');
    }

    public function toggle(Guru $guru)
    {
        $user = $guru->user;

        if ($user->id === auth()->id()) {
            return back()->withErrors(['msg' => 'Anda tidak bisa menonaktifkan akun sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $user->tokens()->delete(); // cabut token API aplikasi mobile
        }

        return back()->with('sukses', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function foto(Guru $guru)
    {
        return app(FotoProfilService::class)->kirim($guru->foto);
    }

    private function validasi(Request $request, ?Guru $guru = null): array
    {
        $userId = $guru?->user_id;

        return $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'no_hp' => 'nullable|string|max:20',
            'role' => ['required', Rule::in([User::ROLE_GURU, User::ROLE_KEPALA_SEKOLAH])],
            'password' => [$guru ? 'nullable' : 'required', 'string', 'min:8'],
            // 'nip' => ['nullable', 'string', 'max:30', Rule::unique('guru', 'nip')->ignore($guru?->id)],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('guru', 'nip')->where('sekolah_id', Tenant::id())->ignore($guru?->id)],
            'jabatan' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'hapus_foto' => 'nullable|boolean',
        ]);
    }
}