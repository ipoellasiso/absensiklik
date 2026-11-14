<?php
// Simpan di: app/Services/MidtransService.php

namespace App\Services;

use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct(private LanggananService $layanan)
    {
        Config::$serverKey = (string) config('langganan.midtrans.server_key');
        Config::$isProduction = (bool) config('langganan.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /** Snap token untuk tagihan. Dipakai ulang selama masih baru (di bawah 20 jam). */
    public function tokenUntuk(Tagihan $t): string
    {
        if ($t->snap_token && $t->gateway_ref && $t->updated_at && $t->updated_at->gt(now()->subHours(20))) {
            return $t->snap_token;
        }

        $sekolah = $t->sekolah;
        $orderId = $t->nomor.'-'.now()->timestamp; // nomor tagihan + penanda percobaan

        $params = [
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int) $t->jumlah],
            'item_details' => [[
                'id' => 'PAKET-'.($t->paket_id ?? 0),
                'price' => (int) $t->jumlah,
                'quantity' => 1,
                'name' => Str::limit("Langganan {$t->paket_nama} {$t->lama_bulan} bln", 50, ''),
            ]],
            'customer_details' => array_filter([
                'first_name' => Str::limit((string) $sekolah?->nama, 50, ''),
                'email' => $sekolah?->email_kontak,
                'phone' => $sekolah?->no_wa,
            ]),
        ];

        $token = Snap::getSnapToken($params);
        $t->update(['snap_token' => $token, 'gateway_ref' => $orderId]);

        return $token;
    }

    /** Tanda tangan notifikasi Midtrans: sha512(order_id + status_code + gross_amount + server_key). */
    public function signatureValid(array $p): bool
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $k) {
            if (!isset($p[$k])) {
                return false;
            }
        }

        $hitung = hash('sha512', $p['order_id'].$p['status_code'].$p['gross_amount'].config('langganan.midtrans.server_key'));

        return hash_equals($hitung, (string) $p['signature_key']);
    }

    /** Tanyakan status ke Midtrans lalu terapkan. Dipakai setelah popup ditutup (jalan juga di lokal). */
    public function sinkron(Tagihan $t): string
    {
        if (!$t->gateway_ref) {
            return $t->status;
        }

        try {
            $p = json_decode(json_encode(Transaction::status($t->gateway_ref)), true);
        } catch (\Throwable $e) {
            return $t->status; // transaksi belum ada di Midtrans (popup ditutup sebelum memilih metode)
        }

        return $this->terapkan($t, (array) $p);
    }

    /** Terapkan status dari webhook atau sinkron. Aman dipanggil berulang. */
    public function terapkan(Tagihan $tagihan, array $p): string
    {
        return DB::transaction(function () use ($tagihan, $p) {
            $t = Tagihan::lockForUpdate()->findOrFail($tagihan->id);

            $status = $p['transaction_status'] ?? '';
            $fraud = $p['fraud_status'] ?? 'accept';
            $lunas = $status === 'settlement' || ($status === 'capture' && $fraud === 'accept');

            if ($t->status !== 'menunggu') {
                if ($lunas && $t->status === 'batal') {
                    Log::warning("Midtrans: pembayaran masuk untuk tagihan {$t->nomor} yang sudah dibatalkan.", $p);
                }

                return $t->status;
            }

            if ($lunas) {
                if ((int) round((float) ($p['gross_amount'] ?? 0)) !== (int) $t->jumlah) {
                    Log::warning("Midtrans: jumlah tidak cocok untuk tagihan {$t->nomor}.", $p);

                    return $t->status;
                }

                $t->update(['catatan' => trim(($t->catatan ? $t->catatan.' | ' : '').'Dibayar via Midtrans ('.($p['payment_type'] ?? '-').'), ref '.($p['transaction_id'] ?? '-'))]);

                return $this->layanan->konfirmasiLunas($t, null)->status;
            }

            if (in_array($status, ['expire', 'cancel', 'deny'], true)) {
                $t->update(['snap_token' => null, 'gateway_ref' => null]); // boleh membayar ulang
            }

            return $t->status;
        });
    }
}