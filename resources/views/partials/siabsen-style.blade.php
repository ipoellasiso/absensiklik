<style>
  /* Komponen SIABSEN SD (prefiks sb- agar tidak bentrok dengan CSS template) */
  .sb-st-hadir { --c: #15803D; }
  .sb-st-terlambat { --c: #B45309; }
  .sb-st-sakit { --c: #0E7490; }
  .sb-st-izin { --c: #6D28D9; }
  .sb-st-alpa { --c: #B91C1C; }
  .sb-st-belum { --c: #94A3B8; }

  .sb-kode {
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 6px; flex-shrink: 0;
    background: color-mix(in srgb, var(--c) 14%, #fff); color: var(--c);
    font-weight: 700; font-size: 13px; line-height: 1;
  }
  .sb-count { display: inline-flex; align-items: center; gap: 5px; font-weight: 600; font-size: 13px; }
  .sb-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: var(--c); }

  /* Pilihan status per siswa */
  .sb-group { display: flex; flex-wrap: wrap; gap: 6px; }
  .sb-input { position: absolute; opacity: 0; pointer-events: none; }
  .sb-chip {
    --c: #64748B;
    display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 11px; margin: 0;
    border: 1.5px solid #E2E8F0; border-radius: 9px; background: #fff; color: #64748B;
    font-weight: 600; font-size: 13px; cursor: pointer; user-select: none;
    transition: background-color .12s, border-color .12s, color .12s;
  }
  .sb-chip:hover { border-color: var(--c); }
  .sb-input:checked + .sb-chip { background: var(--c); border-color: var(--c); color: #fff; }
  .sb-input:checked + .sb-chip .sb-kode { background: rgba(255,255,255,.22); color: #fff; }
  .sb-input:focus-visible + .sb-chip { outline: 3px solid #2563C8; outline-offset: 2px; }

  /* Daftar hadir */
  .sb-reg-row, .sb-reg-head {
    display: grid; grid-template-columns: 36px minmax(0,1fr) auto 210px; gap: 8px 16px;
    align-items: center; padding: 10px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
  }
  .sb-reg-head { font-size: 12px; font-weight: 600; color: #64748B; background: #F8FAFC; }
  .sb-reg-row:last-child { border-bottom: 0; }
  .sb-reg-name { font-weight: 600; color: #111827; }
  .sb-tag { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; background: #EBF2FF; color: #2563C8; margin-left: 6px; }
  .sb-time { font-size: 12px; color: #64748B; margin-left: 6px; font-weight: 400; }
  .sb-save {
    position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; gap: 10px 20px;
    align-items: center; justify-content: space-between; padding: 12px 20px;
    background: #fff; border-top: 1px solid rgba(0,0,0,.08); border-radius: 0 0 12px 12px;
  }
  .sb-tally { display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: 13px; }
  .sb-tally-item { display: inline-flex; align-items: center; gap: 6px; }

  /* Dashboard */
  .sb-welcome { background: linear-gradient(135deg, #0D2B5E 0%, #2563C8 100%); border-radius: 14px; color: #fff; border: none; position: relative; overflow: hidden; }
  .sb-welcome::after { content: ''; position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; border-radius: 50%; border: 2px solid rgba(255,255,255,.07); pointer-events: none; }
  .sb-welcome h4 { color: #fff; }
  .sb-welcome-stat strong { display: block; font-size: 22px; font-weight: 700; line-height: 1; }
  .sb-welcome-stat small { opacity: .6; font-size: 11px; }
  .sb-stat { border: none; border-radius: 12px; }
  .sb-ico { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; background: color-mix(in srgb, var(--c) 13%, #fff); color: var(--c); }
  .sb-stat-value { font-size: 22px; font-weight: 700; line-height: 1.2; color: #111827; }
  .sb-stat-label { font-size: 12px; color: #64748B; }
  .sb-bar { display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: rgba(0,0,0,.08); }
  .sb-bar > span { display: block; height: 100%; background: var(--c); }
  .sb-counts { display: flex; flex-wrap: wrap; gap: 4px 12px; }

  @media (max-width: 767.98px) {
    .sb-reg-row { grid-template-columns: 28px minmax(0,1fr); padding: 12px 14px; }
    .sb-reg-row .sb-group, .sb-reg-row .sb-note { grid-column: 1 / -1; }
  }
  @media (max-width: 575.98px) {
    .sb-group { flex-wrap: nowrap; justify-content: space-between; }
    .sb-chip { flex: 1; justify-content: center; padding: 0 6px; }
    .sb-chip .sb-label { display: none; }
  }
  @media (prefers-reduced-motion: reduce) { .sb-chip { transition: none; } }
</style>
