<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\FotoProfilService;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:60',
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => ['Username atau password salah.']]);
        }

        abort_unless($user->is_active, 403, 'Akun tidak aktif. Hubungi admin sekolah.');

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'user' => $user->only('id', 'name', 'username', 'role'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('guru', 'orangTua', 'sekolah');
        $l = $user->sekolah?->langganan();
        $data = $user->toArray();
        unset($data['guru']['foto']); // path file tidak perlu dikirim ke aplikasi

        return response()->json($data + [
            'foto_ada' => filled($user->guru?->foto),
            'foto_versi' => $user->guru?->updated_at?->timestamp,
            'langganan' => $l ? [
                'status' => $l['status'],
                'label' => $l['label'],
                'sampai' => $l['sampai']?->toDateString(),
                'sisa_hari' => $l['sisa_hari'],
                'boleh_menulis' => $l['boleh_menulis'],
            ] : null,
        ]);
    }

    public function foto(Request $request)
    {
        return app(FotoProfilService::class)->kirim($request->user()->guru?->foto);
    }
    
}
