
@php
    $__u = auth()->user();
@endphp

@if ($__u && $__u->sekolah)

    @php
        $__l = $__u->sekolah->langganan();

        $__s = $__l['sisa_hari'] ?? null;

        $__tgl = isset($__l['sampai'])
            ? $__l['sampai']?->format('d/m/Y')
            : '-';

        $__admin = $__u->hasRole('admin');

        $__status = $__l['status'] ?? '';
    @endphp

    {{-- PERINGATAN MASA UJI COBA / LANGGANAN --}}
    @if (
        in_array($__status, ['uji_coba', 'aktif'], true)
        && $__s !== null
        && $__s <= 14
    )

        <div class="alert alert-{{ $__status === 'uji_coba' ? 'info' : 'warning' }} d-flex flex-wrap justify-content-between align-items-center gap-2">

            <span>
                {{ $__status === 'uji_coba' ? 'Masa uji coba' : 'Langganan' }}
                berakhir {{ $__tgl }} ({{ $__s }} hari lagi).
            </span>

            @if ($__admin)
                <a href="{{ route('langganan.index') }}"
                   class="btn btn-sm btn-primary">
                    Lihat paket dan tagihan
                </a>
            @endif

        </div>

    {{-- MASA TENGGANG --}}
    @elseif ($__status === 'tenggang')

        <div class="alert alert-danger d-flex flex-wrap justify-content-between align-items-center gap-2">

            <span>
                Langganan telah berakhir.
                Masa tenggang sampai {{ $__tgl }},
                setelah itu data hanya bisa dilihat.
            </span>

            @if ($__admin)
                <a href="{{ route('langganan.index') }}"
                   class="btn btn-sm btn-danger">
                    Perpanjang sekarang
                </a>
            @endif

        </div>

    {{-- LANGGANAN BERAKHIR --}}
    @elseif ($__status === 'berakhir')

        <div class="alert alert-danger d-flex flex-wrap justify-content-between align-items-center gap-2">

            <span>
                Mode baca saja: langganan sekolah berakhir.
                Absensi baru tidak bisa diisi, data tetap aman.
            </span>

            @if ($__admin)
                <a href="{{ route('langganan.index') }}"
                   class="btn btn-sm btn-danger">
                    Perpanjang sekarang
                </a>
            @endif

        </div>

    @endif

@endif
