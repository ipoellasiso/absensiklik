@extends('Template.Layout')

@php
    $breadcumd = 'Dashboard';
    $status = ['hadir' => ['H', 'Hadir', 'bx-check-circle'], 'terlambat' => ['T', 'Terlambat', 'bx-time-five'], 'sakit' => ['S', 'Sakit', 'bx-plus-medical'], 'izin' => ['I', 'Izin', 'bx-notepad'], 'alpa' => ['A', 'Alpa', 'bx-x-circle']];
    $persen = fn ($n, $t) => $t > 0 ? round($n / $t * 100, 2) : 0;
    $namaDepan = explode(' ', auth()->user()->name)[0];
@endphp

@section('content')
@include('partials.siabsen-style')
@include('partials.pesan')

@if($mode === 'orang_tua')
    <div class="card sb-welcome mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-1">Selamat datang, {{ $namaDepan }}</h4>
            <p class="mb-0" style="opacity:.7;font-size:13px;">Kehadiran anak bulan {{ now()->translatedFormat('F Y') }}</p>
        </div>
    </div>

    <div class="row g-3">
        @forelse($anak as $a)
            @php($r = $ringkasan->get($a->id, collect()))
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">{{ $a->nama }}</h6>
                        <div class="sb-counts">
                            @foreach($status as $k => [$kode, $label])
                                <span class="sb-count sb-st-{{ $k }}"><span class="sb-kode">{{ $kode }}</span>{{ $r[$k] ?? 0 }} {{ strtolower($label) }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-warning mb-0">Belum ada data anak yang terhubung ke akun ini. Hubungi admin sekolah.</div></div>
        @endforelse
    </div>
    <a href="{{ route('pengajuan.create') }}" class="btn btn-primary mt-3">Ajukan izin atau sakit</a>
@else
    @php($belum = max($total - $tercatat, 0))

    <div class="card sb-welcome mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h4 class="fw-bold mb-1">Selamat datang, {{ $namaDepan }}</h4>
                    <p class="mb-0" style="opacity:.7;font-size:13px;">Kehadiran siswa hari ini, {{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                <div class="col-md-5 mt-3 mt-md-0">
                    <div class="d-flex gap-4 justify-content-md-end">
                        <div class="sb-welcome-stat text-center"><strong>{{ $tercatat }}</strong><small class="d-block">Sudah diabsen</small></div>
                        <div class="sb-welcome-stat text-center"><strong>{{ $belum }}</strong><small class="d-block">Belum diabsen</small></div>
                        <div class="sb-welcome-stat text-center"><strong>{{ $total }}</strong><small class="d-block">Total siswa</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($menunggu > 0)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>{{ $menunggu }} pengajuan izin atau sakit menunggu persetujuan.</span>
            <a href="{{ route('pengajuan.index', ['status' => 'menunggu']) }}" class="btn btn-sm btn-warning">Tinjau pengajuan</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach($status as $k => [$kode, $label, $ikon])
            <div class="col-6 col-md-4 col-xl">
                <div class="card sb-stat shadow-sm h-100 sb-st-{{ $k }}">
                    <div class="card-body p-3">
                        <div class="sb-ico mb-2"><i class="bx {{ $ikon }}"></i></div>
                        <div class="sb-stat-value">{{ $hitung[$k] ?? 0 }}</div>
                        <div class="sb-stat-label">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-6 col-md-4 col-xl">
            <div class="card sb-stat shadow-sm h-100 sb-st-belum">
                <div class="card-body p-3">
                    <div class="sb-ico mb-2"><i class="bx bx-hourglass"></i></div>
                    <div class="sb-stat-value">{{ $belum }}</div>
                    <div class="sb-stat-label">Belum diabsen</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-pie-chart-alt-2 text-primary me-1"></i>Komposisi kehadiran hari ini</h6>
                </div>
                <div class="card-body px-3 pb-3">
                    @if($total > 0)
                        <div id="sbDonut" style="min-height:280px;"></div>
                    @else
                        <div class="text-center text-muted py-5"><i class="bx bx-pie-chart-alt-2 fs-1 d-block mb-2"></i><small>Belum ada siswa terdaftar</small></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-layer text-success me-1"></i>Kehadiran per kelas</h6>
                </div>
                <div class="card-body p-0 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:12.5px;">
                            <thead>
                                <tr class="table-light">
                                    <th class="ps-4 py-2 border-0">Kelas</th>
                                    <th class="py-2 border-0" style="min-width:160px;">Sudah diabsen</th>
                                    <th class="pe-4 py-2 border-0">Rincian</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($perKelas as $row)
                                @php($h = $row['hitung'])
                                @php($jumlah = $row['kelas']->siswa_count)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $row['kelas']->nama }}</td>
                                    <td>
                                        <div class="sb-bar">
                                            @foreach($status as $k => $info)
                                                @if(($h[$k] ?? 0) > 0)<span class="sb-st-{{ $k }}" style="width: {{ $persen($h[$k], $jumlah) }}%"></span>@endif
                                            @endforeach
                                        </div>
                                        <small class="text-muted">{{ $h->sum() }} dari {{ $jumlah }} siswa</small>
                                    </td>
                                    <td class="pe-4">
                                        <div class="sb-counts">
                                            @foreach($status as $k => [$kode, $label])
                                                <span class="sb-count sb-st-{{ $k }}" title="{{ $label }}"><span class="sb-kode">{{ $kode }}</span>{{ $h[$k] ?? 0 }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada kelas pada tahun ajaran aktif. Minta admin menambahkan kelas dan wali kelas.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if($mode !== 'orang_tua' && $total > 0)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.querySelector('#sbDonut');
    if (!el || typeof ApexCharts === 'undefined') return;
    new ApexCharts(el, {
        series: {!! json_encode([(int) ($hitung['hadir'] ?? 0), (int) ($hitung['terlambat'] ?? 0), (int) ($hitung['sakit'] ?? 0), (int) ($hitung['izin'] ?? 0), (int) ($hitung['alpa'] ?? 0), (int) $belum]) !!},
        labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa', 'Belum diabsen'],
        colors: ['#15803D', '#B45309', '#0E7490', '#6D28D9', '#B91C1C', '#94A3B8'],
        chart: { type: 'donut', height: 280 },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' }
    }).render();
});
</script>
@endif
@endpush
