@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="row g-3">
    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Tambah libur nasional</h6></div>
            <form method="POST" action="{{ route('platform.libur-nasional.simpan') }}" class="card-body px-4">
                @csrf
                <p class="small text-muted">Satu baris satu tanggal, format <code>YYYY-MM-DD|Keterangan</code>. Tanggal yang sudah ada akan diperbarui.</p>
                <textarea name="daftar" rows="10" class="form-control mb-3" placeholder="2027-08-17|Hari Kemerdekaan RI&#10;2027-12-25|Hari Raya Natal" required>{{ old('daftar') }}</textarea>
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Daftar libur nasional ({{ $items->count() }})</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:12.5px;">
                    <tbody>
                    @forelse($items as $h)
                        <tr>
                            <td class="ps-4" style="width:150px;">{{ $h->tanggal->translatedFormat('d M Y') }}</td>
                            <td>{{ $h->keterangan }}</td>
                            <td class="pe-4 text-end">
                                <form method="POST" action="{{ route('platform.libur-nasional.hapus', $h) }}" onsubmit="return confirm('Hapus tanggal ini?')">
                                    @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-5">Belum ada data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection