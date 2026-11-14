<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Absensi Siswa</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111; background: #f1f3f6; }
        .toolbar { padding: 12px 16px; background: #fff; border-bottom: 1px solid #ddd; display: flex; gap: 12px; align-items: center; }
        .toolbar button { padding: 8px 16px; font-size: 14px; cursor: pointer; }
        .toolbar span { font-size: 13px; color: #555; }
        .sheet { display: flex; flex-wrap: wrap; gap: 6mm 8mm; width: 190mm; margin: 10mm auto; }
        .kartu {
            width: 85.6mm; height: 54mm; padding: 4mm; display: flex; gap: 4mm;
            border: .3mm solid #222; border-radius: 3mm; background: #fff;
            break-inside: avoid; page-break-inside: avoid;
        }
        .info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .sekolah { font-size: 7pt; font-weight: 700; line-height: 1.2; }
        .judul { font-size: 6.5pt; color: #444; margin-top: 1mm; }
        .nama { margin-top: auto; font-size: 11pt; font-weight: 700; line-height: 1.15; word-break: break-word; }
        .meta { font-size: 8pt; margin-top: 1.5mm; line-height: 1.35; }
        .qr { width: 30mm; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .qr svg { width: 30mm; height: 30mm; }
        .qr small { font-size: 6pt; margin-top: 1mm; color: #444; text-align: center; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Cetak kartu</button>
        <span>{{ $siswa->count() }} kartu. Di jendela cetak: ukuran A4, skala 100%, matikan "Header dan footer".</span>
    </div>

    @if($siswa->isEmpty())
        <p style="padding:20px">Tidak ada siswa aktif untuk dicetak.</p>
    @endif

    <div class="sheet">
        @foreach($siswa as $s)
            <div class="kartu">
                <div class="info">
                    <div class="sekolah">{{ $sekolah }}</div>
                    <div class="judul">Kartu Absensi Siswa{{ $tahun ? ' '.$tahun : '' }}</div>
                    <div class="nama">{{ $s->nama }}</div>
                    <div class="meta">
                        @if($s->nisn)NISN {{ $s->nisn }}<br>@endif
                        @if($s->kelas)Kelas {{ $s->kelas }}@endif
                    </div>
                </div>
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(113)->margin(0)->generate($s->qr_token) !!}
                    <small>Pindai saat tiba di sekolah</small>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>