@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" class="d-flex gap-2">
        <input name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama, username, atau WA" style="min-width:260px;">
        <button class="btn btn-outline-secondary">Cari</button>
    </form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrtu">Tambah orang tua</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr class="table-light">
                        <th class="ps-4 py-2 border-0">Nama</th>
                        <th class="py-2 border-0">Username</th>
                        <th class="py-2 border-0">No. WA</th>
                        <th class="py-2 border-0 text-center">Anak</th>
                        <th class="py-2 border-0 text-center">Status</th>
                        <th class="pe-4 py-2 border-0"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $r)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $r['name'] }}<div class="small text-muted fw-normal">{{ $r['alamat'] }}</div></td>
                        <td>{{ $r['username'] }}</td>
                        <td>{{ $r['no_wa'] ?: '-' }}</td>
                        <td class="text-center">{{ $r['anak'] }}</td>
                        <td class="text-center"><span class="badge rounded-pill bg-{{ $r['is_active'] ? 'success' : 'secondary' }}">{{ $r['is_active'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="pe-4 text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalOrtu"
                                    data-title="Ubah data orang tua" data-action="{{ route('admin.orang-tua.update', $r['id']) }}"
                                    data-item="{{ json_encode($r) }}">Ubah</button>
                            <form method="POST" action="{{ route('admin.orang-tua.toggle', $r['id']) }}" class="d-inline"
                                  onsubmit="return confirm('{{ $r['is_active'] ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}')">
                                @csrf <button class="btn btn-sm btn-outline-{{ $r['is_active'] ? 'danger' : 'success' }}">{{ $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data orang tua.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

<div class="modal fade" id="modalOrtu" tabindex="-1" data-crud data-title-new="Tambah orang tua">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.orang-tua.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <div class="modal-header"><h5 class="modal-title">Tambah orang tua</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nama lengkap</label><input name="name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">No. WhatsApp</label><input name="no_wa" class="form-control" placeholder="08xxxxxxxxxx"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimal 8 karakter. Saat mengubah data, kosongkan jika tidak ingin mengganti.</div>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Email (opsional)</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">No. HP lain (opsional)</label><input name="no_hp" class="form-control"></div>
                    <div class="col-12 mb-3"><label class="form-label">Alamat (opsional)</label><textarea name="alamat" rows="2" class="form-control"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@include('partials.crud-modal-js')
@endsection