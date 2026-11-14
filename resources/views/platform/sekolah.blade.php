@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">Sekolah pelanggan. Tiap sekolah punya data dan akun admin sendiri.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah sekolah</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr class="table-light">
                        <th class="ps-4 py-2 border-0">Sekolah</th>
                        <th class="py-2 border-0">Admin</th>
                        <th class="py-2 border-0">Langganan</th>
                        <th class="py-2 border-0 text-center">Siswa aktif</th>
                        <th class="py-2 border-0 text-center">Guru</th>
                        <th class="py-2 border-0 text-center">Status</th>
                        <th class="pe-4 py-2 border-0"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="ps-4 fw-semibold">
                            {{ $r['nama'] }}
                            <div class="small text-muted fw-normal">{{ $r['npsn'] ? 'NPSN '.$r['npsn'].', ' : '' }}{{ $r['kode'] }}, dibuat {{ $r['dibuat'] }}</div>
                        </td>
                        <td>{{ $r['admin'] ?: '-' }}</td>
                        <td>
                            @php($l = $r['langganan'])
                            <span class="badge bg-{{ ['aktif' => 'success', 'uji_coba' => 'info', 'tenggang' => 'warning', 'berakhir' => 'danger'][$l['status']] }}">{{ $l['label'] }}</span>
                            @if($l['sampai'])<div class="small text-muted">{{ $l['status'] === 'berakhir' ? 'berakhir' : 'sampai' }} {{ $l['sampai']->format('d/m/Y') }}</div>@endif
                        </td>
                        <td class="text-center">{{ $r['siswa'] }}</td>
                        <td class="text-center">{{ $r['guru'] }}</td>
                        <td class="text-center"><span class="badge rounded-pill bg-{{ $r['is_aktif'] ? 'success' : 'secondary' }}">{{ $r['is_aktif'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="pe-4 text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalUbah"
                                    data-title="Ubah data sekolah" data-action="{{ route('platform.sekolah.update', $r['id']) }}"
                                    data-item="{{ json_encode(['nama' => $r['nama'], 'npsn' => $r['npsn'], 'paket_id' => $r['paket_id'], 'uji_coba_sampai' => $r['uji_coba_sampai'], 'berlangganan_sampai' => $r['berlangganan_sampai']]) }}">Ubah</button>
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalReset"
                                    data-action="{{ route('platform.sekolah.reset-password', $r['id']) }}" data-nama="{{ $r['nama'] }}">Reset password admin</button>
                            <form method="POST" action="{{ route('platform.sekolah.toggle', $r['id']) }}" class="d-inline"
                                  onsubmit="return confirm('{{ $r['is_aktif'] ? 'Nonaktifkan sekolah ini? Semua penggunanya tidak bisa masuk.' : 'Aktifkan sekolah ini?' }}')">
                                @csrf <button class="btn btn-sm btn-outline-{{ $r['is_aktif'] ? 'danger' : 'success' }}">{{ $r['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada sekolah.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('platform.sekolah.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tambah sekolah</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <h6 class="fw-bold">Data sekolah</h6>
                <div class="row">
                    <div class="col-md-8 mb-3"><label class="form-label">Nama sekolah</label><input name="nama" value="{{ old('nama') }}" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">NPSN (opsional)</label><input name="npsn" value="{{ old('npsn') }}" class="form-control"></div>
                </div>
                <h6 class="fw-bold mt-2">Akun admin sekolah</h6>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Nama admin</label><input name="admin_nama" value="{{ old('admin_nama') }}" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Username</label><input name="admin_username" value="{{ old('admin_username') }}" class="form-control" required>
                        <div class="form-text">Unik di seluruh layanan, misalnya kode sekolah diikuti nama.</div></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Password</label><input type="password" name="admin_password" class="form-control" minlength="8" autocomplete="new-password" required></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalUbah" tabindex="-1" data-crud data-title-new="Ubah data sekolah">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.sekolah.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <div class="modal-header"><h5 class="modal-title">Ubah data sekolah</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama sekolah</label><input name="nama" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">NPSN</label><input name="npsn" class="form-control"></div>
            </div>
            <hr>
            <h6 class="fw-bold">Masa aktif (atur manual)</h6>
            <div class="mb-3">
                <label class="form-label">Paket</label>
                <select name="paket_id" class="form-select">
                    <option value="">Tanpa paket</option>
                    @foreach($paket as $p)<option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->batas_siswa }} siswa)</option>@endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3"><label class="form-label">Uji coba sampai</label><input type="date" name="uji_coba_sampai" class="form-control"></div>
                <div class="col-6 mb-3"><label class="form-label">Berlangganan sampai</label><input type="date" name="berlangganan_sampai" class="form-control"></div>
            </div>
            <div class="form-text">Untuk sekolah yang sudah membayar di luar sistem, atau pemberian perpanjangan khusus.</div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formReset" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Reset password admin</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="text-muted small" id="namaReset"></p>
                <label class="form-label">Password baru (minimal 8 karakter)</label>
                <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="modal-footer"><button class="btn btn-warning">Ganti password</button></div>
        </form>
    </div>
</div>

@include('partials.crud-modal-js')

@push('scripts')
<script>
document.getElementById('modalReset').addEventListener('show.bs.modal', function (e) {
    document.getElementById('formReset').action = e.relatedTarget.dataset.action;
    document.getElementById('namaReset').textContent = 'Sekolah: ' + e.relatedTarget.dataset.nama;
});
</script>
@endpush
@endsection