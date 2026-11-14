<?php

namespace App\Support;

use App\Models\Notifikasi;

class Notifier
{
    public static function kirim(int $userId, string $tipe, string $judul, string $isi, array $data = []): void
    {
        Notifikasi::create([
            'user_id' => $userId,
            'tipe' => $tipe,
            'judul' => $judul,
            'isi' => $isi,
            'data' => $data ?: null,
        ]);

        // Tempat menambah pengiriman WhatsApp / push (FCM) di tahap berikutnya.
    }
}