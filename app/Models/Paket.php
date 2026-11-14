<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    protected $table = 'paket';

    protected $fillable = ['nama', 'batas_siswa', 'harga_per_tahun', 'keterangan', 'is_aktif'];

    protected $casts = ['is_aktif' => 'boolean'];
}