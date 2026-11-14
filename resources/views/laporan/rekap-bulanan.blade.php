@php
    $kode = ['hadir' => 'H', 'terlambat' => 'T', 'sakit' => 'S', 'izin' => 'I', 'alpa' => 'A'];
    $warna = ['hadir' => '#DCFCE7', 'terlambat' => '#FEF3C7', 'sakit' => '#CFFAFE', 'izin' => '#EDE9FE', 'alpa' => '#FECACA'];
    $b = 'border:1px solid #444444;';
    $kolom = 3 + $hari->count() + 6;
@endphp
@include('laporan._pembuka')
<table>
    @include('laporan._kop', ['kolom' => $kolom])
    <tr>
        <th style="{{ $b }}text-align:center;font-weight:bold;background-color:#E2E8F0;">No</th>
        <th style="{{ $b }}text-align:left;font-weight:bold;background-color:#E2E8F0;">Nama</th>
        <th style="{{ $b }}text-align:center;font-weight:bold;background-color:#E2E8F0;">L/P</th>
        @foreach($hari as $d)
            <th style="{{ $b }}text-align:center;font-weight:bold;background-color:#E2E8F0;">{{ $d->format('j') }}</th>
        @endforeach
        @foreach(['H', 'T', 'S', 'I', 'A', '%'] as $h)
            <th style="{{ $b }}text-align:center;font-weight:bold;background-color:#E2E8F0;">{{ $h }}</th>
        @endforeach
    </tr>
    @forelse($baris as $i => $r)
        <tr>
            <td style="{{ $b }}text-align:center;">{{ $i + 1 }}</td>
            <td style="{{ $b }}">{{ $r['nama'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['jk'] }}</td>
            @foreach($hari as $d)
                @php($st = $r['sel'][$d->toDateString()] ?? null)
                <td style="{{ $b }}text-align:center;{{ $st ? 'background-color:'.$warna[$st].';' : '' }}">{{ $st ? $kode[$st] : '' }}</td>
            @endforeach
            @foreach(['hadir', 'terlambat', 'sakit', 'izin', 'alpa'] as $st)
                <td style="{{ $b }}text-align:center;">{{ $r['hit'][$st] }}</td>
            @endforeach
            <td style="{{ $b }}text-align:center;font-weight:bold;">{{ $r['persen'] !== null ? $r['persen'] : '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ $kolom }}" style="{{ $b }}text-align:center;">Belum ada siswa pada kelas ini.</td></tr>
    @endforelse
    <tr>
        <td colspan="3" style="{{ $b }}font-weight:bold;">Jumlah hadir (H+T)</td>
        @foreach($hari as $d)
            @php($n = collect($baris)->filter(fn ($r) => in_array($r['sel'][$d->toDateString()] ?? null, ['hadir', 'terlambat'], true))->count())
            <td style="{{ $b }}text-align:center;font-weight:bold;">{{ $n }}</td>
        @endforeach
        <td colspan="6" style="{{ $b }}"></td>
    </tr>
    <tr><td colspan="{{ $kolom }}">Keterangan: H hadir, T terlambat, S sakit, I izin, A alpa tanpa keterangan. Persen = (H+T) dibagi hari efektif. Dicetak {{ $dicetak }}.</td></tr>
    @include('laporan._ttd', ['kolom' => $kolom, 'kanan' => 'Wali Kelas', 'namaKanan' => $waliKelas, 'nipKanan' => $waliNip])
</table>
@include('laporan._penutup')