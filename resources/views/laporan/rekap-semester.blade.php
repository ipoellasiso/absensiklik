@php
    $b = 'border:1px solid #444444;';
    $kolom = 11;
    $head = 'text-align:center;font-weight:bold;background-color:#E2E8F0;';
@endphp
@include('laporan._pembuka')
<table>
    @include('laporan._kop', ['kolom' => $kolom])
    <tr>
        <th style="{{ $b }}{{ $head }}">No</th>
        <th style="{{ $b }}{{ $head }}">Nama</th>
        <th style="{{ $b }}{{ $head }}">NISN</th>
        <th style="{{ $b }}{{ $head }}">L/P</th>
        <th style="{{ $b }}{{ $head }}">Hadir</th>
        <th style="{{ $b }}{{ $head }}">Terlambat</th>
        <th style="{{ $b }}{{ $head }}">Sakit</th>
        <th style="{{ $b }}{{ $head }}">Izin</th>
        <th style="{{ $b }}{{ $head }}">Alpa</th>
        <th style="{{ $b }}{{ $head }}">Hari efektif</th>
        <th style="{{ $b }}{{ $head }}">Kehadiran (%)</th>
    </tr>
    @forelse($rows as $i => $r)
        <tr>
            <td style="{{ $b }}text-align:center;">{{ $i + 1 }}</td>
            <td style="{{ $b }}">{{ $r['nama'] }}</td>
            <td style="{{ $b }}">{{ $r['nisn'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['jk'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['hadir'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['terlambat'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['sakit'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['izin'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['alpa'] }}</td>
            <td style="{{ $b }}text-align:center;">{{ $r['hari_efektif'] }}</td>
            <td style="{{ $b }}text-align:center;font-weight:bold;">{{ $r['persen'] }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ $kolom }}" style="{{ $b }}text-align:center;">Belum ada siswa pada kelas ini.</td></tr>
    @endforelse
    <tr><td colspan="{{ $kolom }}">Persen = (hadir + terlambat) dibagi hari efektif. Dicetak {{ $dicetak }}.</td></tr>
    @include('laporan._ttd', ['kolom' => $kolom, 'kanan' => 'Wali Kelas', 'namaKanan' => $waliKelas, 'nipKanan' => $waliNip])
</table>
@include('laporan._penutup')