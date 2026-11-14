<tr><td colspan="{{ $kolom }}" style="font-size:14pt;font-weight:bold;text-align:center;">{{ $sekolah }}</td></tr>
<tr><td colspan="{{ $kolom }}" style="font-size:11pt;font-weight:bold;text-align:center;">{{ $judul }}</td></tr>
@foreach($info as $baris)
    <tr><td colspan="{{ $kolom }}" style="text-align:center;">{{ $baris }}</td></tr>
@endforeach
<tr><td colspan="{{ $kolom }}">&nbsp;</td></tr>