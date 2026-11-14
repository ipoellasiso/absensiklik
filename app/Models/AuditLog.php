<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToSekolah;
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = ['user_id', 'aksi', 'tabel', 'record_id', 'nilai_lama', 'nilai_baru', 'ip_address'];

    protected $casts = [
        'nilai_lama' => 'array',
        'nilai_baru' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function sekolahWajib(): bool
    {
        return false;
    }

}
