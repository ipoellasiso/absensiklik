<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapKehadiranSemester extends Model
{
    use BelongsToSekolah;
    protected $table = 'rekap_kehadiran_semester';

    protected $fillable = [
        'siswa_id', 'semester_id', 'kelas_id', 'hari_efektif',
        'hadir', 'sakit', 'izin', 'alpa', 'terlambat', 'persentase_kehadiran',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
