<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use \App\Models\Concerns\PunyaLangganan;

class Sekolah extends Model
{
    protected $table = 'sekolah';

    protected $fillable = [
        'nama', 
        'npsn', 
        'kode', 
        'is_aktif', 
        'paket_id', 
        'uji_coba_sampai', 
        'berlangganan_sampai',
        'email_kontak', 
        'no_wa', 
        'status_langganan',
        'trial_berakhir_at',
        'langganan_berakhir_at',
        'username',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'uji_coba_sampai' => 'date',
        'berlangganan_sampai' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function paket(): BelongsTo
    {
        return $this->belongsTo(Paket::class);
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    /** Dipakai header template: Auth::user()->sekolah->nama_sekolah */
    public function getNamaSekolahAttribute(): string
    {
        return $this->nama;
    }

    /**
     * Status langganan: uji_coba, aktif, tenggang, berakhir.
     * sisa_hari: hari menuju batas (negatif jika sudah lewat).
     */
    public function langganan(): array
    {
        $hariIni = today();
        $tenggang = (int) config('langganan.masa_tenggang_hari', 7);
        $bayar = $this->berlangganan_sampai;
        $uji = $this->uji_coba_sampai;

        if ($bayar) {
            $akhirTenggang = $bayar->copy()->addDays($tenggang);

            if ($bayar->gte($hariIni)) {
                return $this->bentuk('aktif', 'Berlangganan', $bayar, true);
            }
            if ($akhirTenggang->gte($hariIni)) {
                return $this->bentuk('tenggang', 'Masa tenggang', $akhirTenggang, true);
            }

            return $this->bentuk('berakhir', 'Berakhir', $akhirTenggang, false);
        }

        if ($uji && $uji->gte($hariIni)) {
            return $this->bentuk('uji_coba', 'Uji coba', $uji, true);
        }

        return $this->bentuk('berakhir', 'Berakhir', $uji, false);
    }

    public function bolehMenulis(): bool
    {
        return $this->langganan()['boleh_menulis'];
    }

    /** Batas siswa aktif. null = tidak dibatasi (misalnya sekolah lama tanpa paket). */
    public function batasSiswa(): ?int
    {
        if ($this->berlangganan_sampai) {
            return $this->paket?->batas_siswa;
        }

        return (int) config('langganan.batas_uji_coba_siswa', 150);
    }

    private function bentuk(string $status, string $label, ?CarbonInterface $sampai, bool $boleh): array
    {
        return [
            'status' => $status,
            'label' => $label,
            'sampai' => $sampai,
            'sisa_hari' => $sampai ? (int) today()->diffInDays($sampai->copy()->startOfDay(), false) : null,
            'boleh_menulis' => $boleh,
        ];
    }
}