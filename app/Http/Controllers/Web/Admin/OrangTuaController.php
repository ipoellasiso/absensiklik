<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrangTuaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $items = OrangTua::query()
            ->join('users', 'users.id', '=', 'orang_tua.user_id')
            ->select('orang_tua.*')
            ->with('user')
            ->withCount('anak')
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('users.name', 'like', "%{$q}%")
                ->orWhere('users.username', 'like', "%{$q}%")
                ->orWhere('orang_tua.no_wa', 'like', "%{$q}%")))
            ->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();

        $items->setCollection($items->getCollection()->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->user->name,
            'username' => $o->user->username,
            'email' => $o->user->email,
            'no_hp' => $o->user->no_hp,
            'is_active' => $o->user->is_active,
            'no_wa' => $o->no_wa,
            'alamat' => $o->alamat,
            'anak' => $o->anak_count,
        ]));

        return view('admin.orang-tua', [
            'title' => 'Orang Tua',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Orang Tua',
            'items' => $items,
            'q' => $q,
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->validasi($request);

        DB::transaction(function () use ($d) {
            $user = User::create(Arr::only($d, ['name', 'username', 'email', 'no_hp', 'password']) + ['role' => User::ROLE_ORANG_TUA]);
            OrangTua::create(['user_id' => $user->id, 'no_wa' => $d['no_wa'] ?? null, 'alamat' => $d['alamat'] ?? null]);
        });

        return back()->with('sukses', 'Akun orang tua ditambahkan.');
    }

    public function update(Request $request, OrangTua $orangTua)
    {
        $d = $this->validasi($request, $orangTua);

        DB::transaction(function () use ($d, $orangTua) {
            $user = $orangTua->user;
            $user->fill(Arr::only($d, ['name', 'username', 'email', 'no_hp']));
            if (! empty($d['password'])) {
                $user->password = $d['password'];
            }
            $user->save();
            $orangTua->update(['no_wa' => $d['no_wa'] ?? null, 'alamat' => $d['alamat'] ?? null]);
        });

        return back()->with('sukses', 'Data orang tua diperbarui.');
    }

    public function toggle(OrangTua $orangTua)
    {
        $user = $orangTua->user;
        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return back()->with('sukses', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    private function validasi(Request $request, ?OrangTua $o = null): array
    {
        $userId = $o?->user_id;

        return $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'no_hp' => 'nullable|string|max:20',
            'password' => [$o ? 'nullable' : 'required', 'string', 'min:8'],
            'no_wa' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500',
        ]);
    }
}