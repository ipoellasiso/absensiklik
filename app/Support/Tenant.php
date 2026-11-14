<?php

namespace App\Support;

use App\Models\Sekolah;
use Closure;

class Tenant
{
    private static bool $dipaksa = false;
    private static ?int $paksa = null;

    /** Sekolah aktif: dipaksa manual (command, tinker) atau dari akun yang sedang login. */
    public static function id(): ?int
    {
        if (self::$dipaksa) {
            return self::$paksa;
        }

        $id = auth()->user()?->sekolah_id;

        return $id === null ? null : (int) $id;
    }

    /** Menjalankan kode dalam konteks satu sekolah (scheduler dan proses latar). */
    public static function sebagai(Sekolah|int $sekolah, Closure $fn): mixed
    {
        $lamaDipaksa = self::$dipaksa;
        $lamaPaksa = self::$paksa;

        self::$dipaksa = true;
        self::$paksa = $sekolah instanceof Sekolah ? $sekolah->id : $sekolah;

        try {
            return $fn();
        } finally {
            self::$dipaksa = $lamaDipaksa;
            self::$paksa = $lamaPaksa;
        }
    }

    /** Untuk tinker: Tenant::pakai(1). Tenant::pakai(null) untuk melepas. */
    public static function pakai(?int $id): void
    {
        self::$dipaksa = $id !== null;
        self::$paksa = $id;
    }
}