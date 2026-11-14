@extends('Template.Layout')

@section('content')
@include('partials.pesan')

@if(! $ta)
    <div class="alert alert-warning">Belum ada tahun ajaran. Buat dan aktifkan tahun ajaran di menu Data Master terlebih dahulu.</div>
@else
    <form method="GET" class="mb-3" style="max-width:320px;">
        <label class="form-label small mb-1">Tahun ajaran</label>
        <select name="ta" class="form-select" onchange="this.form.submit()">
            @foreach($tahun as $t)
                <option value="{{ $t->id }}" @selected($ta->id === $t->id)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
            @endforeach
        </select>
    </form>

    @if($kelas->isEmpty())
        <div class="alert alert-info">Tidak ada kelas yang bisa Anda akses pada tahun ajaran ini.</div>
    @else
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold"><i class="bx bx-calendar text-primary me-1"></i>Rekap absensi bulanan per kelas</h6></div>
                    <form method="GET" action="{{ route('laporan.rekap-bulanan') }}" class="card-body px-4">
                        <p class="text-muted small">Daftar hadir siswa per tanggal dengan kode H, T, S, I, A dan persentase.</p>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                @foreach($kelas as $k)<option value="{{ $k->id }}">Kelas {{ $k->nama }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" class="form-select" required>
                                @foreach($bulan as $kunci => $nama)<option value="{{ $kunci }}" @selected($kunci === $bulanDefault)>{{ $nama }}</option>@endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button name="format" value="excel" class="btn btn-success">Unduh Excel</button>
                            <button name="format" value="pdf" formtarget="_blank" class="btn btn-danger">Buka PDF</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold"><i class="bx bx-book-content text-success me-1"></i>Rekap semester dan lembar rapor</h6></div>
                    <form method="GET" action="{{ route('laporan.rekap-semester') }}" class="card-body px-4">
                        <p class="text-muted small">Pratinjau rekap per siswa, lalu unduh Excel, PDF kelas, atau PDF kehadiran per siswa untuk rapor.</p>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                @foreach($kelas as $k)<option value="{{ $k->id }}">Kelas {{ $k->nama }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester_id" class="form-select" required>
                                @foreach($semester as $s)
                                    <option value="{{ $s->id }}">{{ ucfirst($s->nama) }}{{ $s->is_terkunci ? ' (terkunci, final)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary">Lihat rekap</button>
                    </form>
                </div>
            </div>

            @if($lihatGuru)
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold"><i class="bx bx-user-check text-warning me-1"></i>Rekap kehadiran guru bulanan</h6></div>
                        <form method="GET" action="{{ route('laporan.guru-bulanan') }}" class="card-body px-4">
                            <p class="text-muted small">Kehadiran guru dan tenaga kependidikan per tanggal (H, T, S, I, D, TK).</p>
                            <div class="mb-3">
                                <label class="form-label">Bulan</label>
                                <select name="bulan" class="form-select" required>
                                    @foreach($bulan as $kunci => $nama)<option value="{{ $kunci }}" @selected($kunci === $bulanDefault)>{{ $nama }}</option>@endforeach
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button name="format" value="excel" class="btn btn-success">Unduh Excel</button>
                                <button name="format" value="pdf" formtarget="_blank" class="btn btn-danger">Buka PDF</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endif
@endsection