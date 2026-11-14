<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIABSEN SD')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-md navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">SIABSEN SD</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                @if(auth()->user()->hasRole('guru', 'admin'))
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('absensi.*') ? 'active' : '' }}" href="{{ route('absensi.index') }}">Absensi Siswa</a></li>
                @endif
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('pengajuan.*') ? 'active' : '' }}" href="{{ route('pengajuan.index') }}">Izin / Sakit</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 text-white">
                <span class="small">{{ auth()->user()->name }} <span class="badge text-bg-light">{{ str_replace('_', ' ', auth()->user()->role) }}</span></span>
                <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn btn-sm btn-outline-light">Keluar</button></form>
            </div>
        </div>
    </div>
</nav>

<main class="container pb-5">
    @if(session('sukses'))<div class="alert alert-success">{{ session('sukses') }}</div>@endif
    @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
