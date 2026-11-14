<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Paket;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\LanggananService;
use App\Support\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\MidtransService;

class LanggananController extends Controller
{
    public function __construct(private LanggananService $layanan)
    {
    }

    public function index()
    {
        $sekolah = auth()->user()->sekolah;

        return view('langganan.index', [
            'title' => 'Langganan',
            'breadcumd' => 'Langganan',
            'sekolah' => $sekolah,
            'info' => $sekolah->langganan(),
            'siswaAktif' => Siswa::where('is_aktif', true)->count(),
            'paket' => Paket::where('is_aktif', true)->orderBy('batas_siswa')->get(),
            'tagihan' => Tagihan::where('sekolah_id', $sekolah->id)->latest()->get(),
            'rekening' => config('langganan.rekening'),
        ]);
    }

    public function buat(Request $request)
    {
        $d = $request->validate(['paket_id' => 'required|exists:paket,id']);
        $paket = Paket::findOrFail($d['paket_id']);
        $siswaAktif = Siswa::where('is_aktif', true)->count();

        if ($siswaAktif > $paket->batas_siswa) {
            throw ValidationException::withMessages([
                'msg' => ["Siswa aktif saat ini ({$siswaAktif}) melebihi batas paket {$paket->nama} ({$paket->batas_siswa}). Pilih paket yang lebih besar."],
            ]);
        }

        $tagihan = $this->layanan->buatTagihan(auth()->user()->sekolah, $paket);

        return back()->with('sukses', "Tagihan {$tagihan->nomor} dibuat. Lakukan transfer lalu unggah bukti pembayaran.");
    }

    public function unggahBukti(Request $request, Tagihan $tagihan)
    {
        $this->pastikanMilikSendiri($tagihan);
        abort_unless($tagihan->status === 'menunggu', 422, 'Tagihan sudah diproses.');

        $request->validate(['bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048']);

        if ($tagihan->bukti) {
            Storage::disk('local')->delete($tagihan->bukti);
        }

        $tagihan->update([
            'bukti' => $request->file('bukti')->store('bukti-transfer/'.$tagihan->sekolah_id, 'local'),
            'bukti_diunggah_pada' => now(),
        ]);

        return back()->with('sukses', 'Bukti pembayaran terkirim. Kami akan memverifikasi dan mengonfirmasinya.');
    }

    public function batal(Tagihan $tagihan)
    {
        $this->pastikanMilikSendiri($tagihan);
        abort_unless($tagihan->status === 'menunggu', 422, 'Tagihan sudah diproses.');

        $tagihan->update(['status' => 'batal']);

        return back()->with('sukses', 'Tagihan dibatalkan.');
    }

    public function pdf(Tagihan $tagihan)
    {
        $this->pastikanMilikSendiri($tagihan);

        return Pdf::loadView('langganan.tagihan-pdf', $this->layanan->dataFaktur($tagihan))
            ->setPaper('a4', 'portrait')
            ->stream($tagihan->nomor.'.pdf');
    }

        public function bayar(Tagihan $tagihan, MidtransService $midtrans)
    {
        $this->pastikanMilikSendiri($tagihan);
        abort_unless($tagihan->status === 'menunggu', 422, 'Tagihan sudah diproses.');

        try {
            return response()->json(['token' => $midtrans->tokenUntuk($tagihan)]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal menghubungi Midtrans.'.(config('app.debug') ? ' '.$e->getMessage() : ''),
            ], 500);
        }
    }

    public function sinkron(Tagihan $tagihan, MidtransService $midtrans)
    {
        $this->pastikanMilikSendiri($tagihan);

        return response()->json(['status' => $midtrans->sinkron($tagihan)]);
    }

    /** Tagihan tidak memakai filter otomatis, jadi kepemilikan diperiksa manual. */
    private function pastikanMilikSendiri(Tagihan $tagihan): void
    {
        abort_unless($tagihan->sekolah_id === Tenant::id(), 404);
    }
}