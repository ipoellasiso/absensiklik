@php
    $role    = auth()->user()->role ?? '';
    $isAdmin = $role === 'admin';
    $isGuru  = $role === 'guru';

    $canAbsensi = $isAdmin || $isGuru;
    $isKepsek = $role === 'kepala_sekolah';
    $isSuper = $role === 'super_admin';
@endphp

<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">

            <li class="{{ request()->routeIs('dashboard') ? 'mm-active' : '' }}">
                <a href="{{ route('dashboard') }}" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-025-dashboard"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            @if($isAdmin)
            <li class="{{ request()->routeIs('admin.*') ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="bx bxs-cog"></i><span class="nav-text">Data Master</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="{{ route('admin.pengaturan.edit') }}">Pengaturan Sekolah</a></li>
                    <li><a href="{{ route('admin.tahun-ajaran.index') }}">Tahun Ajaran</a></li>
                    <li><a href="{{ route('admin.guru.index') }}">Guru dan Staf</a></li>
                    <li><a href="{{ route('admin.kelas.index') }}">Kelas</a></li>
                    <li><a href="{{ route('admin.siswa.index') }}">Siswa</a></li>
                    <li><a href="{{ route('admin.orang-tua.index') }}">Orang Tua</a></li>
                    <li><a href="{{ route('admin.naik-kelas.index') }}">Naik Kelas</a></li>
                    <li><a href="{{ route('admin.hari-libur.index') }}">Hari Libur</a></li>
                </ul>
            </li>
            @endif

            @if($canAbsensi)
            <li class="{{ request()->routeIs('absensi.*') ? 'mm-active' : '' }}">
                <a href="{{ route('absensi.index') }}" class="ai-icon" aria-expanded="false">
                    <i class="bx bx-list-check"></i>
                    <span class="nav-text">Absensi Siswa</span>
                </a>
            </li>
            @endif

            @if(! $isSuper)
            <li class="{{ request()->routeIs('pengajuan.*') ? 'mm-active' : '' }}">
                <a href="{{ route('pengajuan.index') }}" class="ai-icon" aria-expanded="false">
                    <i class="bx bx-envelope"></i>
                    <span class="nav-text">Izin / Sakit</span>
                </a>
            </li>
            @endif

            @if($isAdmin || $isKepsek)
            <li class="{{ request()->routeIs('kehadiran-guru.*') ? 'mm-active' : '' }}">
                <a href="{{ route('kehadiran-guru.index') }}" class="ai-icon" aria-expanded="false">
                    <i class="bx bx-user-check"></i><span class="nav-text">Kehadiran Guru</span>
                </a>
            </li>
            @endif

            {{-- @if($role !== 'orang_tua') --}}
            @if(! in_array($role, ['orang_tua', 'super_admin']))
            <li class="{{ request()->routeIs('laporan.*') ? 'mm-active' : '' }}">
                <a href="{{ route('laporan.index') }}" class="ai-icon" aria-expanded="false">
                    <i class="bx bx-file"></i><span class="nav-text">Laporan</span>
                </a>
            </li>
            @endif

            {{-- @if($isSuper)
            <li class="{{ request()->routeIs('platform.*') ? 'mm-active' : '' }}">
                <a href="{{ route('platform.sekolah.index') }}" class="ai-icon" aria-expanded="false">
                    <i class="bx bx-building-house"></i><span class="nav-text">Sekolah</span>
                </a>
            </li>
            @endif --}}

            @if($isSuper)
            <li class="{{ request()->routeIs('platform.sekolah.*') ? 'mm-active' : '' }}"><a href="{{ route('platform.sekolah.index') }}" class="ai-icon" aria-expanded="false"><i class="bx bx-building-house"></i><span class="nav-text">Sekolah</span></a></li>
            <li class="{{ request()->routeIs('platform.paket.*') ? 'mm-active' : '' }}"><a href="{{ route('platform.paket.index') }}" class="ai-icon" aria-expanded="false"><i class="bx bx-package"></i><span class="nav-text">Paket</span></a></li>
            <li class="{{ request()->routeIs('platform.tagihan.*') ? 'mm-active' : '' }}"><a href="{{ route('platform.tagihan.index') }}" class="ai-icon" aria-expanded="false"><i class="bx bx-receipt"></i><span class="nav-text">Pembayaran</span></a></li>
            <li class="{{ request()->routeIs('platform.libur-nasional.*') ? 'mm-active' : '' }}"><a href="{{ route('platform.libur-nasional.index') }}" class="ai-icon" aria-expanded="false"><i class="bx bx-calendar-star"></i><span class="nav-text">Libur Nasional</span></a></li>
            @endif

            @if($isAdmin)
            <li class="{{ request()->routeIs('langganan.*') ? 'mm-active' : '' }}"><a href="{{ route('langganan.index') }}" class="ai-icon" aria-expanded="false"><i class="bx bx-credit-card"></i><span class="nav-text">Langganan</span></a></li>
            @endif

        </ul>
    </div>
</div>