<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PastikanSekolahAktif
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return $next($request);
        }

        $sekolah = $user->sekolah;

        if (!$sekolah || !$sekolah->is_aktif) {
            $pesan = 'Sekolah Anda tidak aktif. Hubungi pengelola layanan.';

            if ($request->expectsJson()) {
                abort(403, $pesan);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['login' => $pesan]);
        }

        return Tenant::sebagai(
            (int) $user->sekolah_id,
            fn () => $next($request)
        );
    }
}
