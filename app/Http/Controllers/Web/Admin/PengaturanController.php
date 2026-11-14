<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function edit()
    {
        return view('admin.pengaturan', [
            'title' => 'Pengaturan Sekolah',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Pengaturan',
            'p' => Pengaturan::first() ?? new Pengaturan([
                'jam_masuk' => '07:30', 'jam_pulang' => '12:30', 'jam_tutup_absensi' => '14:00', 'batas_terlambat_menit' => 15,
                'radius_meter' => 100, 'batas_koreksi_hari' => 1, 'batas_pengajuan_mundur_hari' => 3, 'wajib_foto_guru' => true, 'simpan_foto_hari' => 90,
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'nama_sekolah' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:20',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
            'batas_terlambat_menit' => 'required|integer|between:0,120',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meter' => 'required|integer|between:10,2000',
            'batas_koreksi_hari' => 'required|integer|between:0,30',
            'batas_pengajuan_mundur_hari' => 'required|integer|between:0,30',
            'jam_tutup_absensi' => 'required|date_format:H:i|after:jam_pulang',
            'wajib_foto_guru' => 'nullable|boolean',
            'simpan_foto_hari' => 'required|integer|between:7,365',
        ]);

        // Checkbox yang tidak dicentang tidak dikirim browser, jadi harus dipaksa menjadi false.
        $d['wajib_foto_guru'] = $request->boolean('wajib_foto_guru');

        ($p = Pengaturan::first()) ? $p->update($d) : Pengaturan::create($d);

        return back()->with('sukses', 'Pengaturan disimpan.');
    }
}