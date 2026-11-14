@extends('Template.Layout')

@php
    $warna = ['aktif' => 'success', 'uji_coba' => 'info', 'tenggang' => 'warning', 'berakhir' => 'danger'][$info['status']];
    $batas = $sekolah->batasSiswa();
    $persenPakai = $batas ? min(100, round($siswaAktif / $batas * 100)) : 0;
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $adaMenunggu = $tagihan->contains('status', 'menunggu');
@endphp

@section('content')
@include('partials.pesan')

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Status langganan</div>
                <div class="d-flex align-items-center gap-2 my-1">
                    <span class="fs-4 fw-bold">{{ $info['label'] }}</span>
                    <span class="badge bg-{{ $warna }}">{{ $info['status'] }}</span>
                </div>
                @if($info['sampai'])
                    <p class="mb-1">
                        {{ $info['status'] === 'berakhir' ? 'Berakhir' : 'Sampai' }} <strong>{{ $info['sampai']->translatedFormat('d F Y') }}</strong>
                        @if($info['sisa_hari'] !== null && $info['sisa_hari'] >= 0)({{ $info['sisa_hari'] }} hari lagi)@endif
                    </p>
                @endif
                @if($sekolah->paket)<p class="mb-1">Paket <strong>{{ $sekolah->paket->nama }}</strong>, sampai {{ $sekolah->paket->batas_siswa }} siswa aktif.</p>@endif
                @if($info['status'] === 'tenggang')<div class="alert alert-warning mt-2 mb-0">Masa tenggang. Setelah tanggal di atas, aplikasi hanya bisa melihat data.</div>@endif
                @if($info['status'] === 'berakhir')<div class="alert alert-danger mt-2 mb-0">Mode baca saja. Absensi baru tidak bisa diisi sampai langganan diperpanjang. Data Anda tetap aman.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pemakaian</div>
                <div class="fs-4 fw-bold my-1">{{ $siswaAktif }} <span class="fs-6 text-muted">{{ $batas ? 'dari '.$batas.' siswa aktif' : 'siswa aktif (tanpa batas)' }}</span></div>
                @if($batas)
                    <div class="progress" style="height:8px;"><div class="progress-bar bg-{{ $persenPakai >= 90 ? 'danger' : 'primary' }}" style="width: {{ $persenPakai }}%"></div></div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Pilih paket</h6></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:12.5px;">
            <thead><tr class="table-light"><th class="ps-4 py-2 border-0">Paket</th><th class="py-2 border-0">Batas siswa aktif</th><th class="py-2 border-0">Harga per tahun</th><th class="pe-4 py-2 border-0"></th></tr></thead>
            <tbody>
            @forelse($paket as $p)
                <tr>
                    <td class="ps-4 fw-semibold">{{ $p->nama }}<div class="small text-muted fw-normal">{{ $p->keterangan }}</div></td>
                    <td>{{ $p->batas_siswa }}</td>
                    <td>{{ $rp($p->harga_per_tahun) }}</td>
                    <td class="pe-4 text-end">
                        <form method="POST" action="{{ route('langganan.buat') }}">
                            @csrf <input type="hidden" name="paket_id" value="{{ $p->id }}">
                            <button class="btn btn-sm btn-primary" @disabled($adaMenunggu || $siswaAktif > $p->batas_siswa)
                                    title="{{ $siswaAktif > $p->batas_siswa ? 'Siswa aktif melebihi batas paket ini' : '' }}">Pilih dan buat tagihan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada paket yang tersedia.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($adaMenunggu)<div class="card-footer bg-transparent px-4 small text-muted">Selesaikan atau batalkan tagihan yang menunggu pembayaran sebelum membuat yang baru.</div>@endif
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Tagihan</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:12.5px;">
                    <thead><tr class="table-light"><th class="ps-4 py-2 border-0">Nomor</th><th class="py-2 border-0">Jumlah</th><th class="py-2 border-0">Status</th><th class="pe-4 py-2 border-0"></th></tr></thead>
                    <tbody>
                    @forelse($tagihan as $t)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $t->nomor }}<div class="small text-muted fw-normal">{{ $t->paket_nama }}, {{ $t->lama_bulan }} bulan, {{ $t->created_at->format('d/m/Y') }}</div></td>
                            <td>{{ $rp($t->jumlah) }}</td>
                            <td>
                                <span class="badge bg-{{ ['menunggu' => 'warning', 'lunas' => 'success', 'batal' => 'secondary'][$t->status] }}">{{ ucfirst($t->status) }}</span>
                                @if($t->status === 'lunas')<div class="small text-muted">{{ $t->periode_mulai->format('d/m/Y') }} - {{ $t->periode_selesai->format('d/m/Y') }}</div>@endif
                                @if($t->status === 'menunggu' && $t->bukti)<div class="small text-success">Bukti terkirim, menunggu verifikasi</div>@endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('langganan.pdf', $t) }}" target="_blank" class="btn btn-sm btn-outline-primary mb-1">{{ $t->status === 'lunas' ? 'Kwitansi' : 'Tagihan' }} PDF</a>
                                @if($t->status === 'menunggu')
                                    <button type="button" class="btn btn-sm btn-primary mb-1 btn-bayar"
                                    data-bayar="{{ route('langganan.bayar', $t) }}" data-sinkron="{{ route('langganan.sinkron', $t) }}">Bayar online</button>
                                    <form method="POST" action="{{ route('langganan.bukti', $t) }}" enctype="multipart/form-data" class="d-flex gap-1 mb-1">
                                        @csrf
                                        <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" class="form-control form-control-sm" required>
                                        <button class="btn btn-sm btn-success text-nowrap">Unggah bukti</button>
                                    </form>
                                    <form method="POST" action="{{ route('langganan.batal', $t) }}" onsubmit="return confirm('Batalkan tagihan ini?')">
                                        @csrf <button class="btn btn-sm btn-outline-danger">Batalkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada tagihan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent px-4 py-3"><h6 class="mb-0 fw-bold">Cara pembayaran</h6></div>
            <div class="card-body px-4">
                <ol class="ps-3 small mb-3">
                    <li>Pilih paket, tagihan dibuat otomatis.</li>
                    <li>Transfer sesuai jumlah pada tagihan ke rekening di bawah.</li>
                    <li>Unggah foto bukti transfer pada tagihan.</li>
                    <li>Setelah diverifikasi, masa aktif bertambah otomatis dan kwitansi tersedia.</li>
                </ol>
                <div class="p-3 rounded" style="background:#F1F5F9;">
                    <div class="fw-semibold">{{ $rekening['bank'] }}</div>
                    <div class="fs-5 fw-bold">{{ $rekening['nomor'] }}</div>
                    <div class="small text-muted">a.n. {{ $rekening['atas_nama'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ config('langganan.midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com' }}/snap/snap.js" data-client-key="{{ config('langganan.midtrans.client_key') }}"></script>
<script>
document.querySelectorAll('.btn-bayar').forEach(function (tombol) {
    tombol.addEventListener('click', async function () {
        tombol.disabled = true;
        var kirim = function (url) {
            return fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
        };
        var selesai = function () { kirim(tombol.dataset.sinkron).finally(function () { location.reload(); }); };
        try {
            var res = await kirim(tombol.dataset.bayar);
            var data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal membuat pembayaran.');
            window.snap.pay(data.token, {
                onSuccess: selesai,
                onPending: selesai,
                onClose: selesai,
                onError: function () { alert('Pembayaran gagal. Coba lagi.'); tombol.disabled = false; }
            });
        } catch (e) { alert(e.message); tombol.disabled = false; }
    });
});
</script>

@endsection