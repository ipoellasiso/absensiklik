{{-- Simpan di: resources/views/pendaftaran/daftar.blade.php (timpa). Field dan rute sama seperti sebelumnya. --}}
@extends('layouts.publik')

@push('head')
<style>
body{background:linear-gradient(135deg,#dfe7f7,#eef2fb 55%,#e3ebfb);min-height:100vh}
.kartu-login{max-width:1120px;margin:0 auto;border-radius:26px;overflow:hidden;background:#fff;box-shadow:0 30px 70px -30px rgba(15,27,61,.35)}
.sisi-kiri{position:relative;overflow:hidden;background:linear-gradient(160deg,#dbe8ff,#eef4ff 60%,#e4eeff)}
.sisi-kiri::before{content:"";position:absolute;left:-110px;bottom:-130px;width:340px;height:340px;border-radius:50%;background:rgba(36,86,232,.1)}
.sisi-kanan{position:relative}
.sisi-kanan::before{content:"";position:absolute;top:18px;right:18px;width:84px;height:34px;opacity:.35;background:repeating-linear-gradient(135deg,var(--biru) 0 2px,transparent 2px 9px)}
.o{color:#f97316}.b{color:var(--biru)}
.panggung{position:relative;width:300px;margin:0 auto;padding:10px 0 30px}
.kartu-cek{position:relative;background:#fff;border-radius:24px;padding:1.3rem 1.2rem;box-shadow:0 26px 50px -18px rgba(15,27,61,.4)}
.cincin{position:relative;width:118px;height:118px;margin:0 auto .9rem}
.cincin svg{transform:rotate(-90deg)}
.cincin .angka{position:absolute;inset:0;display:grid;place-content:center;text-align:center;line-height:1.05}
.cek{display:flex;align-items:center;gap:.65rem;padding:.42rem 0;font-size:.86rem;font-weight:500}
.cek .lingkar{flex:none;width:22px;height:22px;border-radius:50%;border:2px solid var(--garis);display:grid;place-items:center;color:#fff;font-size:.8rem;background:var(--hadir);border-color:var(--hadir)}
.ubin{position:absolute;width:52px;height:52px;border-radius:16px;background:#fff;display:grid;place-items:center;font-size:1.45rem;box-shadow:0 14px 28px -12px rgba(15,27,61,.4)}
.input-group-text{background:#fff;border-color:var(--garis);color:var(--redam);border-radius:10px 0 0 10px}
.input-group .form-control{border-radius:0}
.input-group .form-control:last-child,.input-group .invalid-feedback+.x{border-radius:0 10px 10px 0}
.btn-masuk .spin{display:none}.btn-masuk.memuat .spin{display:inline-block}
@media (prefers-reduced-motion:no-preference){
  .ubin{animation:melayang 5s ease-in-out infinite;animation-delay:calc(var(--d)*-1.3s)}
  .cek .lingkar{animation:centang 6.5s ease-out infinite backwards;animation-delay:calc(var(--i)*.7s + .5s)}
  .cincin circle.isi{animation:isi 6.5s ease-in-out infinite}
  .kartu-login{animation:masuk .7s cubic-bezier(.2,.8,.2,1) both}
}
@keyframes melayang{0%,100%{transform:translateY(0) rotate(-2deg)}50%{transform:translateY(-11px) rotate(2deg)}}
@keyframes centang{0%{transform:scale(0)}8%{transform:scale(1.3)}13%,88%{transform:scale(1)}100%{transform:scale(0)}}
@keyframes isi{0%{stroke-dashoffset:327}30%,88%{stroke-dashoffset:0}100%{stroke-dashoffset:327}}
@keyframes masuk{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
</style>
@endpush

@section('content')
@php
    $kolom = [
        ['nama_sekolah', 'Nama sekolah', 'text', 'col-md-8', 'SD Negeri 1 Palu', 'bi-building'],
        ['npsn', 'NPSN', 'text', 'col-md-4', '8 digit', 'bi-hash'],
        ['alamat', 'Alamat sekolah', 'text', 'col-12', '', 'bi-geo-alt'],
        ['nama_admin', 'Nama admin', 'text', 'col-md-6', '', 'bi-person-badge'],
        ['no_wa', 'No. WhatsApp', 'tel', 'col-md-6', '081234567890', 'bi-whatsapp'],
        ['username', 'Username untuk login', 'text', 'col-md-6', '', 'bi-person'],
        ['email', 'Email', 'email', 'col-md-6', '', 'bi-envelope'],
        ['password', 'Password', 'password', 'col-md-6', 'Minimal 8 karakter', 'bi-lock'],
        ['password_confirmation', 'Ulangi password', 'password', 'col-md-6', '', 'bi-lock-fill'],
    ];
@endphp
<div class="container py-lg-5 px-lg-4">
<div class="kartu-login row g-0">

    <div class="col-lg-5 sisi-kiri d-none d-lg-flex flex-column justify-content-center p-5">
        <h2 class="judul fs-4 mb-4" style="line-height:1.3">Daftarkan sekolah, <span class="o">uji coba gratis</span> langsung <span class="b">aktif</span>.</h2>
        <div class="panggung" aria-hidden="true">
            <span class="ubin" style="--d:0;left:-14px;top:-4px;color:#f97316"><i class="bi bi-mortarboard-fill"></i></span>
            <span class="ubin" style="--d:1;right:-14px;top:70px;color:var(--biru)"><i class="bi bi-people-fill"></i></span>
            <span class="ubin" style="--d:2;left:-18px;bottom:6px;color:#16a34a"><i class="bi bi-qr-code-scan"></i></span>
            <div class="kartu-cek mt-4">
                <div class="cincin">
                    <svg width="118" height="118" viewBox="0 0 118 118">
                        <circle cx="59" cy="59" r="52" fill="none" stroke="#e4ebf8" stroke-width="9"/>
                        <circle class="isi" cx="59" cy="59" r="52" fill="none" stroke="#2456e8" stroke-width="9" stroke-linecap="round" stroke-dasharray="327" stroke-dashoffset="0"/>
                    </svg>
                    <div class="angka"><span class="judul fs-2">30</span><span class="small redam">hari uji coba</span></div>
                </div>
                @foreach (['Sekolah terdaftar', 'Akun admin dibuat', 'Tahun ajaran dan semester siap', 'Kelas 1 sampai 6 siap dipakai'] as $i => $teks)
                    <div class="cek" style="--i:{{ $i }}"><span class="lingkar"><i class="bi bi-check-lg"></i></span>{{ $teks }}</div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-7 sisi-kanan d-flex align-items-center p-4 p-md-5">
        <div class="w-100 mx-auto" style="max-width:38rem">
            <div class="mb-4">@include('layouts._logo')</div>
            <h1 class="h3 mb-1">Buat akun sekolah</h1>
            <p class="redam small mb-4">Sudah punya akun? <a href="{{ route('login') }}" class="fw-semibold">Masuk</a>. Akun ini menjadi admin sekolah yang mengelola guru, siswa, dan kelas.</p>

            <form method="POST" action="{{ route('pendaftaran.simpan') }}" id="form-daftar" novalidate>
                @csrf
                <div class="row g-3">
                    @foreach ($kolom as [$nama, $label, $tipe, $lebar, $contoh, $ikon])
                        <div class="{{ $lebar }}">
                            <label class="form-label" for="{{ $nama }}">{{ $label }}</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="bi {{ $ikon }}"></i></span>
                                <input id="{{ $nama }}" type="{{ $tipe }}" name="{{ $nama }}" placeholder="{{ $contoh }}"
                                       @unless ($tipe === 'password') value="{{ old($nama) }}" @endunless
                                       class="form-control @error($nama) is-invalid @enderror" required>
                                @error($nama) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="form-check my-4">
                    <input class="form-check-input @error('setuju') is-invalid @enderror" type="checkbox" name="setuju" id="setuju" value="1" @checked(old('setuju'))>
                    <label class="form-check-label" for="setuju">Saya menyetujui syarat penggunaan layanan.</label>
                    @error('setuju') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-biru btn-masuk btn-lg w-100" id="tombol">
                    <span class="spinner-border spinner-border-sm spin me-2" role="status"></span><span class="teks">Daftar dan mulai uji coba</span>
                </button>
            </form>
        </div>
    </div>

</div>
</div>

<script>
document.getElementById('form-daftar').addEventListener('submit', function () {
    var t = document.getElementById('tombol');
    t.classList.add('memuat');
    t.querySelector('.teks').textContent = 'Menyiapkan sekolah...';
    setTimeout(function () { t.disabled = true; }, 0);
});
</script>
@endsection