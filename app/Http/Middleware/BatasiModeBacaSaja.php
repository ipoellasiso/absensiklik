<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class BatasiModeBacaSaja
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Super admin, dan permintaan baca (GET, HEAD) selalu lolos: data tetap bisa dilihat dan laporan diunduh.
        if (! $user || $user->hasRole(User::ROLE_SUPER_ADMIN) || $request->isMethodSafe()) {
            return $next($request);
        }

        if ($user->sekolah?->bolehMenulis()) {
            return $next($request);
        }

        // Tetap boleh: keluar, serta halaman langganan (membuat tagihan dan mengunggah bukti).
        $nama = (string) $request->route()?->getName();
        if ($nama === 'logout' || str_starts_with($nama, 'langganan.') || $request->is('api/logout')) {
            return $next($request);
        }

        $pesan = 'Langganan sekolah telah berakhir. Data hanya bisa dilihat. Perpanjang langganan agar absensi bisa diisi kembali.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan, 'kode' => 'langganan_berakhir'], 402);
        }

        return back()->withErrors(['msg' => $pesan]);
    }
}