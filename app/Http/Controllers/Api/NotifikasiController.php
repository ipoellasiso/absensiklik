<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $q = Notifikasi::where('user_id', $request->user()->id);

        return response()->json([
            'belum_dibaca' => (clone $q)->whereNull('dibaca_pada')->count(),
            'data' => $q->latest()->limit(50)->get(['id', 'tipe', 'judul', 'isi', 'data', 'dibaca_pada', 'created_at']),
        ]);
    }

    public function baca(Request $request, Notifikasi $notifikasi)
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403, 'Bukan notifikasi Anda.');

        $notifikasi->update(['dibaca_pada' => $notifikasi->dibaca_pada ?? now()]);

        return response()->json(['message' => 'Ditandai sudah dibaca.']);
    }

    public function bacaSemua(Request $request)
    {
        Notifikasi::where('user_id', $request->user()->id)->whereNull('dibaca_pada')->update(['dibaca_pada' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}