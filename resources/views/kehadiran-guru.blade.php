@extends('Template.Layout')

@php
    $label = ['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'sakit' => 'Sakit', 'izin' => 'Izin', 'dinas_luar' => 'Dinas luar', 'tanpa_keterangan' => 'Tanpa keterangan'];
@endphp

@section('content')
@include('partials.siabsen-style')
@include('partials.pesan')
<style>
  .sb-st-dinas_luar { --c: #1D4ED8; }
  .sb-st-tanpa_keterangan { --c: #B91C1C; }
</style>

<form method="GET" class="mb-3" style="max-width:240px;">
    <label for="tanggal" class="visually-hidden">Tanggal</label>
    <input id="tanggal" type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()">
</form>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <p class="fs-5 mb-3"><strong>{{ $rows->count() - $belum }}</strong> dari {{ $rows->count() }} guru sudah tercatat pada {{ $tanggal->translatedFormat('l, d F Y') }}.</p>
        <div class="sb-counts">
            @foreach($label as $k => $nama)
                <span class="sb-count sb-st-{{ $k }}"><i class="sb-dot"></i>{{ $nama }} {{ $hitung[$k] ?? 0 }}</span>
            @endforeach
            <span class="sb-count sb-st-belum"><i class="sb-dot"></i>Belum absen {{ $belum }}</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr class="table-light">
                        <th class="ps-4 py-2 border-0">Nama</th>
                        <th class="py-2 border-0">Masuk</th>
                        <th class="py-2 border-0">Pulang</th>
                        <th class="py-2 border-0">Foto</th>
                        <th class="py-2 border-0">Jarak</th>
                        <th class="py-2 border-0">Status</th>
                        <th class="pe-4 py-2 border-0"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $r['nama'] }}<div class="small text-muted fw-normal">{{ $r['jabatan'] }}</div></td>
                        <td>{{ $r['jam_masuk'] ?: '-' }}</td>
                        <td>{{ $r['jam_pulang'] ?: '-' }}</td>
                        <td class="text-nowrap">
                            @foreach(['masuk' => 'foto_masuk', 'pulang' => 'foto_pulang'] as $jenis => $kolom)
                                @if($r[$kolom])
                                    @php($url = route('kehadiran-guru.foto', [$r['absensi_id'], $jenis]))
                                    <img src="{{ $url }}" alt="Foto {{ $jenis }} {{ $r['nama'] }}" width="40" height="40" loading="lazy"
                                        class="rounded me-1" style="object-fit:cover;cursor:pointer"
                                        data-bs-toggle="modal" data-bs-target="#modalFoto" data-src="{{ $url }}" data-judul="{{ $r['nama'] }}, {{ $jenis }}">
                                @endif
                            @endforeach
                            @if(! $r['foto_masuk'] && ! $r['foto_pulang'])<span class="text-muted">-</span>@endif
                        </td>
                        <td>{{ $r['jarak'] !== null ? $r['jarak'].' m' : '-' }}</td>
                        <td>
                            @if($r['status'])
                                <span class="sb-count sb-st-{{ $r['status'] }}"><i class="sb-dot"></i>{{ $label[$r['status']] }}</span>
                                @if($r['sumber'] && $r['sumber'] !== 'aplikasi')<span class="sb-tag">{{ $r['sumber'] }}</span>@endif
                                @if($r['catatan'])<div class="small text-muted">{{ $r['catatan'] }}</div>@endif
                            @else
                                <span class="text-muted">Belum absen</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalKoreksi"
                                    data-title="Koreksi kehadiran" data-action="{{ route('kehadiran-guru.simpan') }}"
                                    data-item="{{ json_encode([
                                        'guru_id' => $r['guru_id'], 'nama' => $r['nama'], 'tanggal' => $tanggal->toDateString(),
                                        'status' => $r['status'] ?? 'hadir', 'jam_masuk' => $r['jam_masuk'], 'jam_pulang' => $r['jam_pulang'], 'catatan' => $r['catatan'],
                                    ]) }}">Koreksi</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKoreksi" tabindex="-1" data-crud data-title-new="Koreksi kehadiran">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('kehadiran-guru.simpan') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="guru_id">
            <input type="hidden" name="tanggal">
            <div class="modal-header"><h5 class="modal-title">Koreksi kehadiran</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Guru</label><input name="nama" class="form-control" readonly></div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach($label as $k => $nama)<option value="{{ $k }}">{{ $nama }}</option>@endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Jam masuk</label><input type="time" name="jam_masuk" class="form-control"></div>
                    <div class="col-6 mb-3"><label class="form-label">Jam pulang</label><input type="time" name="jam_pulang" class="form-control"></div>
                </div>
                <div class="mb-3"><label class="form-label">Catatan</label><input name="catatan" class="form-control" maxlength="255"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan koreksi</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalFoto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="judulFoto"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center"><img id="isiFoto" alt="" class="img-fluid rounded"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('modalFoto').addEventListener('show.bs.modal', function (e) {
    document.getElementById('isiFoto').src = e.relatedTarget.dataset.src;
    document.getElementById('judulFoto').textContent = e.relatedTarget.dataset.judul;
});
</script>
@endpush

@include('partials.crud-modal-js')
@endsection