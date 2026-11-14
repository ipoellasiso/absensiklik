<?php

namespace App\Http\Controllers\Web\Platform;

use App\Http\Controllers\Controller;
use App\Models\LiburNasional;
use App\Models\Paket;
use App\Models\Sekolah;
use App\Models\Tagihan;
use App\Services\LanggananService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LanggananController extends Controller
{
    public function __construct(private LanggananService $layanan)
    {
    }

    // ── Paket ──

    public function paket()
    {
        return view('platform.paket', [
            'title' => 'Paket', 'breadcumd' => 'Platform', 'breadcumd1' => 'Paket',
            'items' => Paket::orderBy('batas_siswa')->get(),
        ]);
    }

    public function paketSimpan(Request $request)
    {
        $d = $request->validate([
            'id' => 'nullable|exists:paket,id',
            'nama' => 'required|string|max:60',
            'batas_siswa' => 'required|integer|min:1|max:100000',
            'harga_per_tahun' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        Paket::updateOrCreate(['id' => $d['id'] ?? null], collect($d)->except('id')->all());

        return back()->with('sukses', 'Paket disimpan.');
    }

    public function paketToggle(Paket $paket)
    {
        $paket->update(['is_aktif' => ! $paket->is_aktif]);

        return back()->with('sukses', $paket->is_aktif ? 'Paket diaktifkan.' : 'Paket disembunyikan dari pilihan sekolah.');
    }

    // ── Tagihan dan pembayaran ──

    public function tagihan(Request $request)
    {
        $status = in_array($request->query('status'), ['menunggu', 'lunas', 'batal'], true) ? $request->query('status') : null;

        return view('platform.tagihan', [
            'title' => 'Pembayaran', 'breadcumd' => 'Platform', 'breadcumd1' => 'Pembayaran',
            'items' => Tagihan::with('sekolah:id,nama')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'sekolah' => Sekolah::orderBy('nama')->get(['id', 'nama']),
            'paket' => Paket::where('is_aktif', true)->orderBy('batas_siswa')->get(),
        ]);
    }

    public function tagihanBuat(Request $request)
    {
        $d = $request->validate([
            'sekolah_id' => 'required|exists:sekolah,id',
            'paket_id' => 'required|exists:paket,id',
            'lama_bulan' => 'required|integer|between:1,36',
            'jumlah' => 'nullable|integer|min:0',
        ]);

        $t = $this->layanan->buatTagihan(
            Sekolah::findOrFail($d['sekolah_id']),
            Paket::findOrFail($d['paket_id']),
            (int) $d['lama_bulan'],
            isset($d['jumlah']) ? (int) $d['jumlah'] : null
        );

        return back()->with('sukses', "Tagihan {$t->nomor} dibuat.");
    }

    public function tagihanKonfirmasi(Request $request, Tagihan $tagihan)
    {
        $t = $this->layanan->konfirmasiLunas($tagihan, $request->user());

        return back()->with('sukses', "Tagihan {$t->nomor} lunas. Langganan aktif sampai {$t->periode_selesai->format('d/m/Y')}.");
    }

    public function tagihanBatal(Tagihan $tagihan)
    {
        abort_unless($tagihan->status === 'menunggu', 422, 'Tagihan sudah diproses.');
        $tagihan->update(['status' => 'batal']);

        return back()->with('sukses', 'Tagihan dibatalkan.');
    }

    public function tagihanBukti(Tagihan $tagihan)
    {
        abort_unless($tagihan->bukti && Storage::disk('local')->exists($tagihan->bukti), 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($tagihan->bukti, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function tagihanPdf(Tagihan $tagihan)
    {
        return Pdf::loadView('langganan.tagihan-pdf', $this->layanan->dataFaktur($tagihan))
            ->setPaper('a4', 'portrait')
            ->stream($tagihan->nomor.'.pdf');
    }

    // ── Libur nasional (satu daftar untuk semua sekolah) ──

    public function liburNasional()
    {
        return view('platform.libur-nasional', [
            'title' => 'Libur Nasional', 'breadcumd' => 'Platform', 'breadcumd1' => 'Libur Nasional',
            'items' => LiburNasional::orderBy('tanggal')->get(),
        ]);
    }

    public function liburNasionalSimpan(Request $request)
    {
        $d = $request->validate(['daftar' => 'required|string|max:20000']);
        $tersimpan = 0;
        $gagal = [];

        foreach (preg_split('/\r\n|\r|\n/', $d['daftar']) as $baris) {
            $baris = trim($baris);
            if ($baris === '') {
                continue;
            }

            [$tgl, $ket] = array_pad(array_map('trim', explode('|', $baris, 2)), 2, '');

            try {
                $c = Carbon::createFromFormat('Y-m-d', $tgl)->startOfDay();
            } catch (Throwable) {
                $gagal[] = $baris;

                continue;
            }

            LiburNasional::updateOrCreate(['tanggal' => $c->toDateString()], ['keterangan' => $ket !== '' ? $ket : 'Libur nasional']);
            $tersimpan++;
        }

        $redirect = back()->with('sukses', "{$tersimpan} tanggal disimpan.");

        return $gagal ? $redirect->withErrors(['msg' => 'Format salah pada: '.implode('; ', $gagal)]) : $redirect;
    }

    public function liburNasionalHapus(LiburNasional $liburNasional)
    {
        $liburNasional->delete();

        return back()->with('sukses', 'Tanggal dihapus.');
    }
}