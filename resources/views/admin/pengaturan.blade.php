@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="card border-0 shadow-sm" style="max-width:760px;">
    <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Pengaturan sekolah</h6></div>
    <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="card-body px-4">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Nama sekolah</label>
                <input name="nama_sekolah" value="{{ old('nama_sekolah', $p->nama_sekolah) }}" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">NPSN</label>
                <input name="npsn" value="{{ old('npsn', $p->npsn) }}" class="form-control">
            </div>
        </div>

        <h6 class="fw-bold mt-2">Jam sekolah</h6>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Jam masuk</label>
                <input type="time" name="jam_masuk" value="{{ old('jam_masuk', substr($p->jam_masuk ?? '07:30', 0, 5)) }}" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Jam pulang</label>
                <input type="time" name="jam_pulang" value="{{ old('jam_pulang', substr($p->jam_pulang ?? '12:30', 0, 5)) }}" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Batas terlambat (menit)</label>
                <input type="number" name="batas_terlambat_menit" value="{{ old('batas_terlambat_menit', $p->batas_terlambat_menit) }}" class="form-control" min="0" max="120" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Jam tutup absensi</label>
                <input type="time" name="jam_tutup_absensi" value="{{ old('jam_tutup_absensi', substr($p->jam_tutup_absensi ?? '14:00', 0, 5)) }}" class="form-control" required>
                <div class="form-text">Setelah jam ini, siswa yang belum tercatat otomatis alpa.</div>
            </div>
        </div>

        <h6 class="fw-bold mt-2">Lokasi sekolah (untuk absensi guru)</h6>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Latitude</label>
                <input name="latitude" value="{{ old('latitude', $p->latitude) }}" class="form-control" placeholder="-0.9000000">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Longitude</label>
                <input name="longitude" value="{{ old('longitude', $p->longitude) }}" class="form-control" placeholder="119.8700000">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Radius (meter)</label>
                <input type="number" name="radius_meter" value="{{ old('radius_meter', $p->radius_meter) }}" class="form-control" min="10" max="2000" required>
            </div>
        </div>

        <h6 class="fw-bold mt-2">Aturan koreksi</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Batas koreksi absensi (hari)</label>
                <input type="number" name="batas_koreksi_hari" value="{{ old('batas_koreksi_hari', $p->batas_koreksi_hari) }}" class="form-control" min="0" max="30" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Batas pengajuan izin mundur (hari)</label>
                <input type="number" name="batas_pengajuan_mundur_hari" value="{{ old('batas_pengajuan_mundur_hari', $p->batas_pengajuan_mundur_hari) }}" class="form-control" min="0" max="30" required>
            </div>
        </div>

        <h6 class="fw-bold mt-2">Foto wajah absensi guru</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="wajib_foto_guru" name="wajib_foto_guru" value="1"
                        @checked(old('wajib_foto_guru', $p->wajib_foto_guru ?? true))>
                    <label class="form-check-label" for="wajib_foto_guru">Wajibkan foto wajah (selfie) saat absen guru</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Simpan foto selama (hari)</label>
                <input type="number" name="simpan_foto_hari" value="{{ old('simpan_foto_hari', $p->simpan_foto_hari ?? 90) }}" class="form-control" min="7" max="365" required>
            </div>
        </div>

        <button class="btn btn-primary">Simpan pengaturan</button>
    </form>
</div>
@endsection