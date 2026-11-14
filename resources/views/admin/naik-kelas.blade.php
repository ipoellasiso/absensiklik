@extends('Template.Layout')

@section('content')
@include('partials.pesan')

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <label class="form-label small mb-1">Dari tahun ajaran</label>
        <select name="asal" class="form-select" onchange="this.form.submit()">
            @foreach($tahun as $t)<option value="{{ $t->id }}" @selected($asal && $asal->id === $t->id)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small mb-1">Ke tahun ajaran</label>
        <select name="tujuan" class="form-select" onchange="this.form.submit()">
            <option value="">Pilih tahun ajaran tujuan</option>
            @foreach($tahun as $t)@if(! $asal || $t->id !== $asal->id)<option value="{{ $t->id }}" @selected($tujuan && $tujuan->id === $t->id)>{{ $t->nama }}</option>@endif @endforeach
        </select>
    </div>
</form>

@if(! $tujuan)
    <div class="alert alert-info">Buat tahun ajaran tujuan beserta kelas-kelasnya terlebih dahulu (menu Tahun Ajaran dan Kelas), lalu pilih di atas.</div>
@elseif($kelasTujuan->isEmpty())
    <div class="alert alert-warning">Tahun ajaran {{ $tujuan->nama }} belum punya kelas. Tambahkan kelasnya di menu Kelas.</div>
@else
    <form method="POST" action="{{ route('admin.naik-kelas.proses') }}" onsubmit="return confirm('Proses kenaikan kelas dari {{ $asal->nama }} ke {{ $tujuan->nama }}?')">
        @csrf
        <input type="hidden" name="asal" value="{{ $asal->id }}">
        <input type="hidden" name="tujuan" value="{{ $tujuan->id }}">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Tentukan kelas tujuan per kelas</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:12.5px;">
                    <thead>
                        <tr class="table-light">
                            <th class="ps-4 py-2 border-0">Kelas {{ $asal->nama }}</th>
                            <th class="py-2 border-0 text-center">Siswa aktif</th>
                            <th class="pe-4 py-2 border-0" style="min-width:260px;">Pindah ke ({{ $tujuan->nama }})</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $r['nama'] }}</td>
                            <td class="text-center">{{ $r['jumlah'] }}</td>
                            <td class="pe-4">
                                <select name="map[{{ $r['id'] }}]" class="form-select form-select-sm" aria-label="Tujuan kelas {{ $r['nama'] }}">
                                    <option value="" @selected($r['saran'] === '')>Tidak diproses</option>
                                    <option value="lulus" @selected($r['saran'] === 'lulus')>Lulus (siswa dinonaktifkan)</option>
                                    @foreach($kelasTujuan as $kt)<option value="{{ $kt->id }}" @selected($r['saran'] === $kt->id)>Kelas {{ $kt->nama }}</option>@endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">Siswa tinggal kelas: pilih "Tidak diproses", lalu tempatkan manual lewat menu Siswa setelah tahun ajaran tujuan diaktifkan.</span>
                <button class="btn btn-primary">Proses kenaikan kelas</button>
            </div>
        </div>
    </form>
@endif
@endsection