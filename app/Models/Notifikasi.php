<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = ['user_id', 'tipe', 'judul', 'isi', 'data', 'dibaca_pada'];

    protected $casts = [
        'data' => 'array',
        'dibaca_pada' => 'datetime',
    ];
}