<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\HariLibur;
use App\Models\Pengaturan;
use App\Services\AbsensiGuruService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiGuruController extends Controller
{
    private const KOLOM = ['id', 'tanggal', 'jam_masuk', 'jam_pulang', 'status', 'jarak_masuk_m', 'catatan'];

    public function __construct(private AbsensiGuruService $service)
    {
    }

    public function hariIni(Request $request)
    {
        $guru = $request->user()->guru;
        abort_unless($guru, 403, 'Akun ini tidak terhubung ke data guru.');

        $p = Pengaturan::current();
        $rec = AbsensiGuru::where('guru_id', $guru->id)->where('tanggal', today()->toDateString())->first();

        return response()->json([
            'tanggal' => today()->toDateString(),
            'hari_efektif' => HariLibur::hariEfektif(today()),
            'lokasi_diatur' => $p->latitude !== null && $p->longitude !== null,
            'sekolah' => $p->nama_sekolah,
            'jam_masuk' => substr($p->jam_masuk, 0, 5),
            'batas_terlambat_menit' => $p->batas_terlambat_menit,
            'radius_meter' => $p->radius_meter,
            'absensi' => $rec?->setVisible(self::KOLOM)->toArray(),
            'wajib_foto' => (bool) $p->wajib_foto_guru,
        ]);
    }

    public function masuk(Request $request)
    {
        $rec = $this->service->masuk($request->user(), $this->lokasi($request), $request->file('foto'));

        return response()->json([
            'message' => 'Absen masuk tercatat pukul '.substr($rec->jam_masuk, 0, 5)." ({$rec->status}).",
            'data' => $rec->setVisible(self::KOLOM),
        ], 201);
    }

    public function pulang(Request $request)
    {
        $rec = $this->service->pulang($request->user(), $this->lokasi($request), $request->file('foto'));

        return response()->json([
            'message' => 'Absen pulang tercatat pukul '.substr($rec->jam_pulang, 0, 5).'.',
            'data' => $rec->setVisible(self::KOLOM),
        ]);
    }

    public function riwayat(Request $request)
    {
        $guru = $request->user()->guru;
        abort_unless($guru, 403, 'Akun ini tidak terhubung ke data guru.');

        $d = $request->validate(['bulan' => 'nullable|date_format:Y-m']);
        $awal = Carbon::parse(($d['bulan'] ?? now()->format('Y-m')).'-01')->startOfMonth();

        $rows = AbsensiGuru::where('guru_id', $guru->id)
            ->whereBetween('tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()])
            ->orderBy('tanggal')
            ->get(self::KOLOM);

        return response()->json([
            'bulan' => $awal->format('Y-m'),
            'ringkasan' => $rows->countBy('status'),
            'data' => $rows,
        ]);
    }

    private function lokasi(Request $request): array
    {
        $wajib = Pengaturan::current()->wajib_foto_guru;

        return $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'mocked' => 'nullable|boolean',
            'akurasi' => 'nullable|numeric|min:0',
            'foto' => [$wajib ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'foto.required' => 'Foto wajah wajib diambil untuk absen.',
            'foto.image' => 'File harus berupa foto.',
            'foto.max' => 'Ukuran foto terlalu besar (maksimal 2 MB).',
        ]);
    }
}