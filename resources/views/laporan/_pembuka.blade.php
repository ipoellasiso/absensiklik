@if(! $untukExcel)
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #111; }
        table { border-collapse: collapse; width: 100%; }
    </style>
</head>
<body>
@endif