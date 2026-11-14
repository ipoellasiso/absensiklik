@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group">
        <a href="{{ route('platform.tagihan.index') }}" class="btn btn-outline-secondary {{ $status ? '' : 'active' }}">Semua</a>
        @foreach(['menunggu', 'lunas', 'batal'] as $st)
            <a href="{{ route('platform.tagihan.index', ['status' => $st]) }}" class="btn btn-outline-secondary {{ $status === $st ? 'active' : '' }}">{{ ucfirst($st) }}</a>
        @endforeach
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTagihan">Buat tagihan</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
            <thead><tr class="table-light"><th class="ps-4 py-2 border-0">Tagihan</th><th class="py-2 border-0">Sekolah</th><th class="py-2 border-0">Jumlah</th><th class="py-2 border-0">Status</th><th class="pe-4 py-2 border-0"></th></tr></thead>
            <tbody>
            @forelse($items as $t)
                <tr>
                    <td class="ps-4 fw-semibold">{{ $t->nomor }}<div class="small text-muted fw-normal">{{ $t->paket_nama }}, {{ $t->lama_bulan }} bulan, {{ $t->created_at->format('d/m/Y') }}</div></td>
                    <td>{{ $t->sekolah->nama ?? '-' }}</td>
                    <td>Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge bg-{{ ['menunggu' => 'warning', 'lunas' => 'success', 'batal' => 'secondary'][$t->status] }}">{{ ucfirst($t->status) }}</span>
                        @if($t->status === 'lunas')<div class="small text-muted">{{ $t->periode_mulai->format('d/m/Y') }} - {{ $t->periode_selesai->format('d/m/Y') }}</div>@endif
                        @if($t->bukti)<div class="small"><a href="{{ route('platform.tagihan.bukti', $t) }}" target="_blank">Lihat bukti transfer</a> ({{ $t->bukti_diunggah_pada?->format('d/m H:i') }})</div>@endif
                    </td>
                    <td class="pe-4 text-end text-nowrap">
                        <a href="{{ route('platform.tagihan.pdf', $t) }}" target="_blank" class="btn btn-sm btn-outline-primary">PDF</a>
                        @if($t->status === 'menunggu')
                            <form method="POST" action="{{ route('platform.tagihan.konfirmasi', $t) }}" class="d-inline" onsubmit="return confirm('Konfirmasi {{ $t->nomor }} sudah dibayar? Masa aktif sekolah akan bertambah.')">
                                @csrf <button class="btn btn-sm btn-success">Konfirmasi lunas</button>
                            </form>
                            <form method="POST" action="{{ route('platform.tagihan.batal', $t) }}" class="d-inline" onsubmit="return confirm('Batalkan tagihan ini?')">
                                @csrf <button class="btn btn-sm btn-outline-danger">Batal</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada tagihan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

<div class="modal fade" id="modalTagihan" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.tagihan.buat') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Buat tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Sekolah</label>
                    <select name="sekolah_id" class="form-select" required>@foreach($sekolah as $s)<option value="{{ $s->id }}">{{ $s->nama }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Paket</label>
                    <select name="paket_id" class="form-select" required>@foreach($paket as $p)<option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->batas_siswa }} siswa)</option>@endforeach</select></div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Lama (bulan)</label><input type="number" name="lama_bulan" value="12" min="1" max="36" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Jumlah (Rp), opsional</label><input type="number" name="jumlah" min="0" class="form-control"><div class="form-text">Kosongkan untuk harga paket. Isi untuk diskon.</div></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Buat tagihan</button></div>
        </form>
    </div>
</div>
@endsection