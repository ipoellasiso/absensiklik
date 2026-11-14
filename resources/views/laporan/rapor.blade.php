<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kehadiran {{ $siswa->nama }}</title>
    <style>
        @page { margin: 18mm 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 13pt; text-align: center; margin: 0; }
        h2 { font-size: 11pt; text-align: center; margin: 2pt 0 14pt; font-weight: normal; }
        table { border-collapse: collapse; width: 100%; }
        .identitas td { padding: 2pt 0; }
        .kehadiran th, .kehadiran td { border: 1px solid #444; padding: 5pt 8pt; }
        .kehadiran th { background: #E2E8F0; text-align: left; }
        .ttd td { text-align: center; padding-top: 4pt; }
    </style>
</head>
<body>
    <h1>{{ $sekolah }}</h1>
    <h2>LAPORAN KEHADIRAN SISWA, SEMESTER {{ strtoupper($semester->nama) }} TAHUN AJARAN {{ $semester->tahunAjaran->nama }}</h2>

    <table class="identitas" style="margin-bottom:14pt;">
        <tr><td style="width:28%;">Nama siswa</td><td>: {{ $siswa->nama }}</td></tr>
        <tr><td>NISN</td><td>: {{ $siswa->nisn ?: '-' }}</td></tr>
        <tr><td>Kelas</td><td>: {{ $kelas->nama }}</td></tr>
    </table>

    <table class="kehadiran">
        <tr><th>Keterangan</th><th style="width:25%;text-align:center;">Jumlah (hari)</th></tr>
        <tr><td>Sakit</td><td style="text-align:center;">{{ $row['sakit'] }}</td></tr>
        <tr><td>Izin</td><td style="text-align:center;">{{ $row['izin'] }}</td></tr>
        <tr><td>Tanpa keterangan (alpa)</td><td style="text-align:center;">{{ $row['alpa'] }}</td></tr>
        <tr><td>Terlambat</td><td style="text-align:center;">{{ $row['terlambat'] }}</td></tr>
        <tr>
            <td><strong>Kehadiran</strong> (hadir dan terlambat)</td>
            <td style="text-align:center;"><strong>{{ $row['hadir'] + $row['terlambat'] }} dari {{ $row['hari_efektif'] }} hari ({{ $row['persen'] }}%)</strong></td>
        </tr>
    </table>

    <p style="font-size:8.5pt;color:#555;margin-top:8pt;">
        {{ $row['sumber'] === 'final' ? 'Data final (semester telah dikunci).' : 'Data sementara (semester belum dikunci).' }}
        Dicetak {{ $dicetak }}.
    </p>

    <table class="ttd" style="margin-top:28pt;">
        <tr>
            <td style="width:33%;">Mengetahui,<br>Orang tua/wali</td>
            <td style="width:34%;">Wali kelas</td>
            <td style="width:33%;">Kepala sekolah</td>
        </tr>
        <tr><td colspan="3">&nbsp;<br>&nbsp;<br>&nbsp;</td></tr>
        <tr>
            <td>..............................</td>
            <td><strong>{{ $waliKelas ?: '..............................' }}</strong><br>{{ $waliNip ? 'NIP '.$waliNip : '' }}</td>
            <td><strong>{{ $kepsek }}</strong><br>{{ $kepsekNip ? 'NIP '.$kepsekNip : '' }}</td>
        </tr>
    </table>
</body>
</html>