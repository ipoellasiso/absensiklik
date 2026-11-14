{{-- Simpan di: resources/views/admin/kartu.blade.php (timpa). Foto siswa sejajar dengan blok teks, di tengah kartu. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Absensi Siswa</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111; background: #f1f3f6; }
        .toolbar { padding: 12px 16px; background: #fff; border-bottom: 1px solid #ddd; display: flex; gap: 12px; align-items: center; }
        .toolbar button { padding: 8px 16px; font-size: 14px; cursor: pointer; }
        .toolbar span { font-size: 13px; color: #555; }
        .sheet { display: flex; flex-wrap: wrap; gap: 6mm 8mm; width: 190mm; margin: 10mm auto; }

        /* Kartu: foto, teks, dan QR sama-sama di tengah secara vertikal */
        .kartu {
            width: 85.6mm; height: 54mm; padding: 0 4mm; display: flex; align-items: center; gap: 3mm;
            border: .3mm solid #222; border-radius: 3mm; background: #fff;
            break-inside: avoid; page-break-inside: avoid;
        }
        /* Foto dan blok teks punya tinggi yang sama (28mm): tepi atas = "SD NEGERI", tepi bawah = baris kelas */
        .foto {
            width: 21mm; height: 28mm; flex: none; overflow: hidden;
            border: .25mm solid #b9c0cc; border-radius: 1.5mm; background: #eef1f5;
            display: flex; align-items: center; justify-content: center;
        }
        .foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .foto svg { width: 12mm; height: 12mm; fill: #9aa3b2; }
        .info { flex: 1; min-width: 0; height: 28mm; display: flex; flex-direction: column; justify-content: space-between; }
        .sekolah { font-size: 6.5pt; font-weight: 700; line-height: 1.15; }
        .judul { font-size: 6pt; color: #444; margin-top: .8mm; line-height: 1.2; }
        .nama { font-size: 9.5pt; font-weight: 700; line-height: 1.15; word-break: break-word; }
        .meta { font-size: 7pt; margin-top: 1mm; line-height: 1.3; }
        .qr { width: 25mm; flex: none; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .qr svg { width: 25mm; height: 25mm; }
        .qr small { font-size: 5.5pt; margin-top: 1mm; color: #444; text-align: center; }
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
                <div class="foto">
                    @if($s->foto)
                        <img src="{{ route('admin.siswa.foto', ['siswa' => $s->id, 'v' => strtotime($s->updated_at)]) }}" alt="Foto {{ $s->nama }}">
                    @else
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4.4 0-8 2.2-8 5v3h16v-3c0-2.8-3.6-5-8-5z"/></svg>
                    @endif
                </div>
                <div class="info">
                    <div>
                        <div class="sekolah">{{ $sekolah }}</div>
                        <div class="judul">Kartu Absensi Siswa{{ $tahun ? ' '.$tahun : '' }}</div>
                    </div>
                    <div>
                        <div class="nama">{{ $s->nama }}</div>
                        <div class="meta">
                            @if($s->nisn)NISN {{ $s->nisn }}<br>@endif
                            @if($s->kelas){{ \Illuminate\Support\Str::startsWith($s->kelas, 'Kelas') ? $s->kelas : 'Kelas '.$s->kelas }}@endif
                        </div>
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