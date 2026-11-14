@extends('Template.Layout')

@php($breadcumd = 'Izin / Sakit')

@section('content')
@include('partials.siabsen-style')
@include('partials.pesan')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group">
        <a href="{{ route('pengajuan.index') }}" class="btn btn-outline-secondary {{ request('status') ? '' : 'active' }}">Semua</a>
        @foreach(['menunggu', 'disetujui', 'ditolak'] as $st)
            <a href="{{ route('pengajuan.index', ['status' => $st]) }}" class="btn btn-outline-secondary {{ request('status') === $st ? 'active' : '' }}">{{ ucfirst($st) }}</a>
        @endforeach
    </div>
    @if($bisaMengajukan)<a href="{{ route('pengajuan.create') }}" class="btn btn-primary">Buat pengajuan</a>@endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr class="table-light">
                        <th class="ps-4 py-2 border-0">Pemohon</th>
                        <th class="py-2 border-0">Jenis</th>
                        <th class="py-2 border-0">Tanggal</th>
                        <th class="py-2 border-0">Alasan</th>
                        <th class="py-2 border-0 text-center">Status</th>
                        <th class="pe-4 py-2 border-0"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $p)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $p->siswa->nama ?? ($p->guru->user->name ?? '-') }}</div>
                            <small class="text-muted">{{ $p->siswa_id ? 'Siswa' : 'Guru' }}</small>
                        </td>
                        <td>{{ str_replace('_', ' ', ucfirst($p->jenis)) }}</td>
                        <td>
                            {{ $p->tanggal_mulai->format('d/m/Y') }}
                            @if(! $p->tanggal_mulai->isSameDay($p->tanggal_selesai)) - {{ $p->tanggal_selesai->format('d/m/Y') }} @endif
                        </td>
                        <td style="max-width:260px;">
                            {{ $p->alasan ?: '-' }}
                            @if($p->lampiran)<div><a href="{{ asset('storage/'.$p->lampiran) }}" target="_blank" class="small">Lihat lampiran</a></div>@endif
                            @if($p->catatan_penolakan)<div class="small text-danger">Ditolak: {{ $p->catatan_penolakan }}</div>@endif
                        </td>
                        <td class="text-center">
                            @php($warna = ['menunggu' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger'][$p->status])
                            <span class="badge rounded-pill bg-{{ $warna }}">{{ ucfirst($p->status) }}</span>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @if($boleh[$p->id] ?? false)
                                <form method="POST" action="{{ route('pengajuan.setujui', $p) }}" class="d-inline" onsubmit="return confirm('Setujui pengajuan ini?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-success">Setujui</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTolak" data-action="{{ route('pengajuan.tolak', $p) }}">Tolak</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5"><i class="bx bx-envelope fs-2 d-block mb-1"></i>Belum ada pengajuan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="formTolak">
            @csrf @method('PATCH')
            <div class="modal-header"><h5 class="modal-title">Tolak pengajuan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <label class="form-label">Alasan penolakan</label>
                <input name="catatan_penolakan" class="form-control" maxlength="255" required>
            </div>
            <div class="modal-footer"><button class="btn btn-danger">Tolak pengajuan</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalTolak');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (e) {
        document.getElementById('formTolak').action = e.relatedTarget.dataset.action;
    });
});
</script>
@endpush
