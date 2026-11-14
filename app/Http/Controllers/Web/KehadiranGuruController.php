<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Services\AbsensiSiswaService;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Support\Tenant; 

class KehadiranGuruController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['tanggal' => 'nullable|date_format:Y-m-d']);
        $tanggal = Carbon::parse($request->query('tanggal', today()->toDateString()))->startOfDay();

        $absen = AbsensiGuru::where('tanggal', $tanggal->toDateString())->get()->keyBy('guru_id');

        $rows = Guru::with('user')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($g) => $g->user->name)
            ->values()
            ->map(function ($g) use ($absen) {
                $a = $absen->get($g->id);

                return [
                    'guru_id' => $g->id,
                    'nama' => $g->user->name,
                    'jabatan' => $g->jabatan,
                    'status' => $a?->status,
                    'jam_masuk' => $a?->jam_masuk ? substr($a->jam_masuk, 0, 5) : '',
                    'jam_pulang' => $a?->jam_pulang ? substr($a->jam_pulang, 0, 5) : '',
                    'jarak' => $a?->jarak_masuk_m,
                    'sumber' => $a?->sumber,
                    'catatan' => $a?->catatan,
                    'absensi_id' => $a?->id,
                    'foto_masuk' => (bool) $a?->foto_masuk,
                    'foto_pulang' => (bool) $a?->foto_pulang,
                ];
            });

        $hitung = $rows->whereNotNull('status')->countBy('status');

        return view('kehadiran-guru', [
            'title' => 'Kehadiran Guru',
            'breadcumd' => 'Kehadiran Guru',
            'tanggal' => $tanggal,
            'rows' => $rows,
            'hitung' => $hitung,
            'belum' => $rows->whereNull('status')->count(),
        ]);
    }

    public function simpan(Request $request)
    {
        $d = $request->validate([
            // 'guru_id' => 'required|exists:guru,id',
            'guru_id' => ['required', Rule::exists('guru', 'id')->where('sekolah_id', Tenant::id())],
            'tanggal' => 'required|date_format:Y-m-d|before_or_equal:today',
            'status' => ['required', Rule::in(AbsensiGuru::STATUS)],
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => ['nullable', 'date_format:H:i', Rule::when($request->filled('jam_masuk'), ['after:jam_masuk'])],
            'catatan' => 'nullable|string|max:255',
        ]);

        abort_if(
            app(AbsensiSiswaService::class)->semesterTerkunci(Carbon::parse($d['tanggal'])),
            422,
            'Semester sudah dikunci. Koreksi tidak bisa dilakukan.'
        );

        $jam = fn ($v) => $v ? $v.':00' : null;
        $old = AbsensiGuru::where('guru_id', $d['guru_id'])->where('tanggal', $d['tanggal'])->first();

        $rec = AbsensiGuru::updateOrCreate(
            ['guru_id' => $d['guru_id'], 'tanggal' => $d['tanggal']],
            [
                'status' => $d['status'],
                'jam_masuk' => $jam($d['jam_masuk'] ?? null),
                'jam_pulang' => $jam($d['jam_pulang'] ?? null),
                'catatan' => $d['catatan'] ?? null,
                'sumber' => 'manual',
                'pengajuan_izin_id' => null,
                'dicatat_oleh' => $request->user()->id,
            ]
        );

        if (! $old || $old->status !== $rec->status || $old->jam_masuk !== $rec->jam_masuk) {
            AuditLogger::log('koreksi_kehadiran_guru', 'absensi_guru', $rec->id,
                $old ? ['status' => $old->status, 'jam_masuk' => $old->jam_masuk, 'jam_pulang' => $old->jam_pulang] : null,
                ['status' => $rec->status, 'jam_masuk' => $rec->jam_masuk, 'jam_pulang' => $rec->jam_pulang]);
        }

        return back()->with('sukses', 'Kehadiran guru disimpan.');
    }

    public function foto(AbsensiGuru $absensiGuru, string $jenis)
    {
        $path = $jenis === 'masuk' ? $absensiGuru->foto_masuk : $absensiGuru->foto_pulang;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }
    
}