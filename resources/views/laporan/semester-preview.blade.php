@extends('Template.Layout')

@section('content')
@include('partials.pesan')

@php($query = ['kelas_id' => $kelas->id, 'semester_id' => $semester->id])

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Kelas {{ $kelas->nama }}, Semester {{ ucfirst($semester->nama) }} {{ $kelas->tahunAjaran->nama }}</h5>
        <div class="small text-muted">
            Hari efektif {{ $hariEfektif }} hari.
            @if($final)
                <span class="badge bg-success">Final</span> Angka dari rekap yang dibekukan saat semester dikunci.
            @else
                <span class="badge bg-warning text-dark">Sementara</span> Angka dihitung dari data terkini. Kunci semester di menu Tahun Ajaran untuk membekukannya.
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('laporan.rekap-semester', $query + ['format' => 'excel']) }}" class="btn btn-success">Excel</a>
        <a href="{{ route('laporan.rekap-semester', $query + ['format' => 'pdf']) }}" target="_blank" class="btn btn-danger">PDF</a>
        <a href="{{ route('laporan.index', ['ta' => $semester->tahun_ajaran_id]) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr class="table-light">
                        <th class="ps-4 py-2 border-0">Nama</th>
                        <th class="py-2 border-0 text-center">Hadir</th>
                        <th class="py-2 border-0 text-center">Terlambat</th>
                        <th class="py-2 border-0 text-center">Sakit</th>
                        <th class="py-2 border-0 text-center">Izin</th>
                        <th class="py-2 border-0 text-center">Alpa</th>
                        <th class="py-2 border-0 text-center">Kehadiran</th>
                        <th class="pe-4 py-2 border-0"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    @php($warna = $r['persen'] >= 90 ? '#15803D' : ($r['persen'] >= 75 ? '#B45309' : '#B91C1C'))
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $r['nama'] }}<div class="small text-muted fw-normal">{{ $r['nisn'] ? 'NISN '.$r['nisn'] : '' }}</div></td>
                        <td class="text-center">{{ $r['hadir'] }}</td>
                        <td class="text-center">{{ $r['terlambat'] }}</td>
                        <td class="text-center">{{ $r['sakit'] }}</td>
                        <td class="text-center">{{ $r['izin'] }}</td>
                        <td class="text-center fw-semibold" @if($r['alpa'] > 0) style="color:#B91C1C" @endif>{{ $r['alpa'] }}</td>
                        <td class="text-center fw-bold" style="color: {{ $warna }}">{{ $r['persen'] }}%</td>
                        <td class="pe-4 text-end">
                            <a href="{{ route('laporan.rapor', [$r['siswa_id'], 'semester_id' => $semester->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">PDF rapor</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Belum ada siswa pada kelas ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection