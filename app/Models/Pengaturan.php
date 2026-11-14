<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSekolah;

class Pengaturan extends Model
{
    use BelongsToSekolah;
    protected $table = 'pengaturan';

    protected $fillable = [
        'nama_sekolah', 'npsn', 'jam_masuk', 'jam_pulang', 'jam_tutup_absensi', 'batas_terlambat_menit',
        'latitude', 'longitude', 'radius_meter', 'wajib_foto_guru', 'simpan_foto_hari',
        'batas_koreksi_hari', 'batas_pengajuan_mundur_hari',
    ];

    protected $casts = ['wajib_foto_guru' => 'boolean'];

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}
