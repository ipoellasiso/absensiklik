<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrangTua extends Model
{
    use BelongsToSekolah;
    protected $table = 'orang_tua';

    protected $fillable = ['user_id', 'no_wa', 'alamat'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anak(): HasMany
    {
        return $this->hasMany(Siswa::class, 'orang_tua_id');
    }
}
