<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    use BelongsToSekolah;
    protected $table = 'guru';

    protected $fillable = ['user_id', 'nip', 'jabatan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }

    public function pengajuanIzin(): HasMany
    {
        return $this->hasMany(PengajuanIzin::class);
    }
}
