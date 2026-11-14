<?php

namespace App\Services;

use App\Models\Paket;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LanggananService
{
    public function buatTagihan(Sekolah $sekolah, Paket $paket, int $bulan = 12, ?int $jumlah = null): Tagihan
    {
        abort_unless($paket->is_aktif, 422, 'Paket tidak tersedia.');

        if (Tagihan::where('sekolah_id', $sekolah->id)->where('status', 'menunggu')->exists()) {
            throw ValidationException::withMessages([
                'msg' => ['Masih ada tagihan yang belum dibayar. Selesaikan atau batalkan dulu.'],
            ]);
        }

        return Tagihan::create([
            'sekolah_id' => $sekolah->id,
            'paket_id' => $paket->id,
            'nomor' => $this->nomorBaru(),
            'paket_nama' => $paket->nama,
            'batas_siswa' => $paket->batas_siswa,
            'lama_bulan' => $bulan,
            'jumlah' => $jumlah ?? (int) round($paket->harga_per_tahun * $bulan / 12),
        ]);
    }

    /** Dikonfirmasi lunas: masa aktif bertambah, disambung dari tanggal berakhir jika belum habis. */
    public function konfirmasiLunas(Tagihan $tagihan, ?User $oleh = null): Tagihan
    {
        abort_unless($tagihan->status === 'menunggu', 422, 'Tagihan sudah diproses.');

        return DB::transaction(function () use ($tagihan, $oleh) {
            $sekolah = Sekolah::lockForUpdate()->findOrFail($tagihan->sekolah_id);

            $mulai = ($sekolah->berlangganan_sampai && $sekolah->berlangganan_sampai->gte(today()))
                ? $sekolah->berlangganan_sampai->copy()->addDay()
                : today();
            $selesai = $mulai->copy()->addMonthsNoOverflow($tagihan->lama_bulan)->subDay();

            $tagihan->update([
                'status' => 'lunas',
                'periode_mulai' => $mulai,
                'periode_selesai' => $selesai,
                'dibayar_pada' => now(),
                // 'dikonfirmasi_oleh' => $oleh->id,
                'dikonfirmasi_oleh' => $oleh?->id,
            ]);

            $sekolah->update(['paket_id' => $tagihan->paket_id, 'berlangganan_sampai' => $selesai]);

            foreach (User::where('sekolah_id', $sekolah->id)->where('role', User::ROLE_ADMIN)->where('is_active', true)->get() as $admin) {
                Notifier::kirim(
                    $admin->id,
                    'langganan_lunas',
                    'Pembayaran diterima',
                    "Tagihan {$tagihan->nomor} lunas. Langganan aktif sampai {$selesai->format('d/m/Y')}.",
                    ['tagihan_id' => $tagihan->id]
                );
            }

            return $tagihan->fresh();
        });
    }

    /** Tolak penambahan siswa aktif yang melewati batas paket. Dipanggil dalam konteks sekolah. */
    public function pastikanKuotaSiswa(Sekolah $sekolah, int $tambah = 1): void
    {
        $batas = $sekolah->batasSiswa();

        if ($batas === null) {
            return;
        }

        if (Siswa::where('is_aktif', true)->count() + $tambah > $batas) {
            throw ValidationException::withMessages([
                'msg' => ["Batas paket {$batas} siswa aktif sudah tercapai. Tingkatkan paket di menu Langganan."],
            ]);
        }
    }

    /** Data untuk PDF tagihan atau kwitansi. */
    public function dataFaktur(Tagihan $t): array
    {
        return [
            't' => $t,
            'sekolah' => $t->sekolah,
            'judul' => $t->status === 'lunas' ? 'KWITANSI' : 'TAGIHAN',
            'penerbit' => config('langganan.penerbit'),
            'kontak' => config('langganan.kontak'),
            'rekening' => config('langganan.rekening'),
        ];
    }

    private function nomorBaru(): string
    {
        $tahun = now()->year;
        $urut = Tagihan::whereYear('created_at', $tahun)->count() + 1;

        do {
            $nomor = sprintf('INV-%d-%04d', $tahun, $urut++);
        } while (Tagihan::where('nomor', $nomor)->exists());

        return $nomor;
    }
}