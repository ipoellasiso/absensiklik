<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGuru extends Model
{
    use BelongsToSekolah;
    protected $table = 'absensi_guru';

    public const STATUS = ['hadir', 'terlambat', 'sakit', 'izin', 'dinas_luar', 'tanpa_keterangan'];

    protected $fillable = [
        'guru_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'status',
        'lat_masuk', 'lng_masuk', 'lat_pulang', 'lng_pulang', 'jarak_masuk_m', 'jarak_pulang_m',
        'foto_masuk', 'foto_pulang',
        'sumber', 'pengajuan_izin_id', 'catatan', 'dicatat_oleh',
    ];

    protected $casts = ['tanggal' => 'date:Y-m-d'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}