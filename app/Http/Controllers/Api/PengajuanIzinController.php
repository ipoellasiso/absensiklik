<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanIzin;
use App\Services\PengajuanIzinService;
use Illuminate\Http\Request;

class PengajuanIzinController extends Controller
{
    public function __construct(private PengajuanIzinService $service)
    {
    }

    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:menunggu,disetujui,ditolak']);

        $q = $this->service->queryUntuk($request->user());
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        return response()->json($q->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => 'nullable|integer|exists:siswa,id',
            'jenis' => 'required|in:sakit,izin,dinas_luar',
            'tanggal_mulai' => 'required|date_format:Y-m-d',
            'tanggal_selesai' => 'required|date_format:Y-m-d|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:500',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $pengajuan = $this->service->ajukan($request->user(), $data, $request->file('lampiran'));

        return response()->json([
            'message' => $pengajuan->status === PengajuanIzin::STATUS_DISETUJUI
                ? 'Pengajuan dicatat dan langsung disetujui.'
                : 'Pengajuan terkirim, menunggu persetujuan.',
            'data' => $pengajuan,
        ], 201);
    }

    public function setujui(Request $request, PengajuanIzin $pengajuan)
    {
        $this->service->setujui($request->user(), $pengajuan);

        return response()->json(['message' => 'Pengajuan disetujui.', 'data' => $pengajuan->fresh()]);
    }

    public function tolak(Request $request, PengajuanIzin $pengajuan)
    {
        $data = $request->validate(['catatan_penolakan' => 'required|string|max:255']);
        $this->service->tolak($request->user(), $pengajuan, $data['catatan_penolakan']);

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $pengajuan->fresh()]);
    }
}
