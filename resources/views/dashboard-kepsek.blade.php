@extends('Template.Layout')

@php
    $statusSiswa = ['hadir' => ['H', 'Hadir'], 'terlambat' => ['T', 'Terlambat'], 'sakit' => ['S', 'Sakit'], 'izin' => ['I', 'Izin'], 'alpa' => ['A', 'Alpa']];
    $statusGuru = ['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'sakit' => 'Sakit', 'izin' => 'Izin', 'dinas_luar' => 'Dinas luar', 'tanpa_keterangan' => 'Tanpa keterangan'];
    $persen = fn ($n, $t) => $t > 0 ? round($n / $t * 100, 2) : 0;
    $warnaPersen = fn ($p) => $p === null ? '#94A3B8' : ($p >= 90 ? '#15803D' : ($p >= 75 ? '#B45309' : '#B91C1C'));
@endphp

@section('content')
@include('partials.siabsen-style')
@include('partials.pesan')
<style>
  .sb-st-dinas_luar { --c: #1D4ED8; }
  .sb-st-tanpa_keterangan { --c: #B91C1C; }
  .sb-item { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,.06); }
  .sb-item:last-child { border-bottom: 0; padding-bottom: 0; }
</style>

@if(! $ta)
    <div class="alert alert-warning">Belum ada tahun ajaran aktif. Aktifkan tahun ajaran di menu Data Master, Tahun Ajaran.</div>
@else
    @php
        $siswaTercatat = (int) $siswaHariIni->sum();
        $siswaBelum = max($totalSiswa - $siswaTercatat, 0);
        $hadirSiswa = (int) ($siswaHariIni['hadir'] ?? 0) + (int) ($siswaHariIni['terlambat'] ?? 0);
        $guruTercatat = (int) $guruHariIni->sum();
        $guruBelum = max($totalGuru - $guruTercatat, 0);
        $namaDepan = explode(' ', auth()->user()->name)[0];
    @endphp

    <div class="card sb-welcome mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-7">
                    <h4 class="fw-bold mb-1">Selamat datang, {{ $namaDepan }}</h4>
                    <p class="mb-0" style="opacity:.7;font-size:13px;">Tahun ajaran {{ $ta->nama }}, {{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                <div class="col-md-5">
                    <form method="GET" class="d-flex align-items-center gap-2 justify-content-md-end">
                        <label for="bulan" class="small mb-0" style="opacity:.8;">Bulan</label>
                        <select id="bulan" name="bulan" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
                            @foreach($bulanTersedia as $kunci => $nama)
                                <option value="{{ $kunci }}" @selected($kunci === $bulan)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($izinGuruMenunggu > 0 || $izinSiswaMenunggu > 0)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                @if($izinGuruMenunggu > 0)<strong>{{ $izinGuruMenunggu }}</strong> izin guru menunggu persetujuan Anda.@endif
                @if($izinSiswaMenunggu > 0) {{ $izinSiswaMenunggu }} izin siswa menunggu wali kelas.@endif
            </span>
            <a href="{{ route('pengajuan.index', ['status' => 'menunggu']) }}" class="btn btn-sm btn-warning">Tinjau pengajuan</a>
        </div>
    @endif

    @if($kelasBelum->isNotEmpty())
        <div class="alert alert-info">
            <strong>{{ $kelasBelum->count() }} kelas belum diabsen hari ini:</strong>
            {{ $kelasBelum->map(fn ($k) => 'Kelas '.$k->nama.($k->waliKelas?->user ? ' ('.$k->waliKelas->user->name.')' : ''))->implode(', ') }}.
        </div>
    @endif

    {{-- Hari ini --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bx bx-group text-primary me-1"></i>Siswa hari ini</h6>
                    <p class="fs-5 mb-1"><strong>{{ $siswaTercatat }}</strong> dari {{ $totalSiswa }} siswa sudah diabsen</p>
                    @if($siswaTercatat > 0)
                        <p class="small text-muted">Kehadiran {{ round($hadirSiswa / $siswaTercatat * 100, 1) }}% (hadir dan terlambat)</p>
                    @else
                        <p class="small text-muted">Belum ada data absensi hari ini.</p>
                    @endif
                    <div class="sb-bar mb-3">
                        @foreach($statusSiswa as $k => $info)
                            @if(($siswaHariIni[$k] ?? 0) > 0)<span class="sb-st-{{ $k }}" style="width: {{ $persen($siswaHariIni[$k], $totalSiswa) }}%"></span>@endif
                        @endforeach
                    </div>
                    <div class="sb-counts">
                        @foreach($statusSiswa as $k => [$kode, $label])
                            <span class="sb-count sb-st-{{ $k }}"><i class="sb-dot"></i>{{ $label }} {{ $siswaHariIni[$k] ?? 0 }}</span>
                        @endforeach
                        <span class="sb-count sb-st-belum"><i class="sb-dot"></i>Belum {{ $siswaBelum }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bx bx-user-check text-success me-1"></i>Guru hari ini</h6>
                    <p class="fs-5 mb-1"><strong>{{ $guruTercatat }}</strong> dari {{ $totalGuru }} guru sudah tercatat</p>
                    <p class="small text-muted"><a href="{{ route('kehadiran-guru.index') }}">Lihat detail dan foto absen</a></p>
                    <div class="sb-bar mb-3">
                        @foreach($statusGuru as $k => $label)
                            @if(($guruHariIni[$k] ?? 0) > 0)<span class="sb-st-{{ $k }}" style="width: {{ $persen($guruHariIni[$k], $totalGuru) }}%"></span>@endif
                        @endforeach
                    </div>
                    <div class="sb-counts">
                        @foreach($statusGuru as $k => $label)
                            <span class="sb-count sb-st-{{ $k }}"><i class="sb-dot"></i>{{ $label }} {{ $guruHariIni[$k] ?? 0 }}</span>
                        @endforeach
                        <span class="sb-count sb-st-belum"><i class="sb-dot"></i>Belum {{ $guruBelum }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-bar-chart-alt-2 text-primary me-1"></i>Kehadiran siswa per hari, {{ $bulanTersedia[$bulan] }}</h6>
                </div>
                <div class="card-body px-3 pb-3">
                    @if(count($labelHarian) > 0)
                        <div id="sbChartHarian" style="min-height:300px;"></div>
                    @else
                        <div class="text-center text-muted py-5"><i class="bx bx-bar-chart-alt-2 fs-1 d-block mb-2"></i><small>Belum ada data absensi pada bulan ini.</small></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-line-chart text-success me-1"></i>Persentase kehadiran per bulan</h6>
                </div>
                <div class="card-body px-3 pb-3">
                    <div id="sbChartBulanan" style="min-height:300px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Per kelas dan perlu perhatian --}}
    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-layer text-success me-1"></i>Ringkasan per kelas, {{ $bulanTersedia[$bulan] }}</h6>
                </div>
                <div class="card-body p-0 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                            <thead>
                                <tr class="table-light">
                                    <th class="ps-4 py-2 border-0">Kelas</th>
                                    <th class="py-2 border-0" style="min-width:150px;">Kehadiran</th>
                                    <th class="py-2 border-0 text-center">Terlambat</th>
                                    <th class="py-2 border-0 text-center">Sakit</th>
                                    <th class="py-2 border-0 text-center">Izin</th>
                                    <th class="pe-4 py-2 border-0 text-center">Alpa</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($kelasRows as $r)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold">{{ $r['nama'] }}</span>
                                        <div class="small text-muted">{{ $r['wali'] ?: 'Belum ada wali' }}, {{ $r['siswa'] }} siswa</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="sb-bar flex-grow-1"><span style="width: {{ $r['persen'] ?? 0 }}%; background: {{ $warnaPersen($r['persen']) }}"></span></div>
                                            <span class="fw-semibold" style="min-width:48px;color: {{ $warnaPersen($r['persen']) }}">{{ $r['persen'] !== null ? $r['persen'].'%' : '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $r['terlambat'] }}</td>
                                    <td class="text-center">{{ $r['sakit'] }}</td>
                                    <td class="text-center">{{ $r['izin'] }}</td>
                                    <td class="pe-4 text-center fw-semibold" @if($r['alpa'] > 0) style="color:#B91C1C" @endif>{{ $r['alpa'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kelas pada tahun ajaran aktif.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold text-danger"><i class="bx bx-error-circle me-1"></i>Siswa perlu perhatian</h6>
                    <small class="text-muted">30 hari terakhir: alpa {{ $batas['alpa'] }} kali atau lebih, atau terlambat {{ $batas['terlambat'] }} kali atau lebih</small>
                </div>
                <div class="card-body px-4 pt-2">
                    @forelse($siswaMasalah as $s)
                        <div class="sb-item">
                            <div>
                                <div class="fw-semibold">{{ $s->nama }}</div>
                                <small class="text-muted">Kelas {{ $s->kelas }}</small>
                            </div>
                            <div class="sb-counts justify-content-end">
                                @if($s->alpa > 0)<span class="sb-count sb-st-alpa"><span class="sb-kode">A</span>{{ $s->alpa }}</span>@endif
                                @if($s->terlambat > 0)<span class="sb-count sb-st-terlambat"><span class="sb-kode">T</span>{{ $s->terlambat }}</span>@endif
                                @if($s->sakit_izin > 0)<span class="sb-count sb-st-sakit"><span class="sb-kode">S/I</span>{{ $s->sakit_izin }}</span>@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0 py-2">Tidak ada siswa yang melewati batas. 👍</p>
                    @endforelse
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <h6 class="mb-0 fw-bold text-warning"><i class="bx bx-time-five me-1"></i>Guru perlu perhatian</h6>
                    <small class="text-muted">{{ $bulanTersedia[$bulan] }}: terlambat {{ $batas['terlambat_guru'] }} kali atau lebih, atau pernah tanpa keterangan</small>
                </div>
                <div class="card-body px-4 pt-2">
                    @forelse($guruMasalah as $g)
                        <div class="sb-item">
                            <div class="fw-semibold">{{ $g->name }}</div>
                            <div class="sb-counts justify-content-end">
                                @if($g->tanpa_ket > 0)<span class="sb-count sb-st-tanpa_keterangan"><i class="sb-dot"></i>Tanpa ket. {{ $g->tanpa_ket }}</span>@endif
                                @if($g->terlambat > 0)<span class="sb-count sb-st-terlambat"><i class="sb-dot"></i>Terlambat {{ $g->terlambat }}</span>@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0 py-2">Tidak ada guru yang melewati batas. 👍</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if($ta)
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    var harian = document.querySelector('#sbChartHarian');
    if (harian) {
        new ApexCharts(harian, {
            series: {!! json_encode($seriesHarian) !!},
            chart: { type: 'bar', height: 300, stacked: true, toolbar: { show: false } },
            colors: ['#15803D', '#B45309', '#0E7490', '#6D28D9', '#B91C1C'],
            plotOptions: { bar: { columnWidth: '62%', borderRadius: 3 } },
            xaxis: { categories: {!! json_encode($labelHarian) !!} },
            yaxis: { labels: { formatter: function (v) { return Math.round(v); } } },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' }
        }).render();
    }

    var bulanan = document.querySelector('#sbChartBulanan');
    if (bulanan) {
        new ApexCharts(bulanan, {
            series: [
                { name: 'Siswa', data: {!! json_encode($persenSiswaBulan) !!} },
                { name: 'Guru', data: {!! json_encode($persenGuruBulan) !!} }
            ],
            chart: { type: 'line', height: 300, toolbar: { show: false } },
            colors: ['#2563C8', '#15803D'],
            stroke: { width: 3, curve: 'smooth' },
            markers: { size: 4 },
            xaxis: { categories: {!! json_encode($labelBulan) !!} },
            yaxis: { min: 0, max: 100, labels: { formatter: function (v) { return v + '%'; } } },
            tooltip: { y: { formatter: function (v) { return v === null ? '-' : v + '%'; } } },
            legend: { position: 'bottom' }
        }).render();
    }
});
</script>
@endif
@endpush