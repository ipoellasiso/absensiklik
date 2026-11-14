<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SekolahScope;
use App\Models\Sekolah;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToSekolah
{
    protected static function bootBelongsToSekolah(): void
    {
        static::addGlobalScope(new SekolahScope());

        static::creating(function ($model) {
            if ($model->sekolah_id !== null) {
                return;
            }

            $id = Tenant::id();

            if ($id === null && static::sekolahWajib()) {
                throw new LogicException('Konteks sekolah belum ditetapkan. Gunakan Tenant::sebagai() atau Tenant::pakai().');
            }

            $model->sekolah_id = $id;
        });
    }

    protected static function sekolahWajib(): bool
    {
        return true;
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }
}