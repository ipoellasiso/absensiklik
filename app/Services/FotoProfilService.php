<?php
// Simpan di: app/Services/FotoProfilService.php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Foto siswa dan guru. Disimpan di disk PRIVAT (storage/app), bukan folder publik,
 * dan hanya dikirim lewat rute yang mengecek login.
 */
class FotoProfilService
{
    private const LEBAR = 300;
    private const TINGGI = 400; // potret 3:4, cocok untuk kartu dan avatar

    /** Terapkan unggahan 'foto' atau centang 'hapus_foto' dari form. */
    public function terapkan(Model $model, string $jenis, Request $request): void
    {
        if ($request->hasFile('foto')) {
            $lama = $model->foto;
            $baru = $this->simpan($request->file('foto'), "foto-profil/{$model->sekolah_id}/{$jenis}");
            $model->forceFill(['foto' => $baru])->save();
            $this->hapus($lama);
        } elseif ($request->boolean('hapus_foto') && $model->foto) {
            $lama = $model->foto;
            $model->forceFill(['foto' => null])->save();
            $this->hapus($lama);
        }
    }

    /** Respons gambar untuk rute yang sudah dilindungi login. */
    public function kirim(?string $path)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    private function hapus(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function simpan(UploadedFile $file, string $folder): string
    {
        $isi = $this->kecilkan($file);

        if ($isi === null) { // GD tidak tersedia atau file tidak terbaca: simpan apa adanya
            return $file->store($folder, 'local');
        }

        $path = $folder.'/'.Str::random(40).'.jpg';
        Storage::disk('local')->put($path, $isi);

        return $path;
    }

    /** Putar sesuai EXIF, potong tengah ke rasio 3:4, kecilkan jadi 300x400 JPEG. */
    private function kecilkan(UploadedFile $file): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $src = @imagecreatefromstring((string) @file_get_contents($file->getRealPath()));
        if (!$src) {
            return null;
        }

        if (function_exists('exif_read_data') && $file->getMimeType() === 'image/jpeg') {
            $exif = @exif_read_data($file->getRealPath());
            $sudut = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($sudut && ($putar = imagerotate($src, $sudut, 0))) {
                $src = $putar;
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $rasio = self::LEBAR / self::TINGGI;

        if ($w / $h > $rasio) {
            $cw = (int) round($h * $rasio);
            $ch = $h;
            $x = (int) (($w - $cw) / 2);
            $y = 0;
        } else {
            $cw = $w;
            $ch = (int) round($w / $rasio);
            $x = 0;
            $y = (int) (($h - $ch) / 2);
        }

        $dst = imagecreatetruecolor(self::LEBAR, self::TINGGI);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // latar putih untuk PNG transparan
        imagecopyresampled($dst, $src, 0, 0, $x, $y, self::LEBAR, self::TINGGI, $cw, $ch);

        ob_start();
        imagejpeg($dst, null, 85);
        $jpg = ob_get_clean();

        return $jpg ?: null;
    }
}