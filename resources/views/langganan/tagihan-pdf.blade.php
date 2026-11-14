<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }} {{ $t->nomor }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 20pt; margin: 0 0 4pt; letter-spacing: 1px; }
        table { border-collapse: collapse; width: 100%; }
        .rincian th, .rincian td { border: 1px solid #444; padding: 6pt 8pt; }
        .rincian th { background: #E2E8F0; text-align: left; }
        .lunas { color: #15803D; border: 2px solid #15803D; padding: 4pt 14pt; font-weight: bold; font-size: 14pt; display: inline-block; }
        .kecil { font-size: 8.5pt; color: #555; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width:60%;vertical-align:top;">
                <h1>{{ $judul }}</h1>
                <strong>{{ $penerbit }}</strong><br>
                <span class="kecil">{{ $kontak }}</span>
            </td>
            <td style="vertical-align:top;text-align:right;">
                Nomor: <strong>{{ $t->nomor }}</strong><br>
                Tanggal: {{ $t->created_at->translatedFormat('d F Y') }}
            </td>
        </tr>
    </table>

    <p style="margin-top:18pt;">Kepada:<br><strong>{{ $sekolah->nama }}</strong>@if($sekolah->npsn)<br>NPSN {{ $sekolah->npsn }}@endif</p>

    <table class="rincian" style="margin-top:8pt;">
        <tr><th>Uraian</th><th style="width:28%;text-align:right;">Jumlah</th></tr>
        <tr>
            <td>
                Langganan layanan absensi SIABSEN SD<br>
                Paket {{ $t->paket_nama }} (sampai {{ $t->batas_siswa }} siswa aktif), {{ $t->lama_bulan }} bulan<br>
                <span class="kecil">
                    @if($t->status === 'lunas')
                        Periode {{ $t->periode_mulai->translatedFormat('d F Y') }} sampai {{ $t->periode_selesai->translatedFormat('d F Y') }}
                    @else
                        Periode dihitung sejak pembayaran dikonfirmasi
                    @endif
                </span>
            </td>
            <td style="text-align:right;">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
        </tr>
        <tr><td style="text-align:right;"><strong>Total</strong></td><td style="text-align:right;"><strong>Rp {{ number_format($t->jumlah, 0, ',', '.') }}</strong></td></tr>
    </table>

    @if($t->status === 'lunas')
        <p style="margin-top:20pt;"><span class="lunas">LUNAS</span><br>
        <span class="kecil">Diterima {{ $t->dibayar_pada?->translatedFormat('d F Y') }}</span></p>
    @elseif($t->status === 'menunggu')
        <p style="margin-top:18pt;"><strong>Cara pembayaran</strong><br>
        Transfer ke {{ $rekening['bank'] }}, nomor <strong>{{ $rekening['nomor'] }}</strong> a.n. {{ $rekening['atas_nama'] }}.<br>
        Cantumkan nomor tagihan {{ $t->nomor }} dan unggah bukti transfer di menu Langganan.</p>
    @else
        <p style="margin-top:18pt;"><strong>DIBATALKAN</strong></p>
    @endif

    <table style="margin-top:36pt;">
        <tr><td style="width:60%;"></td><td style="text-align:center;">Hormat kami,<br><br><br><br><strong>{{ $penerbit }}</strong></td></tr>
    </table>
</body>
</html>