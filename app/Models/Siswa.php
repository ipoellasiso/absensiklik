<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Support\Str;

class Siswa extends Model
{
    use BelongsToSekolah;
    protected $table = 'siswa';

    protected $fillable = [
        'nisn', 'nis', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'foto',
        'orang_tua_id', 'qr_token', 'is_aktif',
    ];

    protected $hidden = ['qr_token'];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'is_aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Siswa $siswa) {
            $siswa->qr_token ??= Str::random(48);
        });
    }

    public function regenerateQrToken(): void
    {
        $this->forceFill(['qr_token' => Str::random(48)])->save();
    }

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(OrangTua::class);
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa')
            ->withPivot('tahun_ajaran_id')
            ->withTimestamps();
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class);
    }

    public function pengajuanIzin(): HasMany
    {
        return $this->hasMany(PengajuanIzin::class);
    }
}
