@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Libur nasional</h6></div>
            <div class="card-body px-4">
                <p class="small text-muted">Salin libur nasional ke kalender sekolah untuk periode {{ $dari->translatedFormat('d M Y') }} sampai {{ $sampai->translatedFormat('d M Y') }}.</p>
                <form method="POST" action="{{ route('admin.hari-libur.salin-nasional') }}">
                    @csrf
                    <button class="btn btn-primary" @disabled($belumDisalin === 0)>
                        {{ $belumDisalin > 0 ? "Salin {$belumDisalin} libur nasional" : 'Semua sudah disalin' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Tambah libur sekolah</h6></div>
            <form method="POST" action="{{ route('admin.hari-libur.simpan') }}" class="card-body px-4">
                @csrf
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Mulai</label><input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Sampai (opsional)</label><input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" class="form-control"></div>
                </div>
                <div class="mb-3"><label class="form-label">Keterangan</label><input name="keterangan" value="{{ old('keterangan') }}" class="form-control" placeholder="Libur semester, ujian, rapat guru" required></div>
                <button class="btn btn-primary">Tambah</button>
                <div class="form-text mt-2">Sabtu dan Minggu sudah otomatis bukan hari efektif.</div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Kalender libur sekolah ({{ $rows->count() }} hari)</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:12.5px;">
                    <tbody>
                    @forelse($rows as $h)
                        <tr>
                            <td class="ps-4" style="width:180px;">{{ $h->tanggal->translatedFormat('l, d M Y') }}</td>
                            <td>{{ $h->keterangan }}</td>
                            <td class="pe-4 text-end">
                                <form method="POST" action="{{ route('admin.hari-libur.hapus', $h) }}" onsubmit="return confirm('Hapus hari libur ini?')">
                                    @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-5">Belum ada hari libur. Salin libur nasional atau tambahkan sendiri.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection