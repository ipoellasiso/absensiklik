@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Paket dibatasi jumlah siswa aktif. Harga dihitung per tahun.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPaket">Tambah paket</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
            <thead><tr class="table-light"><th class="ps-4 py-2 border-0">Paket</th><th class="py-2 border-0">Batas siswa</th><th class="py-2 border-0">Harga per tahun</th><th class="py-2 border-0 text-center">Status</th><th class="pe-4 py-2 border-0"></th></tr></thead>
            <tbody>
            @forelse($items as $p)
                <tr>
                    <td class="ps-4 fw-semibold">{{ $p->nama }}<div class="small text-muted fw-normal">{{ $p->keterangan }}</div></td>
                    <td>{{ $p->batas_siswa }}</td>
                    <td>Rp {{ number_format($p->harga_per_tahun, 0, ',', '.') }}</td>
                    <td class="text-center"><span class="badge rounded-pill bg-{{ $p->is_aktif ? 'success' : 'secondary' }}">{{ $p->is_aktif ? 'Tersedia' : 'Disembunyikan' }}</span></td>
                    <td class="pe-4 text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPaket"
                                data-title="Ubah paket" data-action="{{ route('platform.paket.simpan') }}"
                                data-item="{{ json_encode(['id' => $p->id, 'nama' => $p->nama, 'batas_siswa' => $p->batas_siswa, 'harga_per_tahun' => $p->harga_per_tahun, 'keterangan' => $p->keterangan]) }}">Ubah</button>
                        <form method="POST" action="{{ route('platform.paket.toggle', $p) }}" class="d-inline">
                            @csrf <button class="btn btn-sm btn-outline-{{ $p->is_aktif ? 'danger' : 'success' }}">{{ $p->is_aktif ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada paket. Tambahkan paket pertama.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalPaket" tabindex="-1" data-crud data-title-new="Tambah paket">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.paket.simpan') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Tambah paket</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama paket</label><input name="nama" class="form-control" maxlength="60" required></div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Batas siswa aktif</label><input type="number" name="batas_siswa" class="form-control" min="1" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Harga per tahun (Rp)</label><input type="number" name="harga_per_tahun" class="form-control" min="0" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Keterangan</label><input name="keterangan" class="form-control" maxlength="255"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@include('partials.crud-modal-js')
@endsection