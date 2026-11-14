@extends('Template.Layout')

@section('content')
@include('partials.pesan')

@if(! $dipilih)
    <div class="alert alert-warning">Belum ada tahun ajaran. Buat dan aktifkan tahun ajaran terlebih dahulu di menu Tahun Ajaran.</div>
@else
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET">
            <select name="ta" class="form-select" onchange="this.form.submit()" aria-label="Tahun ajaran">
                @foreach($tahun as $t)
                    <option value="{{ $t->id }}" @selected($dipilih->id === $t->id)>Tahun ajaran {{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
                @endforeach
            </select>
        </form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKelas">Tambah kelas</button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                    <thead>
                        <tr class="table-light">
                            <th class="ps-4 py-2 border-0">Kelas</th>
                            <th class="py-2 border-0">Tingkat</th>
                            <th class="py-2 border-0">Wali kelas</th>
                            <th class="py-2 border-0 text-center">Siswa</th>
                            <th class="pe-4 py-2 border-0"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $r['nama'] }}</td>
                            <td>{{ $r['tingkat'] }}</td>
                            <td>{{ $r['wali'] ?: '-' }}</td>
                            <td class="text-center">{{ $r['jumlah'] }}</td>
                            <td class="pe-4 text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalKelas"
                                        data-title="Ubah kelas" data-action="{{ route('admin.kelas.update', $r['id']) }}"
                                        data-item="{{ json_encode($r) }}">Ubah</button>
                                <form method="POST" action="{{ route('admin.kelas.destroy', $r['id']) }}" class="d-inline" onsubmit="return confirm('Hapus kelas {{ $r['nama'] }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada kelas pada tahun ajaran ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalKelas" tabindex="-1" data-crud data-title-new="Tambah kelas">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.kelas.store') }}" class="modal-content">
                @csrf
                <input type="hidden" name="_method" value="PUT" disabled>
                <input type="hidden" name="tahun_ajaran_id" value="{{ $dipilih->id }}">
                <div class="modal-header"><h5 class="modal-title">Tambah kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3"><label class="form-label">Nama kelas (contoh 1A)</label><input name="nama" class="form-control" maxlength="20" required></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" class="form-select" required>
                                @for($i = 1; $i <= 6; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wali kelas</label>
                        <select name="wali_kelas_id" class="form-select">
                            <option value="">Belum ditentukan</option>
                            @foreach($guru as $g)<option value="{{ $g['id'] }}">{{ $g['nama'] }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>

    @include('partials.crud-modal-js')
@endif
@endsection