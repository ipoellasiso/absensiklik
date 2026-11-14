<?php

use App\Http\Controllers\Api\AbsensiGuruController;
use App\Http\Controllers\Api\AbsensiSiswaController;
use App\Http\Controllers\Api\AnakController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PengajuanIzinController;
use Illuminate\Support\Facades\Route;

// Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Route::middleware('auth:sanctum')->group(function () {
Route::middleware(['auth:sanctum', 'sekolah.aktif', 'langganan.tulis'])->group(function () { // api.php
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/foto', [AuthController::class, 'foto']);

    // Wali kelas / admin: absensi siswa
    Route::middleware('role:guru,admin')->group(function () {
        Route::get('/kelas-saya', [AbsensiSiswaController::class, 'kelasSaya']);
        Route::post('/absensi-siswa', [AbsensiSiswaController::class, 'simpan']);
        Route::post('/absensi-siswa/scan', [AbsensiSiswaController::class, 'scan']);
    });
    Route::get('/kelas/{kelas}/siswa', [AbsensiSiswaController::class, 'siswa'])
        ->middleware('role:guru,admin,kepala_sekolah');

    // Orang tua
    Route::middleware('role:orang_tua')->group(function () {
        Route::get('/anak', [AnakController::class, 'index']);
        Route::get('/anak/{siswa}/absensi', [AnakController::class, 'absensi']);
    });

    // Pengajuan izin / sakit
    Route::get('/pengajuan-izin', [PengajuanIzinController::class, 'index']);
    Route::post('/pengajuan-izin', [PengajuanIzinController::class, 'store'])->middleware('role:orang_tua,guru');
    Route::middleware('role:guru,kepala_sekolah,admin')->group(function () {
        Route::patch('/pengajuan-izin/{pengajuan}/setujui', [PengajuanIzinController::class, 'setujui']);
        Route::patch('/pengajuan-izin/{pengajuan}/tolak', [PengajuanIzinController::class, 'tolak']);
    });

    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua']);
    Route::post('/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'baca']);

    Route::middleware('role:guru,kepala_sekolah')->prefix('absensi-guru')->group(function () {
        Route::get('/hari-ini', [AbsensiGuruController::class, 'hariIni']);
        Route::post('/masuk', [AbsensiGuruController::class, 'masuk']);
        Route::post('/pulang', [AbsensiGuruController::class, 'pulang']);
        Route::get('/riwayat', [AbsensiGuruController::class, 'riwayat']);
    });

});
