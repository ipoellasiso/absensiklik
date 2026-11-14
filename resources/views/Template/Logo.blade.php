{{-- Simpan di: resources/views/Template/Logo.blade.php
     Pakai: @include('Template.Logo')                    -> ikon + teks
            @include('Template.Logo', ['teks' => false]) -> ikon saja (sidebar mini)
     SVG inline, jadi tidak butuh Bootstrap Icons atau file gambar. --}}
@php($ukuran = $ukuran ?? 40)
<a href="{{ url('/') }}" class="d-inline-flex align-items-center text-decoration-none" style="gap:.6rem" aria-label="AbsenKlik">
    <svg width="{{ $ukuran }}" height="{{ $ukuran }}" viewBox="0 0 44 44" role="img" aria-hidden="true" style="flex:none">
        <defs>
            <linearGradient id="absenklik-g" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#3b82f6"/>
                <stop offset="1" stop-color="#1d4ed8"/>
            </linearGradient>
        </defs>
        <rect x="2" y="2" width="36" height="36" rx="11" fill="url(#absenklik-g)"/>
        <g fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 27V20a9 9 0 0 1 18 0v4"/>
            <path d="M15 28V20a5 5 0 0 1 10 0v6"/>
            <path d="M20 20v9"/>
        </g>
        <circle cx="35" cy="35" r="7" fill="#22c55e" stroke="#fff" stroke-width="2.5"/>
        <path d="M31.8 35.2l2.2 2.2 4-4.2" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    @if ($teks ?? true)
        <span style="font-weight:800;font-size:1.3rem;letter-spacing:-.02em;color:#0f1b3d;line-height:1">AbsenKlik</span>
    @endif
</a>