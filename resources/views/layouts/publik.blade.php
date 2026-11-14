{{-- Simpan di: resources/views/layouts/publik.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AbsenKlik - {{ $title ?? 'Halaman Login' }}</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--tinta:#0f1b3d;--biru:#2456e8;--biru-tua:#1639a8;--kertas:#f4f7fc;--garis:#dbe3f1;--redam:#5b6785;
--hadir:#16a34a;--sakit:#d97706;--izin:#0284c7;--alpa:#dc2626}
body{font-family:'Public Sans',system-ui,sans-serif;color:var(--tinta);background:var(--kertas)}
h1,h2,h3,.judul{font-family:'Bricolage Grotesque','Public Sans',sans-serif;letter-spacing:-.02em;font-weight:800}
.redam{color:var(--redam)}
:focus-visible{outline:3px solid #93b4ff;outline-offset:2px}
.logo{display:inline-flex;align-items:center;gap:.6rem;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:1.25rem;color:var(--tinta);text-decoration:none}
.logo-ikon{position:relative;width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;display:grid;place-items:center;font-size:1.3rem}
.logo-ikon .bi-check-circle-fill{position:absolute;right:-5px;bottom:-5px;font-size:.9rem;color:#22c55e;background:#fff;border-radius:50%;line-height:1}
.logo.putih{color:#fff}
.btn-biru{background:var(--biru);color:#fff;border:0;border-radius:10px;padding:.7rem 1.3rem;font-weight:600}
.btn-biru:hover{background:var(--biru-tua);color:#fff}
.btn-garis{border:1.5px solid var(--garis);background:#fff;color:var(--tinta);border-radius:10px;padding:.7rem 1.3rem;font-weight:600}
.btn-garis:hover{border-color:var(--biru);color:var(--biru)}
.form-control{border-radius:10px;border-color:var(--garis);padding:.65rem .85rem}
.form-control:focus{border-color:var(--biru);box-shadow:0 0 0 .2rem rgba(36,86,232,.15)}
.form-label{font-weight:600;font-size:.9rem}
.st{display:inline-grid;place-items:center;width:30px;height:30px;border-radius:8px;font-weight:700;font-size:.8rem;border:1.5px solid var(--garis);color:var(--redam);background:#fff}
.st.on{color:#fff;border-color:transparent}
.st-h.on{background:var(--hadir)}.st-s.on{background:var(--sakit)}.st-i.on{background:var(--izin)}.st-a.on{background:var(--alpa)}
.panel-auth{background:linear-gradient(160deg,#1d4ed8,#1639a8 70%);color:#fff}
@media (prefers-reduced-motion:no-preference){.st.on{animation:cap .35s ease-out both;animation-delay:calc(var(--n)*.12s + .2s)}}
@keyframes cap{from{transform:scale(1.5);opacity:0}to{transform:scale(1);opacity:1}}
</style>
@stack('head')
</head>
<body>
@yield('content')
</body>
</html>