<?php
// Simpan di: app/Models/Concerns/PunyaLangganan.php (timpa file lama)
// Memakai kolom yang SUDAH ADA di tabel sekolah: is_aktif, uji_coba_sampai, berlangganan_sampai.
// Di model Sekolah tambahkan $casts: 'uji_coba_sampai' => 'date', 'berlangganan_sampai' => 'date'

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * @property bool $is_aktif
 * @property \Illuminate\Support\Carbon|string|null $uji_coba_sampai
 * @property \Illuminate\Support\Carbon|string|null $berlangganan_sampai
 */
trait PunyaLangganan
{
    /** trial | aktif | kadaluarsa | ditangguhkan */
    public function statusLangganan(): string
    {
        if (!$this->is_aktif) {
            return 'ditangguhkan';
        }

        if ($this->masihBerlaku($this->berlangganan_sampai)) {
            return 'aktif';
        }

        if ($this->masihBerlaku($this->uji_coba_sampai)) {
            return 'trial';
        }

        return 'kadaluarsa';
    }

    public function batasLangganan(): ?Carbon
    {
        return match ($this->statusLangganan()) {
            'aktif' => Carbon::parse($this->berlangganan_sampai),
            'trial' => Carbon::parse($this->uji_coba_sampai),
            default => null,
        };
    }

    public function sisaHari(): int
    {
        $batas = $this->batasLangganan();

        if (!$batas) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($batas->copy()->startOfDay(), false));
    }

    /** false = mode baca-saja */
    public function bisaTulis(): bool
    {
        return in_array($this->statusLangganan(), ['trial', 'aktif'], true);
    }

    private function masihBerlaku($tanggal): bool
    {
        return $tanggal && Carbon::parse($tanggal)->endOfDay()->isFuture();
    }
}