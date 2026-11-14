@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Setiap tahun ajaran otomatis punya semester ganjil dan genap.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTa">Tambah tahun ajaran</button>
</div>

@forelse($items as $ta)
    @php
        $ganjil = $ta->semester->firstWhere('nama', 'ganjil');
        $item = [
            'nama' => $ta->nama,
            'tanggal_mulai' => $ta->tanggal_mulai->toDateString(),
            'selesai_ganjil' => $ganjil?->tanggal_selesai->toDateString(),
            'tanggal_selesai' => $ta->tanggal_selesai->toDateString(),
        ];
    @endphp
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3">
            <div>
                <h6 class="mb-0 fw-bold d-inline">{{ $ta->nama }}</h6>
                @if($ta->is_aktif)<span class="badge bg-success ms-2">Aktif</span>@endif
                <div class="small text-muted">{{ $ta->tanggal_mulai->format('d/m/Y') }} sampai {{ $ta->tanggal_selesai->format('d/m/Y') }}</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTa"
                        data-title="Ubah tahun ajaran" data-action="{{ route('admin.tahun-ajaran.update', $ta) }}"
                        data-item="{{ json_encode($item) }}">Ubah</button>
                @unless($ta->is_aktif)
                    <form method="POST" action="{{ route('admin.tahun-ajaran.aktifkan', $ta) }}" onsubmit="return confirm('Jadikan {{ $ta->nama }} tahun ajaran aktif?')">
                        @csrf <button class="btn btn-sm btn-success">Aktifkan</button>
                    </form>
                @endunless
            </div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:12.5px;">
                <tbody>
                @foreach($ta->semester->sortBy('tanggal_mulai') as $s)
                    <tr>
                        <td class="ps-4">Semester {{ $s->nama }}</td>
                        <td>{{ $s->tanggal_mulai->format('d/m/Y') }} sampai {{ $s->tanggal_selesai->format('d/m/Y') }}</td>
                        <td>
                            @if($s->is_aktif)<span class="badge bg-success">Aktif</span>@endif
                            @if($s->is_terkunci)<span class="badge bg-danger">Terkunci</span>@endif
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @if($ta->is_aktif && ! $s->is_aktif)
                                <form method="POST" action="{{ route('admin.semester.aktifkan', $s) }}" class="d-inline">
                                    @csrf <button class="btn btn-sm btn-outline-success">Aktifkan semester</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.semester.kunci', $s) }}" class="d-inline"
                                  onsubmit="return confirm('{{ $s->is_terkunci ? 'Buka kunci semester ini?' : 'Kunci semester ini? Rekap kehadiran dibekukan untuk rapor, dan absensi serta pengajuan pada rentangnya tidak bisa diubah lagi.' }}')">
                                @csrf <button class="btn btn-sm btn-outline-secondary">{{ $s->is_terkunci ? 'Buka kunci' : 'Kunci' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">Belum ada tahun ajaran. Tambahkan yang pertama, lalu aktifkan.</div></div>
@endforelse

<div class="modal fade" id="modalTa" tabindex="-1" data-crud data-title-new="Tambah tahun ajaran">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.tahun-ajaran.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <div class="modal-header"><h5 class="modal-title">Tambah tahun ajaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama (contoh 2026/2027)</label>
                    <input name="nama" class="form-control" pattern="\d{4}/\d{4}" required>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Mulai</label><input type="date" name="tanggal_mulai" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Akhir semester ganjil</label><input type="date" name="selesai_ganjil" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Selesai</label><input type="date" name="tanggal_selesai" class="form-control" required></div>
                </div>
                <p class="small text-muted mb-0">Semester genap otomatis dimulai sehari setelah semester ganjil berakhir.</p>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@include('partials.crud-modal-js')
@endsection