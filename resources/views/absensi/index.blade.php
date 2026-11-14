@extends('Template.Layout')

@php
    $breadcumd = 'Absensi Siswa';
    $status = ['hadir' => ['H', 'Hadir'], 'terlambat' => ['T', 'Terlambat'], 'sakit' => ['S', 'Sakit'], 'izin' => ['I', 'Izin'], 'alpa' => ['A', 'Alpa']];
@endphp

@section('content')
@include('partials.siabsen-style')
@include('partials.pesan')

<form method="GET" class="row g-2 mb-3">
    <div class="col-sm-6 col-md-4">
        <label for="kelas_id" class="visually-hidden">Kelas</label>
        <select id="kelas_id" name="kelas_id" class="form-select" onchange="this.form.submit()">
            @foreach($kelasList as $k)
                <option value="{{ $k->id }}" @selected($kelas && $kelas->id === $k->id)>Kelas {{ $k->nama }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-3">
        <label for="tanggal" class="visually-hidden">Tanggal</label>
        <input id="tanggal" type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()">
    </div>
</form>

@if(! $kelas)
    <div class="alert alert-warning">Anda belum menjadi wali kelas pada tahun ajaran aktif. Minta admin menetapkan kelas Anda.</div>
@else
    @if($hariIni)
        <form method="POST" action="{{ route('absensi.scan') }}" class="card border-0 shadow-sm card-body mb-3">
            @csrf
            <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
            <label for="qr_token" class="form-label mb-1 fw-semibold">Scan kartu siswa</label>
            <div class="input-group">
                <input id="qr_token" name="qr_token" class="form-control" placeholder="Arahkan scanner ke kolom ini" autocomplete="off">
                <button class="btn btn-outline-primary">Catat</button>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ route('absensi.store') }}">
        @csrf
        <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
        <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3">
                <h6 class="mb-0 fw-bold">Kelas {{ $kelas->nama }}, {{ $tanggal->translatedFormat('l d F Y') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-success" id="semua-hadir">Tandai semua hadir</button>
            </div>

            <div class="d-sm-none px-3 py-2 small border-bottom">
                @foreach($status as $k => [$kode, $label])
                    <span class="sb-count sb-st-{{ $k }} me-2"><span class="sb-kode">{{ $kode }}</span>{{ $label }}</span>
                @endforeach
            </div>

            <div class="sb-reg-head d-none d-md-grid">
                <div>No</div><div>Nama</div><div>Status</div><div>Catatan</div>
            </div>

            @forelse($siswa as $i => $s)
                @php($a = $absensi->get($s->id))
                @php($cur = old('status.'.$s->id, $a->status ?? 'hadir'))
                <div class="sb-reg-row">
                    <div class="text-muted">{{ $i + 1 }}</div>
                    <div class="sb-reg-name">
                        {{ $s->nama }}
                        @if($a && $a->sumber !== 'manual')<span class="sb-tag">{{ $a->sumber === 'qr' ? 'Scan QR' : 'Dari pengajuan' }}</span>@endif
                        @if($a && $a->jam_masuk)<span class="sb-time">{{ substr($a->jam_masuk, 0, 5) }}</span>@endif
                    </div>
                    <div class="sb-group" role="radiogroup" aria-label="Status {{ $s->nama }}">
                        @foreach($status as $k => [$kode, $label])
                            <input type="radio" class="sb-input" name="status[{{ $s->id }}]" id="s{{ $s->id }}_{{ $k }}" value="{{ $k }}" @checked($cur === $k)>
                            <label class="sb-chip sb-st-{{ $k }}" for="s{{ $s->id }}_{{ $k }}" title="{{ $label }}"><span class="sb-kode">{{ $kode }}</span><span class="sb-label">{{ $label }}</span></label>
                        @endforeach
                    </div>
                    <div class="sb-note">
                        <input name="catatan[{{ $s->id }}]" value="{{ old('catatan.'.$s->id, $a->catatan ?? '') }}" class="form-control form-control-sm" maxlength="255" placeholder="Catatan" aria-label="Catatan untuk {{ $s->nama }}">
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-muted">Belum ada siswa di kelas ini. Minta admin memasukkan siswa ke kelas.</div>
            @endforelse

            @if($siswa->isNotEmpty())
                <div class="sb-save">
                    <div class="sb-tally" id="sbTally" aria-live="polite"></div>
                    <button class="btn btn-primary">Simpan absensi</button>
                </div>
            @endif
        </div>
    </form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var labels = { hadir: 'Hadir', terlambat: 'Terlambat', sakit: 'Sakit', izin: 'Izin', alpa: 'Alpa' };
    var tally = document.getElementById('sbTally');

    function hitung() {
        if (!tally) return;
        var c = { hadir: 0, terlambat: 0, sakit: 0, izin: 0, alpa: 0 };
        document.querySelectorAll('.sb-input:checked').forEach(function (i) { c[i.value]++; });
        tally.innerHTML = Object.keys(labels).map(function (k) {
            return '<span class="sb-tally-item sb-st-' + k + '"><i class="sb-dot"></i>' + labels[k] + ' <b>' + c[k] + '</b></span>';
        }).join('');
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList && e.target.classList.contains('sb-input')) hitung();
    });

    var semua = document.getElementById('semua-hadir');
    if (semua) semua.addEventListener('click', function () {
        document.querySelectorAll('.sb-input[value=hadir]').forEach(function (r) { r.checked = true; });
        hitung();
    });

    hitung();
})();
</script>
@endpush
