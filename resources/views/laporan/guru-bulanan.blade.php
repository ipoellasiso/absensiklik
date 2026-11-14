@php
    $kode = ['hadir' => 'H', 'terlambat' => 'T', 'sakit' => 'S', 'izin' => 'I', 'dinas_luar' => 'D', 'tanpa_keterangan' => 'TK'];
    $warna = ['hadir' => '#DCFCE7', 'terlambat' => '#FEF3C7', 'sakit' => '#CFFAFE', 'izin' => '#EDE9FE', 'dinas_luar' => '#DBEAFE', 'tanpa_keterangan' => '#FECACA'];
    $b = 'border:1px solid #444444;';
    $head = 'text-align:center;font-weight:bold;background-color:#E2E8F0;';
    $kolom = 2 + $hari->count() + 7;
@endphp
@include('laporan._pembuka')
<table>
    @include('laporan._kop', ['kolom' => $kolom])
    <tr>
        <th style="{{ $b }}{{ $head }}">No</th>
        <th style="{{ $b }}{{ $head }}">Nama</th>
        @foreach($hari as $d)
            <th style="{{ $b }}{{ $head }}">{{ $d->format('j') }}</th>
        @endforeach
        @foreach(['H', 'T', 'S', 'I', 'D', 'TK', '%'] as $h)
            <th style="{{ $b }}{{ $head }}">{{ $h }}</th>
        @endforeach
    </tr>
    @forelse($baris as $i => $r)
        <tr>
            <td style="{{ $b }}text-align:center;">{{ $i + 1 }}</td>
            <td style="{{ $b }}">{{ $r['nama'] }}</td>
            @foreach($hari as $d)
                @php($st = $r['sel'][$d->toDateString()] ?? null)
                <td style="{{ $b }}text-align:center;{{ $st ? 'background-color:'.$warna[$st].';' : '' }}">{{ $st ? $kode[$st] : '' }}</td>
            @endforeach
            @foreach(['hadir', 'terlambat', 'sakit', 'izin', 'dinas_luar', 'tanpa_keterangan'] as $st)
                <td style="{{ $b }}text-align:center;">{{ $r['hit'][$st] }}</td>
            @endforeach
            <td style="{{ $b }}text-align:center;font-weight:bold;">{{ $r['persen'] !== null ? $r['persen'] : '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ $kolom }}" style="{{ $b }}text-align:center;">Belum ada data guru.</td></tr>
    @endforelse
    <tr><td colspan="{{ $kolom }}">Keterangan: H hadir, T terlambat, S sakit, I izin, D dinas luar, TK tanpa keterangan. Persen = (H+T+D) dibagi hari efektif. Dicetak {{ $dicetak }}.</td></tr>
    @include('laporan._ttd', ['kolom' => $kolom, 'kanan' => 'Pembuat laporan', 'namaKanan' => auth()->user()->name, 'nipKanan' => null])
</table>
@include('laporan._penutup')