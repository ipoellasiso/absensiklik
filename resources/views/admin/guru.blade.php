@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Akun guru dan kepala sekolah. Akun tidak dihapus, hanya dinonaktifkan.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGuru">Tambah guru</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr class="table-light">
                        <th class="ps-4 py-2 border-0">Nama</th>
                        <th class="py-2 border-0">Username</th>
                        <th class="py-2 border-0">NIP / Jabatan</th>
                        <th class="py-2 border-0">Peran</th>
                        <th class="py-2 border-0 text-center">Status</th>
                        <th class="pe-4 py-2 border-0"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        {{-- <td class="ps-4 fw-semibold">{{ $r['name'] }}<div class="small text-muted fw-normal">{{ $r['no_hp'] }}</div></td> --}}
                        <td class="ps-4 fw-semibold">
                            <div class="d-flex align-items-center gap-2">
                                @if($r['ada_foto'])
                                    <img src="{{ route('admin.guru.foto', [$r['id'], 'v' => $r['foto_v']]) }}" alt="" width="40" height="40" class="rounded-circle" style="object-fit:cover">
                                @else
                                    <span class="rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px">{{ mb_strtoupper(mb_substr($r['name'], 0, 1)) }}</span>
                                @endif
                                <div>{{ $r['name'] }}<div class="small text-muted fw-normal">{{ $r['no_hp'] }}</div></div>
                            </div>
                        </td>
                        <td>{{ $r['username'] }}</td>
                        <td>{{ $r['nip'] ?: '-' }}<div class="small text-muted">{{ $r['jabatan'] }}</div></td>
                        <td>{{ $r['role'] === 'kepala_sekolah' ? 'Kepala sekolah' : 'Guru' }}</td>
                        <td class="text-center"><span class="badge rounded-pill bg-{{ $r['is_active'] ? 'success' : 'secondary' }}">{{ $r['is_active'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="pe-4 text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalGuru"
                                    data-title="Ubah data guru" data-action="{{ route('admin.guru.update', $r['id']) }}"
                                    data-item="{{ json_encode($r) }}">Ubah</button>
                            <form method="POST" action="{{ route('admin.guru.toggle', $r['id']) }}" class="d-inline"
                                  onsubmit="return confirm('{{ $r['is_active'] ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}')">
                                @csrf <button class="btn btn-sm btn-outline-{{ $r['is_active'] ? 'danger' : 'success' }}">{{ $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada guru. Tambahkan guru pertama.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGuru" tabindex="-1" data-crud data-title-new="Tambah guru">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.guru.store') }}" class="modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <div class="modal-header"><h5 class="modal-title">Tambah guru</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nama lengkap</label><input name="name" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Peran</label>
                        <select name="role" class="form-select" required>
                            <option value="guru">Guru</option>
                            <option value="kepala_sekolah">Kepala sekolah</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimal 8 karakter. Saat mengubah data, kosongkan jika tidak ingin mengganti.</div>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Email (opsional)</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">No. HP (opsional)</label><input name="no_hp" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">NIP (opsional)</label><input name="nip" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Jabatan (opsional)</label><input name="jabatan" class="form-control"></div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Foto (opsional)</label>
                        <input type="file" name="foto" accept="image/jpeg,image/png" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapusFotoGuru"><label class="form-check-label" for="hapusFotoGuru">Hapus foto</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@include('partials.crud-modal-js')
@endsection