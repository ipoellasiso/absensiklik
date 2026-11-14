<?php

namespace App\Models;

use Carbon\CarbonInterface;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use BelongsToSekolah;
    protected $table = 'hari_libur';

    protected $fillable = ['tanggal', 'keterangan'];

    protected $casts = ['tanggal' => 'date'];

    public static function hariEfektif(CarbonInterface $tanggal): bool
    {
        return ! $tanggal->isWeekend()
            && ! static::whereDate('tanggal', $tanggal)->exists();
    }
}
