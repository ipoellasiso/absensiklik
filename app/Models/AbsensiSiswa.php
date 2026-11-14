<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSekolah;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    use BelongsToSekolah;
    protected $table = 'absensi_siswa';

    public const HADIR = 'hadir';
    public const SAKIT = 'sakit';
    public const IZIN = 'izin';
    public const ALPA = 'alpa';
    public const TERLAMBAT = 'terlambat';

    public const SUMBER_MANUAL = 'manual';
    public const SUMBER_QR = 'qr';
    public const SUMBER_PENGAJUAN = 'pengajuan';
    public const SUMBER_OTOMATIS = 'otomatis';

    protected $fillable = [
        'siswa_id', 'kelas_id', 'tanggal', 'status', 'jam_masuk', 'catatan',
        'sumber', 'pengajuan_izin_id', 'dicatat_oleh',
    ];
    
    protected $casts = ['tanggal' => 'date:Y-m-d'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengajuanIzin(): BelongsTo
    {
        return $this->belongsTo(PengajuanIzin::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
