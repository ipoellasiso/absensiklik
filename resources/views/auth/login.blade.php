{{-- Simpan di: resources/views/auth/login.blade.php (timpa). Memakai layouts/publik.blade.php dan layouts/_logo.blade.php. --}}
@extends('layouts.publik')

@push('head')
<style>
body{background:linear-gradient(135deg,#dfe7f7,#eef2fb 55%,#e3ebfb);min-height:100vh}
.kartu-login{max-width:1080px;margin:0 auto;border-radius:26px;overflow:hidden;background:#fff;box-shadow:0 30px 70px -30px rgba(15,27,61,.35)}
.sisi-kiri{position:relative;overflow:hidden;background:linear-gradient(160deg,#dbe8ff,#eef4ff 60%,#e4eeff);min-height:620px}
.sisi-kiri::before{content:"";position:absolute;left:-110px;bottom:-130px;width:340px;height:340px;border-radius:50%;background:rgba(36,86,232,.1)}
.sisi-kanan{position:relative}
.sisi-kanan::before{content:"";position:absolute;top:18px;right:18px;width:84px;height:34px;opacity:.35;background:repeating-linear-gradient(135deg,var(--biru) 0 2px,transparent 2px 9px)}
.o{color:#f97316}.b{color:var(--biru)}
.panggung{position:relative;width:300px;height:380px;margin:0 auto}
.hp{position:absolute;left:50%;top:14px;transform:translateX(-50%);width:215px;height:345px;border-radius:34px;background:#fff;border:7px solid #fff;box-shadow:0 26px 50px -18px rgba(15,27,61,.4);overflow:hidden}
.hp-atas{background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;padding:.9rem 1rem;font-weight:700;font-size:.9rem;display:flex;justify-content:space-between}
.hp-baris{display:flex;align-items:center;justify-content:space-between;padding:.55rem .85rem;border-bottom:1px solid var(--garis);font-size:.78rem;font-weight:500}
.hp .st{width:22px;height:22px;font-size:.62rem;border-radius:6px}
.hp-tombol{position:absolute;left:14px;right:14px;bottom:14px;background:var(--biru);color:#fff;text-align:center;border-radius:12px;padding:.55rem;font-size:.8rem;font-weight:600}
.ubin{position:absolute;width:54px;height:54px;border-radius:16px;background:#fff;display:grid;place-items:center;font-size:1.5rem;box-shadow:0 14px 28px -12px rgba(15,27,61,.4)}
.ubin .titik{position:absolute;top:9px;right:9px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid #fff}
.toast-kirim{position:absolute;left:-6px;bottom:52px;background:#16a34a;color:#fff;border-radius:12px;padding:.5rem .8rem;font-size:.78rem;font-weight:600;box-shadow:0 14px 26px -10px rgba(22,163,74,.6);display:flex;gap:.4rem;align-items:center}
.input-group-text{background:#fff;border-color:var(--garis);color:var(--redam);border-radius:10px 0 0 10px}
.input-group .form-control{border-radius:0}
.input-group .form-control:last-child{border-radius:0 10px 10px 0}
.input-group .btn-mata{border:1px solid var(--garis);border-left:0;background:#fff;color:var(--redam);border-radius:0 10px 10px 0}
.btn-masuk.memuat .spin{display:inline-block}.btn-masuk .spin{display:none}
@media (prefers-reduced-motion:no-preference){
  .ubin{animation:melayang 5s ease-in-out infinite;animation-delay:calc(var(--d)*-1.3s)}
  .toast-kirim{animation:toast 5s ease-out infinite}
  .kartu-login{animation:masuk .7s cubic-bezier(.2,.8,.2,1) both}
}
@keyframes melayang{0%,100%{transform:translateY(0) rotate(-2deg)}50%{transform:translateY(-11px) rotate(2deg)}}
@keyframes toast{0%,35%{opacity:0;transform:translateY(14px) scale(.9)}45%{opacity:1;transform:none}88%{opacity:1}100%{opacity:0}}
@keyframes masuk{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
</style>
@endpush

@section('content')
<div class="container py-lg-5 px-lg-4">
<div class="kartu-login row g-0">

    <div class="col-lg-6 sisi-kiri d-none d-lg-block p-5">
        <h2 class="judul fs-4 mb-4" style="max-width:21rem;line-height:1.3">Absen kelas jadi lebih cepat iyokahhh, <span class="o">orang tua</span> dan <span class="b">guru</span> selalu terhubung.</h2>

        <div class="panggung" aria-hidden="true">
            <div class="hp">
                <div class="hp-atas"><span>Kelas 3A</span><span>07.15</span></div>
                @foreach ([['Alya Putri','h'],['Bima Saputra','h'],['Citra Lestari','s'],['Dimas Pratama','h'],['Eka Wulandari','i']] as $i => [$n,$p])
                    <div class="hp-baris"><span>{{ $n }}</span>
                        <span class="d-flex gap-1" style="--n:{{ $i }}">
                            @foreach (['h'=>'H','s'=>'S','i'=>'I','a'=>'A'] as $k=>$hrf)<span class="st st-{{ $k }} {{ $k===$p ? 'on' : '' }}">{{ $hrf }}</span>@endforeach
                        </span>
                    </div>
                @endforeach
                <div class="hp-tombol">Simpan absen</div>
            </div>
            <span class="ubin" style="--d:0;left:0;top:50px;color:#f97316"><i class="bi bi-pencil-fill"></i></span>
            <span class="ubin" style="--d:1;right:0;top:20px;color:var(--biru)"><i class="bi bi-bell-fill"></i><span class="titik"></span></span>
            <span class="ubin" style="--d:2;right:-4px;bottom:120px;color:#16a34a"><i class="bi bi-qr-code-scan"></i></span>
            <span class="ubin" style="--d:3;left:-10px;bottom:0;color:#7c3aed"><i class="bi bi-graph-up-arrow"></i></span>
            <div class="toast-kirim"><i class="bi bi-check-circle-fill"></i>Terkirim ke orang tua</div>
        </div>
    </div>

    <div class="col-lg-6 sisi-kanan d-flex align-items-center p-4 p-md-5">
        <div class="w-100 mx-auto" style="max-width:24rem">
            <div class="mb-4">@include('layouts._logo')</div>
            <h1 class="h3 mb-1">Hai, selamat datang kembali</h1>
            <p class="redam small mb-4">Baru di AbsenKlik? <a href="{{ route('pendaftaran.form') }}" class="fw-semibold">Daftar gratis</a></p>

            @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

            <form method="POST" action="{{ route('login') }}" id="form-login">
                @csrf
                <div class="mb-3">
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" placeholder="Username atau email" aria-label="Username atau email"
                               autocomplete="username" autofocus class="form-control @error('login') is-invalid @enderror" required>
                        @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input id="password" type="password" name="password" placeholder="Password" aria-label="Password"
                               autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" required>
                        <button type="button" class="btn btn-mata" id="lihat" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4 small">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Ingat perangkat ini</label>
                    </div>
                    @if (Route::has('password.request')) <a href="{{ route('password.request') }}">Lupa password?</a> @endif
                </div>
                <button type="submit" class="btn btn-biru btn-masuk w-100 py-2" id="tombol">
                    <span class="spinner-border spinner-border-sm spin me-2" role="status"></span><span class="teks">Masuk</span>
                </button>
            </form>
            <p class="small redam mt-4 mb-0">Dengan masuk, Anda menyetujui syarat penggunaan layanan AbsenKlik.</p>
        </div>
    </div>

</div>
</div>

<script>
document.getElementById('lihat').addEventListener('click', function () {
    var p = document.getElementById('password'), tampil = p.type === 'password';
    p.type = tampil ? 'text' : 'password';
    this.innerHTML = '<i class="bi bi-eye' + (tampil ? '-slash' : '') + '"></i>';
    this.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
});
document.getElementById('form-login').addEventListener('submit', function () {
    var t = document.getElementById('tombol');
    t.classList.add('memuat');
    t.querySelector('.teks').textContent = 'Memeriksa...';
    setTimeout(function () { t.disabled = true; }, 0);
});
</script>
@endsection