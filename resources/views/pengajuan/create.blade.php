@extends('Template.Layout')

@php($breadcumd = 'Izin / Sakit')
@php($breadcumd1 = 'Buat pengajuan')

@section('content')
@include('partials.pesan')

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Buat pengajuan izin atau sakit</h6></div>
    <form method="POST" action="{{ route('pengajuan.store') }}" enctype="multipart/form-data" class="card-body px-4">
        @csrf

        <div class="mb-3">
            <label class="form-label">{{ $isGuru ? 'Untuk siapa' : 'Nama anak' }}</label>
            <select name="siswa_id" class="form-select" @if(! $isGuru) required @endif>
                @if($isGuru)<option value="">Izin saya sendiri (guru)</option>@endif
                @foreach($siswa as $s)
                    <option value="{{ $s->id }}" @selected(old('siswa_id') == $s->id)>{{ $s->nama }}@if($isGuru) (siswa, dari surat fisik)@endif</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Jenis</label>
            <select name="jenis" class="form-select" required>
                <option value="sakit" @selected(old('jenis') === 'sakit')>Sakit</option>
                <option value="izin" @selected(old('jenis') === 'izin')>Izin</option>
                @if($isGuru)<option value="dinas_luar" @selected(old('jenis') === 'dinas_luar')>Dinas luar (khusus guru)</option>@endif
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tanggal mulai</label>
                <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', today()->toDateString()) }}" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tanggal selesai</label>
                <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', today()->toDateString()) }}" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Alasan</label>
            <textarea name="alasan" rows="3" maxlength="500" class="form-control">{{ old('alasan') }}</textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Lampiran surat (opsional, JPG/PNG/PDF, maks 2 MB)</label>
            <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Kirim pengajuan</button>
            <a href="{{ route('pengajuan.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
