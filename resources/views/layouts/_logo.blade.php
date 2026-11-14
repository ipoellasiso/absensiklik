{{-- Simpan di: resources/views/layouts/_logo.blade.php. Pakai: @include('layouts._logo', ['putih' => true]) --}}
<a href="{{ url('/') }}" class="logo {{ !empty($putih) ? 'putih' : '' }}">
    <span class="logo-ikon"><i class="bi bi-fingerprint"></i><i class="bi bi-check-circle-fill"></i></span>
    AbsenKlik
</a>