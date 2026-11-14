@php($separuh = intdiv($kolom, 2))
<tr><td colspan="{{ $kolom }}">&nbsp;</td></tr>
<tr>
    <td colspan="{{ $separuh }}" style="text-align:center;">Mengetahui,<br>Kepala Sekolah</td>
    <td colspan="{{ $kolom - $separuh }}" style="text-align:center;">{{ $kanan }}</td>
</tr>
<tr><td colspan="{{ $kolom }}">&nbsp;<br>&nbsp;<br>&nbsp;</td></tr>
<tr>
    <td colspan="{{ $separuh }}" style="text-align:center;font-weight:bold;">{{ $kepsek }}<br>{{ $kepsekNip ? 'NIP '.$kepsekNip : '' }}</td>
    <td colspan="{{ $kolom - $separuh }}" style="text-align:center;font-weight:bold;">{{ $namaKanan ?: '..............................' }}<br>{{ !empty($nipKanan) ? 'NIP '.$nipKanan : '' }}</td>
</tr>