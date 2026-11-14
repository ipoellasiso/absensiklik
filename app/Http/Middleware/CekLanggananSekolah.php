<?php
// Simpan di: app/Http/Middleware/CekLanggananSekolah.php

namespace App\Http\Middleware;

use App\Models\Sekolah;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CekLanggananSekolah
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Super admin / user tanpa sekolah: lewati
        if (!$user || !$user->sekolah_id) {
            return $next($request);
        }

        $sekolah = Sekolah::find($user->sekolah_id);

        if (!$sekolah) {
            return $next($request);
        }

        // Dipakai banner "sisa uji coba" di Blade: $langgananSekolah
        View::share('langgananSekolah', $sekolah);

        $request->attributes->set('status_langganan', $sekolah->statusLangganan());

        if ($sekolah->bisaTulis()) {
            return $next($request);
        }

        // Mode baca-saja: izinkan GET/HEAD/OPTIONS
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // Pengecualian: logout dan halaman langganan/pembayaran
        if ($request->is('logout', 'api/logout', 'langganan*', 'api/langganan*')) {
            return $next($request);
        }

        $pesan = 'Masa uji coba/langganan sekolah telah berakhir. Data tetap bisa dilihat, perpanjang langganan untuk mengubah data.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code'    => 'LANGGANAN_KADALUARSA',
                'message' => $pesan,
            ], 403);
        }

        return back()->with('error', $pesan);
    }
}