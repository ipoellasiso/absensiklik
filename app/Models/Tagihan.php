<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tagihan extends Model
{
    protected $table = 'tagihan';

    protected $fillable = [
        'sekolah_id', 'paket_id', 'nomor', 'paket_nama', 'batas_siswa', 'lama_bulan', 'jumlah',
        'periode_mulai', 'periode_selesai', 'status', 'bukti', 'bukti_diunggah_pada',
        'dibayar_pada', 'dikonfirmasi_oleh', 'snap_token', 'gateway_ref', 'catatan',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
        'bukti_diunggah_pada' => 'datetime',
        'dibayar_pada' => 'datetime',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function paket(): BelongsTo
    {
        return $this->belongsTo(Paket::class);
    }
}