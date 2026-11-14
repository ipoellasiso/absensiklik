<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use BelongsToSekolah;
    protected $table = 'semester';

    protected $fillable = ['tahun_ajaran_id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'is_aktif', 'is_terkunci'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_aktif' => 'boolean',
        'is_terkunci' => 'boolean',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function rekap(): HasMany
    {
        return $this->hasMany(RekapKehadiranSemester::class);
    }

    public static function aktif(): ?self
    {
        return static::where('is_aktif', true)->first();
    }
}
