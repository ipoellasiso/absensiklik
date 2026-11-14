<div class="topbar d-flex align-items-center">
  <nav class="navbar navbar-expand">
    <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>

    {{-- ── WELCOME TYPING TEXT + ROLE (kiri) ─────────────────────── --}}
    <div class="ms-3 d-none d-md-flex align-items-center gap-2" style="min-width:0;flex:1;">
      
      {{-- Role badge static --}}
      @php
        $roleLabels = [
          'super_admin'    => ['label'=>'Super Admin',    'color'=>'#a78bfa','bg'=>'rgba(167,139,250,.15)'],
          'operator_bpkad' => ['label'=>'Operator BPKAD','color'=>'#38bdf8','bg'=>'rgba(56,189,248,.15)'],
          'admin_sekolah'  => ['label'=>'Admin Sekolah',  'color'=>'#34d399','bg'=>'rgba(52,211,153,.15)'],
          'bendahara'      => ['label'=>'Bendahara',      'color'=>'#fbbf24','bg'=>'rgba(251,191,36,.15)'],
          'kepala_sekolah' => ['label'=>'Kepala Sekolah', 'color'=>'#f87171','bg'=>'rgba(248,113,113,.15)'],
        ];
        $role     = Auth::user()->role ?? '';
        $roleInfo = $roleLabels[$role] ?? ['label'=>ucfirst($role),'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.15)'];
      @endphp
      <span style="font-size:10px;font-weight:700;
                  color:{{ $roleInfo['color'] }};
                  background:{{ $roleInfo['bg'] }};
                  border:1px solid {{ $roleInfo['color'] }}40;
                  border-radius:10px;padding:2px 10px;
                  white-space:nowrap;line-height:1.8;flex-shrink:0;">
        {{ $roleInfo['label'] }}
      </span>

      {{-- Separator --}}
      <span style="color:rgba(255,255,255,.2);font-size:14px;flex-shrink:0;">|</span>

      {{-- Typing text --}}
      <div class="d-flex align-items-center" style="min-width:0;">
        <span id="navWelcomeTyped"
              style="font-size:12px;font-weight:600;color:rgba(255,255,255,.7);
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
        </span>
        <span style="display:inline-block;width:2px;height:14px;background:#38bdf8;
                    margin-left:2px;animation:blink .7s steps(1) infinite;
                    vertical-align:middle;flex-shrink:0;"></span>
      </div>

    </div>

    <style>
      @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
    </style>

    {{-- ── GANTI TAHUN ─────────────────────────────────────── --}}
    <div class="dropdown me-3" style="position:relative;">
      <button type="button" class="btn p-0 border-0" id="dropdownTahunBtn"
              data-bs-toggle="dropdown" aria-expanded="false"
              style="background:rgba(56,189,248,.15);border:1px solid rgba(56,189,248,.25)!important;
                     border-radius:20px;padding:4px 14px!important;
                     display:flex;align-items:center;gap:6px;">
        <i class='bx bx-calendar-check' style="color:#38bdf8;font-size:15px;"></i>
        <span style="font-size:12px;font-weight:600;color:#38bdf8;">
          TA {{ session('tahun_anggaran', date('Y')) }}
        </span>
        <i class='bx bx-chevron-down' style="color:#38bdf8;font-size:12px;"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="dropdownTahunBtn"
           style="min-width:240px;background:#1a2e4a;border:1px solid rgba(255,255,255,.15);
                  border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.5);">
        <p style="font-size:11px;color:rgba(255,255,255,.45);margin-bottom:4px;">Tahun aktif saat ini</p>
        <p style="font-size:14px;font-weight:700;color:#38bdf8;margin-bottom:12px;">
          {{ session('tahun_anggaran', date('Y')) }}
        </p>
        <form method="POST" action="{{ route('tahun.ganti') }}">
          @csrf
          @php
            $daftarTahun = \Illuminate\Support\Facades\DB::table('tahun_anggaran')
                ->select('tahun')->groupBy('tahun')->orderByDesc('tahun')->pluck('tahun');
          @endphp
          <select name="tahun_anggaran" class="form-select form-select-sm mb-2"
                  style="background:#0f2337;color:#fff;border-color:rgba(255,255,255,.2);font-size:13px;">
            @forelse($daftarTahun as $thn)
            <option value="{{ $thn }}" {{ session('tahun_anggaran')==$thn?'selected':'' }}>
              {{ $thn }}{{ session('tahun_anggaran')==$thn?' — Aktif':'' }}
            </option>
            @empty
            <option value="{{ date('Y') }}" selected>{{ date('Y') }}</option>
            @endforelse
          </select>
          <button type="submit" class="btn btn-sm w-100 fw-semibold"
                  style="background:linear-gradient(135deg,#0891b2,#38bdf8);color:#fff;
                         border:none;border-radius:7px;">
            <i class='bx bx-check me-1'></i>Terapkan
          </button>
        </form>
      </div>
    </div>

    {{-- ── USER DROPDOWN (kiri dari tahun) ───────────────── --}}
    <div class="user-box dropdown">
      <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret"
         href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="{{ asset('assets/images/avatars/logo_twh.png') }}"
             class="user-img" alt="user avatar">
        <div class="user-info ps-3">
          <p class="designattion mb-0">{{ Auth::user()->sekolah->nama_sekolah ?? '-' }}</p>
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="javascript:;"><i class="bx bx-user"></i><span>Profile</span></a></li>
        <li><a class="dropdown-item" href="javascript:;"><i class="bx bx-cog"></i><span>Settings</span></a></li>
        <li><div class="dropdown-divider mb-0"></div></li>
        <li><a class="dropdown-item" href="/logout"><i class='bx bx-log-out-circle'></i><span>Logout</span></a></li>
      </ul>
    </div>

  </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const text = "Selamat Datang, {{ Auth::user()->name }} 👋";
    const typedEl = document.getElementById('navWelcomeTyped');
    let i = 0, isDeleting = false;
    function typeLoop() {
        typedEl.textContent = text.substring(0, i);
        if (!isDeleting && i < text.length) {
            i++; setTimeout(typeLoop, 80);
        } else if (isDeleting && i > 0) {
            i--; setTimeout(typeLoop, 50);
        } else {
            isDeleting = !isDeleting;
            setTimeout(typeLoop, isDeleting ? 3000 : 500);
        }
    }
    typeLoop();
});
</script>