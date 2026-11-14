@extends('Template.Layout')

@section('content')
@include('partials.pesan')

@if(! $ta)
    <div class="alert alert-warning">Belum ada tahun ajaran aktif. Aktifkan tahun ajaran di menu Tahun Ajaran terlebih dahulu.</div>
@else
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama atau NISN" style="min-width:220px;">
            <select name="kelas_id" class="form-select" style="width:auto;" onchange="this.form.submit()" aria-label="Kelas">
                <option value="">Semua kelas</option>
                <option value="none" @selected($kelasFilter === 'none')>Belum punya kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" @selected($kelasFilter === (string) $k->id)>Kelas {{ $k->nama }}</option>@endforeach
            </select>
            <select name="status" class="form-select" style="width:auto;" onchange="this.form.submit()" aria-label="Status">
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
                <option value="semua" @selected($status === 'semua')>Semua</option>
            </select>
            <button class="btn btn-outline-secondary">Cari</button>
        </form>
        <div class="d-flex gap-2">
            @if(ctype_digit($kelasFilter))
                <a href="{{ route('admin.kartu.cetak', ['kelas_id' => $kelasFilter]) }}" target="_blank" class="btn btn-outline-primary">Cetak kartu kelas ini</a>
            @endif
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSiswa">Tambah siswa</button>
        </div>
    </div>
    <p class="small text-muted">Kelas yang tampil adalah kelas pada tahun ajaran aktif ({{ $ta->nama }}).</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                    <thead>
                        <tr class="table-light">
                            <th class="ps-4 py-2 border-0">Nama</th>
                            <th class="py-2 border-0">L/P</th>
                            <th class="py-2 border-0">Kelas</th>
                            <th class="py-2 border-0">Orang tua</th>
                            <th class="py-2 border-0 text-center">Status</th>
                            <th class="pe-4 py-2 border-0"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $r)
                        <tr>
                            {{-- <td class="ps-4 fw-semibold">{{ $r['nama'] }}<div class="small text-muted fw-normal">{{ $r['nisn'] ? 'NISN '.$r['nisn'] : 'NISN belum diisi' }}</div></td> --}}
                            <td class="ps-4 fw-semibold">
                                <div class="d-flex align-items-center gap-2">
                                    @if($r['ada_foto'])
                                        <img src="{{ route('admin.siswa.foto', [$r['id'], 'v' => $r['foto_v']]) }}" alt="" width="36" height="48" class="rounded" style="object-fit:cover">
                                    @else
                                        <span class="rounded bg-light text-muted d-inline-flex align-items-center justify-content-center" style="width:36px;height:48px">{{ mb_strtoupper(mb_substr($r['nama'], 0, 1)) }}</span>
                                    @endif
                                    <div>{{ $r['nama'] }}<div class="small text-muted fw-normal">{{ $r['nisn'] ? 'NISN '.$r['nisn'] : 'NISN belum diisi' }}</div></div>
                                </div>
                            </td>
                            <td>{{ $r['jenis_kelamin'] }}</td>
                            <td>{{ $r['kelas_nama'] ?: '-' }}</td>
                            <td>{{ $r['ortu_nama'] ?: '-' }}</td>
                            <td class="text-center"><span class="badge rounded-pill bg-{{ $r['is_aktif'] ? 'success' : 'secondary' }}">{{ $r['is_aktif'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="pe-4 text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalSiswa"
                                        data-title="Ubah data siswa" data-action="{{ route('admin.siswa.update', $r['id']) }}"
                                        data-item="{{ json_encode($r) }}">Ubah</button>
                                @if($r['is_aktif'])
                                    <a href="{{ route('admin.kartu.cetak', ['siswa_id' => $r['id']]) }}" target="_blank" class="btn btn-sm btn-outline-primary">Kartu</a>
                                    <form method="POST" action="{{ route('admin.siswa.qr-baru', $r['id']) }}" class="d-inline"
                                          onsubmit="return confirm('Ganti QR {{ $r['nama'] }}? Kartu lama tidak akan berlaku lagi.')">
                                        @csrf <button class="btn btn-sm btn-outline-warning" title="Gunakan jika kartu hilang">QR baru</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.siswa.toggle', $r['id']) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $r['is_aktif'] ? 'Nonaktifkan siswa ini?' : 'Aktifkan siswa ini?' }}')">
                                    @csrf <button class="btn btn-sm btn-outline-{{ $r['is_aktif'] ? 'danger' : 'success' }}">{{ $r['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada siswa yang cocok dengan filter ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

    <div class="modal fade" id="modalSiswa" tabindex="-1" data-crud data-title-new="Tambah siswa">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('admin.siswa.store') }}" class="modal-content" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT" disabled>
                <div class="modal-header"><h5 class="modal-title">Tambah siswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3"><label class="form-label">Nama lengkap</label><input name="nama" class="form-control" required></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required><option value="L">Laki-laki</option><option value="P">Perempuan</option></select>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label">NISN (10 digit)</label><input name="nisn" class="form-control" maxlength="10" inputmode="numeric"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">NIS</label><input name="nis" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Tanggal lahir</label><input type="date" name="tanggal_lahir" class="form-control"></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kelas ({{ $ta->nama }})</label>
                            <select name="kelas_id" class="form-select">
                                <option value="">Belum ditentukan</option>
                                @foreach($kelasList as $k)<option value="{{ $k->id }}">Kelas {{ $k->nama }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Orang tua / wali</label>
                            <select name="orang_tua_id" class="form-select">
                                <option value="">Belum ditentukan</option>
                                @foreach($ortu as $o)<option value="{{ $o['id'] }}">{{ $o['nama'] }}</option>@endforeach
                            </select>
                            <div class="form-text">Belum ada? Tambahkan dulu di menu Orang Tua.</div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Foto (opsional)</label>
                            <input type="file" name="foto" accept="image/jpeg,image/png" class="form-control">
                            <div class="form-text">Foto wajah menghadap depan. Otomatis dipotong rasio 3:4.</div>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapusFotoSiswa"><label class="form-check-label" for="hapusFotoSiswa">Hapus foto</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>

    @include('partials.crud-modal-js')
@endif
@endsection