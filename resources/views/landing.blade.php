{{-- Simpan di: resources/views/landing.blade.php (timpa). Rute: Route::view('/', 'landing', ['title' => 'Absensi Sekolah Dasar'])->middleware('guest'); --}}
@extends('layouts.publik')

@push('head')
<style>
body{background:linear-gradient(135deg,#e3ebfb,#f4f7fc 45%,#e8eefc);overflow-x:hidden}
.hero-wrap{position:relative}
.hero-wrap::before{content:"";position:absolute;right:-140px;top:-60px;width:460px;height:460px;border-radius:50%;background:rgba(36,86,232,.08);z-index:-1}
.hero-card{position:relative;background:#fff;border-radius:22px;padding:1.4rem;box-shadow:0 30px 60px -28px rgba(15,27,61,.4)}
.hero-card .baris{display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--garis)}
.hero-card .baris:last-of-type{border-bottom:0}
.ubin{position:absolute;width:54px;height:54px;border-radius:16px;background:#fff;display:grid;place-items:center;font-size:1.5rem;box-shadow:0 14px 28px -12px rgba(15,27,61,.4)}
.ubin .titik{position:absolute;top:9px;right:9px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid #fff}
.toast-kirim{position:absolute;left:-22px;bottom:-20px;background:#16a34a;color:#fff;border-radius:12px;padding:.55rem .9rem;font-size:.85rem;font-weight:600;box-shadow:0 14px 26px -10px rgba(22,163,74,.6);display:flex;gap:.45rem;align-items:center}
.ikon-peran{flex:none;width:46px;height:46px;border-radius:14px;display:grid;place-items:center;font-size:1.3rem;background:#fff;box-shadow:0 10px 22px -12px rgba(15,27,61,.45)}
.langkah-wrap{position:relative}
.langkah-garis{position:absolute;top:27px;left:6%;right:6%;height:3px;background:var(--garis);border-radius:3px;overflow:hidden}
.langkah-garis::after{content:"";position:absolute;inset:0;background:var(--biru);transform-origin:left;transform:scaleX(1)}
.bulat{position:relative;width:54px;height:54px;border-radius:50%;background:var(--biru);color:#fff;display:grid;place-items:center;font-size:1.4rem;box-shadow:0 0 0 6px #e8eefc}
.band{background:linear-gradient(120deg,#1d4ed8,#2456e8,#1639a8,#2456e8);background-size:300% 300%;color:#fff;border-radius:26px}
@media (prefers-reduced-motion:no-preference){
  .hero-card .st.on{animation:ulang 7s ease-out infinite;animation-delay:calc(var(--n)*.18s + .3s)}
  .ubin{animation:melayang 5s ease-in-out infinite;animation-delay:calc(var(--d)*-1.3s)}
  .toast-kirim{animation:toast 7s ease-out infinite;animation-delay:1.6s}
  .band{animation:geser 10s ease infinite}
  .hero-anim{animation:masuk .8s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--k)*.12s)}
  .js .muncul{opacity:0;transform:translateY(26px);transition:opacity .7s ease,transform .7s cubic-bezier(.2,.8,.2,1);transition-delay:calc(var(--k,0)*.12s)}
  .js .muncul.tampak{opacity:1;transform:none}
  .js .langkah-garis::after{transform:scaleX(0);transition:transform 1.4s ease .2s}
  .js .tampak .langkah-garis::after{transform:scaleX(1)}
  .js .bulat{transform:scale(0);transition:transform .5s cubic-bezier(.3,1.6,.5,1);transition-delay:calc(var(--k,0)*.45s + .2s)}
  .js .tampak .bulat{transform:scale(1)}
}
@keyframes ulang{0%{transform:scale(1.7);opacity:0}6%,90%{transform:none;opacity:1}100%{opacity:0}}
@keyframes melayang{0%,100%{transform:translateY(0) rotate(-2deg)}50%{transform:translateY(-11px) rotate(2deg)}}
@keyframes toast{0%,30%{opacity:0;transform:translateY(14px) scale(.9)}40%{opacity:1;transform:none}85%{opacity:1}100%{opacity:0}}
@keyframes geser{0%,100%{background-position:0 50%}50%{background-position:100% 50%}}
@keyframes masuk{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:none}}
</style>
@endpush

@section('content')
@php
    $baris = [['Alya Putri','h'],['Bima Saputra','h'],['Citra Lestari','s'],['Dimas Pratama','h'],['Eka Wulandari','i'],['Fajar Nugroho','h']];
    $kode = ['h' => 'H', 's' => 'S', 'i' => 'I', 'a' => 'A'];
@endphp

<nav class="container py-3 d-flex align-items-center justify-content-between">
    @include('layouts._logo')
    <div class="d-flex gap-2">
        <a href="{{ route('login') }}" class="btn btn-garis">Masuk</a>
        <a href="{{ route('pendaftaran.form') }}" class="btn btn-biru d-none d-sm-inline-block">Coba gratis</a>
    </div>
</nav>

<header class="container py-5 hero-wrap">
    <div class="row align-items-center g-5">
        <div class="col-lg-6">
            <h1 class="display-4 mb-3 hero-anim" style="--k:0">Absen satu kelas dalam hitungan detik. <span class="b">Orang tua</span> langsung tahu.</h1>
            <p class="fs-5 redam mb-4 hero-anim" style="--k:1;max-width:34rem">AbsenKlik mencatat kehadiran siswa dan guru, memberi kabar ke orang tua saat anak tidak hadir, dan menyiapkan rekap untuk rapor.</p>
            <div class="d-flex flex-wrap gap-2 mb-3 hero-anim" style="--k:2">
                <a href="{{ route('pendaftaran.form') }}" class="btn btn-biru btn-lg">Coba gratis 30 hari</a>
                <a href="{{ route('login') }}" class="btn btn-garis btn-lg">Masuk</a>
            </div>
            <p class="small redam mb-0 hero-anim" style="--k:3">Tanpa kartu kredit. Aktif langsung setelah sekolah mendaftar.</p>
        </div>

        <div class="col-lg-6">
            <div class="position-relative mx-auto hero-anim" style="--k:2;max-width:30rem;padding:20px 24px 28px">
                <span class="ubin" style="--d:0;left:0;top:0;color:#f97316"><i class="bi bi-pencil-fill"></i></span>
                <span class="ubin" style="--d:1;right:0;top:-6px;color:var(--biru)"><i class="bi bi-bell-fill"></i><span class="titik"></span></span>
                <span class="ubin" style="--d:2;right:-4px;bottom:70px;color:#16a34a"><i class="bi bi-qr-code-scan"></i></span>

                <div class="hero-card mt-4">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h2 class="h5 mb-0">Kelas 3A</h2>
                        <span class="small redam">Senin, 07.15</span>
                    </div>
                    @foreach ($baris as $i => [$nama, $pilih])
                        <div class="baris">
                            <span class="fw-medium">{{ $nama }}</span>
                            <span class="d-flex gap-1" style="--n:{{ $i }}">
                                @foreach ($kode as $k => $huruf)<span class="st st-{{ $k }} {{ $k === $pilih ? 'on' : '' }}">{{ $huruf }}</span>@endforeach
                            </span>
                        </div>
                    @endforeach
                    <div class="d-flex flex-wrap gap-3 small mt-3 pt-3 border-top" style="border-color:var(--garis)!important">
                        <span><b style="color:var(--hadir)" data-ke="4">4</b> hadir</span>
                        <span><b style="color:var(--sakit)" data-ke="1">1</b> sakit</span>
                        <span><b style="color:var(--izin)" data-ke="1">1</b> izin</span>
                        <span><b style="color:var(--alpa)" data-ke="0">0</b> alpa</span>
                    </div>
                    <div class="toast-kirim"><i class="bi bi-check-circle-fill"></i>Orang tua Citra sudah diberi kabar</div>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="container py-5">
    <div class="row g-5">
        <div class="col-lg-4 muncul">
            <h2 class="display-6">Satu aplikasi untuk semua yang terlibat di sekolah.</h2>
        </div>
        <div class="col-lg-8">
            @foreach ([
                ['Guru', 'bi-person-workspace', '#f97316', 'Absen manual atau scan QR kartu siswa. Absen guru memakai lokasi sekolah, lengkap dengan foto.'],
                ['Orang tua', 'bi-bell-fill', '#2456e8', 'Dapat kabar saat anak tidak hadir, dan mengajukan izin atau sakit dari HP tanpa datang ke sekolah.'],
                ['Kepala sekolah', 'bi-bar-chart-line-fill', '#16a34a', 'Lihat siapa yang sudah dan belum hadir hari ini, buka grafik bulanan, lalu unduh laporan Excel atau PDF.'],
                ['Wali kelas', 'bi-journal-check', '#7c3aed', 'Rekap sakit, izin, dan alpa per semester terhitung otomatis, siap dipindahkan ke rapor.'],
            ] as $i => [$peran, $ikon, $warna, $isi])
                <div class="d-flex gap-3 py-3 muncul {{ !$loop->last ? 'border-bottom' : '' }}" style="--k:{{ $i }};border-color:var(--garis)!important">
                    <span class="ikon-peran" style="color:{{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                    <div><h3 class="h5 mb-1">{{ $peran }}</h3><p class="mb-0 redam">{{ $isi }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="container py-5 muncul">
    <h2 class="display-6 mb-5">Mulai dalam tiga langkah</h2>
    <div class="langkah-wrap">
        <div class="langkah-garis d-none d-md-block"></div>
        <div class="row g-4">
            @foreach ([
                ['Daftarkan sekolah', 'Isi nama sekolah, NPSN, dan akun admin. Uji coba 30 hari langsung berjalan.'],
                ['Isi data sekolah', 'Tambahkan guru, kelas, dan siswa beserta orang tuanya.'],
                ['Cetak kartu QR, mulai absen', 'Guru bisa langsung memakai aplikasi di HP pada hari yang sama.'],
            ] as $n => [$judul, $isi])
                <div class="col-md-4">
                    <div class="bulat judul mb-3" style="--k:{{ $n }}">{{ $n + 1 }}</div>
                    <h3 class="h5">{{ $judul }}</h3>
                    <p class="redam mb-0">{{ $isi }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="container my-5 muncul">
    <div class="band p-4 p-md-5 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h2 class="mb-0 display-6">Coba di sekolah Anda selama 30 hari.</h2>
        <a href="{{ route('pendaftaran.form') }}" class="btn btn-light btn-lg fw-semibold">Daftarkan sekolah</a>
    </div>
</section>

<footer class="container py-4 small redam">&copy; {{ date('Y') }} AbsenKlik</footer>

<script>
document.documentElement.classList.add('js');
(function () {
    var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (es) {
        es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('tampak'); io.unobserve(e.target); } });
    }, { threshold: .15 }) : null;
    document.querySelectorAll('.muncul').forEach(function (el) { io ? io.observe(el) : el.classList.add('tampak'); });

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.querySelectorAll('[data-ke]').forEach(function (el) {
        var ke = +el.dataset.ke, mulai = null;
        (function langkah(t) {
            mulai = mulai || t;
            var p = Math.min((t - mulai) / 1200, 1);
            el.textContent = Math.round(ke * p);
            if (p < 1) requestAnimationFrame(langkah);
        })(performance.now());
    });
})();
</script>
@endsection