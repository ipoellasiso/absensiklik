<?php

namespace App\Support;

use App\Models\AuditLog;

class AuditLogger
{
    public static function log(string $aksi, string $tabel, ?int $recordId, ?array $lama = null, ?array $baru = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'tabel' => $tabel,
            'record_id' => $recordId,
            'nilai_lama' => $lama,
            'nilai_baru' => $baru,
            'ip_address' => request()?->ip(),
        ]);
    }
}
