<?php

use Illuminate\Support\Facades\Route;

// =============================================
// AUTH CONTROLLERS
// =============================================
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;

// =============================================
// WEB CONTROLLERS
// =============================================
use App\Http\Controllers\Web\AbsensiController;
use App\Http\Controllers\Web\PengajuanIzinController;
use App\Http\Controllers\Web\KehadiranGuruController;
use App\Http\Controllers\Web\LaporanController;
use App\Http\Controllers\Web\LanggananController;

// =============================================
// ADMIN SEKOLAH CONTROLLERS
// =============================================
use App\Http\Controllers\Web\Admin\GuruController;
use App\Http\Controllers\Web\Admin\KelasController;
use App\Http\Controllers\Web\Admin\PengaturanController;
use App\Http\Controllers\Web\Admin\TahunAjaranController;
use App\Http\Controllers\Web\Admin\KartuController;
use App\Http\Controllers\Web\Admin\NaikKelasController;
use App\Http\Controllers\Web\Admin\OrangTuaController;
use App\Http\Controllers\Web\Admin\SiswaController;
use App\Http\Controllers\Web\Admin\HariLiburController;

// =============================================
// PLATFORM CONTROLLERS
// =============================================
use App\Http\Controllers\Web\Platform\SekolahController as PlatformSekolahController;
use App\Http\Controllers\Web\Platform\LanggananController as PlatformLanggananController;

use App\Http\Controllers\PendaftaranSekolahController;
use App\Http\Controllers\Web\MidtransController;


// =====================================================
// 1. AUTHENTICATION
// =====================================================

Route::view('/', 'landing', ['title' => 'Home'])->middleware('guest');

Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

});


// =====================================================
// 2. ROUTE SETELAH LOGIN
// =====================================================

Route::middleware('auth')->group(function () {

    // Logout tidak boleh terhalang status langganan
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');


    // =================================================
    // 3. PLATFORM - CENTRAL LICENSE SERVER
    // KHUSUS SUPER ADMIN
    //
    // Tidak menggunakan middleware:
    // sekolah.aktif
    // langganan.tulis
    //
    // Agar Super Admin tetap bisa mengelola platform
    // meskipun tidak memiliki sekolah aktif.
    // =================================================

    Route::middleware('role:super_admin')
        ->prefix('platform')
        ->name('platform.')
        ->group(function () {

            // -----------------------------------------
            // MANAJEMEN SEKOLAH
            // -----------------------------------------

            Route::controller(PlatformSekolahController::class)->group(function () {

                Route::get('/sekolah', 'index')
                    ->name('sekolah.index');

                Route::post('/sekolah', 'store')
                    ->name('sekolah.store');

                Route::put('/sekolah/{sekolah}', 'update')
                    ->name('sekolah.update');

                Route::post('/sekolah/{sekolah}/toggle', 'toggle')
                    ->name('sekolah.toggle');

                Route::post('/sekolah/{sekolah}/reset-password', 'resetPassword')
                    ->name('sekolah.reset-password');

            });


            // -----------------------------------------
            // MANAJEMEN PAKET LANGGANAN
            // -----------------------------------------

            Route::controller(PlatformLanggananController::class)->group(function () {

                Route::get('/paket', 'paket')
                    ->name('paket.index');

                Route::post('/paket', 'paketSimpan')
                    ->name('paket.simpan');

                Route::post('/paket/{paket}/toggle', 'paketToggle')
                    ->name('paket.toggle');


                // -------------------------------------
                // MANAJEMEN TAGIHAN
                // -------------------------------------

                Route::get('/tagihan', 'tagihan')
                    ->name('tagihan.index');

                Route::post('/tagihan', 'tagihanBuat')
                    ->name('tagihan.buat');

                Route::post('/tagihan/{tagihan}/konfirmasi', 'tagihanKonfirmasi')
                    ->name('tagihan.konfirmasi');

                Route::post('/tagihan/{tagihan}/batal', 'tagihanBatal')
                    ->name('tagihan.batal');

                Route::get('/tagihan/{tagihan}/bukti', 'tagihanBukti')
                    ->name('tagihan.bukti');

                Route::get('/tagihan/{tagihan}/pdf', 'tagihanPdf')
                    ->name('tagihan.pdf');


                // -------------------------------------
                // LIBUR NASIONAL PLATFORM
                // -------------------------------------

                Route::get('/libur-nasional', 'liburNasional')
                    ->name('libur-nasional.index');

                Route::post('/libur-nasional', 'liburNasionalSimpan')
                    ->name('libur-nasional.simpan');

                Route::delete('/libur-nasional/{liburNasional}', 'liburNasionalHapus')
                    ->name('libur-nasional.hapus');

            });

        });


    // =================================================
    // 4. AREA SEKOLAH
    // MEMERLUKAN SEKOLAH AKTIF
    // =================================================

    Route::middleware('sekolah.aktif')->group(function () {


        // ---------------------------------------------
        // DASHBOARD
        // ---------------------------------------------

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');


        // =================================================
        // 5. LANGGANAN SEKOLAH
        // TETAP BISA DIAKSES DALAM MODE BACA SAJA
        // =================================================

        Route::middleware('role:admin')
            ->prefix('langganan')
            ->name('langganan.')
            ->group(function () {

                Route::get('/', [LanggananController::class, 'index'])
                    ->name('index');

                Route::post('/tagihan', [LanggananController::class, 'buat'])
                    ->name('buat');

                Route::post('/tagihan/{tagihan}/bukti', [LanggananController::class, 'unggahBukti'])
                    ->name('bukti');

                Route::post('/tagihan/{tagihan}/batal', [LanggananController::class, 'batal'])
                    ->name('batal');

                Route::get('/tagihan/{tagihan}/pdf', [LanggananController::class, 'pdf'])
                    ->name('pdf');
                
                Route::post('/tagihan/{tagihan}/bayar', [LanggananController::class, 'bayar'])->name('bayar');
                Route::post('/tagihan/{tagihan}/sinkron', [LanggananController::class, 'sinkron'])->name('sinkron');

            });


        // =================================================
        // 6. FITUR OPERASIONAL SEKOLAH
        // MEMERLUKAN LANGGANAN YANG MENGIZINKAN PENULISAN
        // =================================================

        Route::middleware('langganan.tulis')->group(function () {


            // ---------------------------------------------
            // ABSENSI SISWA
            // ---------------------------------------------

            Route::middleware('role:guru,admin')->group(function () {

                Route::get('/absensi', [AbsensiController::class, 'index'])
                    ->name('absensi.index');

                Route::post('/absensi', [AbsensiController::class, 'store'])
                    ->name('absensi.store');

                Route::post('/absensi/scan', [AbsensiController::class, 'scan'])
                    ->name('absensi.scan');

            });


            // ---------------------------------------------
            // PENGAJUAN IZIN
            // ---------------------------------------------

            Route::get('/pengajuan', [PengajuanIzinController::class, 'index'])
                ->name('pengajuan.index');


            Route::middleware('role:orang_tua,guru')->group(function () {

                Route::get('/pengajuan/buat', [PengajuanIzinController::class, 'create'])
                    ->name('pengajuan.create');

                Route::post('/pengajuan', [PengajuanIzinController::class, 'store'])
                    ->name('pengajuan.store');

            });


            Route::middleware('role:guru,kepala_sekolah,admin')->group(function () {

                Route::patch('/pengajuan/{pengajuan}/setujui', [PengajuanIzinController::class, 'setujui'])
                    ->name('pengajuan.setujui');

                Route::patch('/pengajuan/{pengajuan}/tolak', [PengajuanIzinController::class, 'tolak'])
                    ->name('pengajuan.tolak');

            });


            // =================================================
            // 7. ADMINISTRASI SEKOLAH
            // =================================================

            Route::middleware('role:admin')
                ->prefix('admin')
                ->name('admin.')
                ->group(function () {


                    // -----------------------------------------
                    // PENGATURAN SEKOLAH
                    // -----------------------------------------

                    Route::controller(PengaturanController::class)->group(function () {

                        Route::get('/pengaturan', 'edit')
                            ->name('pengaturan.edit');

                        Route::put('/pengaturan', 'update')
                            ->name('pengaturan.update');

                    });


                    // -----------------------------------------
                    // TAHUN AJARAN DAN SEMESTER
                    // -----------------------------------------

                    Route::controller(TahunAjaranController::class)->group(function () {

                        Route::get('/tahun-ajaran', 'index')
                            ->name('tahun-ajaran.index');

                        Route::post('/tahun-ajaran', 'store')
                            ->name('tahun-ajaran.store');

                        Route::put('/tahun-ajaran/{tahunAjaran}', 'update')
                            ->name('tahun-ajaran.update');

                        Route::post('/tahun-ajaran/{tahunAjaran}/aktifkan', 'aktifkan')
                            ->name('tahun-ajaran.aktifkan');

                        Route::post('/semester/{semester}/aktifkan', 'aktifkanSemester')
                            ->name('semester.aktifkan');

                        Route::post('/semester/{semester}/kunci', 'kunciSemester')
                            ->name('semester.kunci');

                    });


                    // -----------------------------------------
                    // DATA GURU
                    // -----------------------------------------

                    Route::controller(GuruController::class)->group(function () {

                        Route::get('/guru', 'index')
                            ->name('guru.index');

                        Route::post('/guru', 'store')
                            ->name('guru.store');

                        Route::put('/guru/{guru}', 'update')
                            ->name('guru.update');

                        Route::post('/guru/{guru}/toggle', 'toggle')
                            ->name('guru.toggle');
                        
                        Route::get('/guru/{guru}/foto', 'foto')->name('guru.foto');

                    });


                    // -----------------------------------------
                    // DATA KELAS
                    // -----------------------------------------

                    Route::controller(KelasController::class)->group(function () {

                        Route::get('/kelas', 'index')
                            ->name('kelas.index');

                        Route::post('/kelas', 'store')
                            ->name('kelas.store');

                        Route::put('/kelas/{kelas}', 'update')
                            ->name('kelas.update');

                        Route::delete('/kelas/{kelas}', 'destroy')
                            ->name('kelas.destroy');

                    });


                    // -----------------------------------------
                    // DATA ORANG TUA
                    // -----------------------------------------

                    Route::controller(OrangTuaController::class)->group(function () {

                        Route::get('/orang-tua', 'index')
                            ->name('orang-tua.index');

                        Route::post('/orang-tua', 'store')
                            ->name('orang-tua.store');

                        Route::put('/orang-tua/{orangTua}', 'update')
                            ->name('orang-tua.update');

                        Route::post('/orang-tua/{orangTua}/toggle', 'toggle')
                            ->name('orang-tua.toggle');

                    });


                    // -----------------------------------------
                    // DATA SISWA
                    // -----------------------------------------

                    Route::controller(SiswaController::class)->group(function () {

                        Route::get('/siswa', 'index')
                            ->name('siswa.index');

                        Route::post('/siswa', 'store')
                            ->name('siswa.store');

                        Route::put('/siswa/{siswa}', 'update')
                            ->name('siswa.update');

                        Route::post('/siswa/{siswa}/toggle', 'toggle')
                            ->name('siswa.toggle');

                        Route::post('/siswa/{siswa}/qr-baru', 'qrBaru')
                            ->name('siswa.qr-baru');
                        
                        Route::get('/siswa/{siswa}/foto', 'foto')->name('siswa.foto');

                    });


                    // -----------------------------------------
                    // NAIK KELAS
                    // -----------------------------------------

                    Route::controller(NaikKelasController::class)->group(function () {

                        Route::get('/naik-kelas', 'index')
                            ->name('naik-kelas.index');

                        Route::post('/naik-kelas', 'proses')
                            ->name('naik-kelas.proses');

                    });


                    // -----------------------------------------
                    // HARI LIBUR SEKOLAH
                    // -----------------------------------------

                    Route::controller(HariLiburController::class)->group(function () {

                        Route::get('/hari-libur', 'index')
                            ->name('hari-libur.index');

                        Route::post('/hari-libur', 'simpan')
                            ->name('hari-libur.simpan');

                        Route::delete('/hari-libur/{hariLibur}', 'hapus')
                            ->name('hari-libur.hapus');

                        Route::post('/hari-libur/salin-nasional', 'salinNasional')
                            ->name('hari-libur.salin-nasional');

                    });


                    // -----------------------------------------
                    // CETAK KARTU SISWA
                    // -----------------------------------------

                    Route::get('/kartu', [KartuController::class, 'cetak'])
                        ->name('kartu.cetak');


                });


            // =================================================
            // 8. KEHADIRAN GURU
            // =================================================

            Route::middleware('role:admin,kepala_sekolah')->group(function () {

                Route::get('/kehadiran-guru', [KehadiranGuruController::class, 'index'])
                    ->name('kehadiran-guru.index');

                Route::post('/kehadiran-guru', [KehadiranGuruController::class, 'simpan'])
                    ->name('kehadiran-guru.simpan');

            });


            // =================================================
            // 9. LAPORAN
            // =================================================

            Route::middleware('role:guru,kepala_sekolah,admin')
                ->prefix('laporan')
                ->name('laporan.')
                ->group(function () {

                    Route::get('/', [LaporanController::class, 'index'])
                        ->name('index');

                    Route::get('/rekap-bulanan', [LaporanController::class, 'rekapBulanan'])
                        ->name('rekap-bulanan');

                    Route::get('/rekap-semester', [LaporanController::class, 'rekapSemester'])
                        ->name('rekap-semester');

                    Route::get('/rapor/{siswa}', [LaporanController::class, 'rapor'])
                        ->name('rapor');

                    Route::get('/guru-bulanan', [LaporanController::class, 'guruBulanan'])
                        ->middleware('role:admin,kepala_sekolah')
                        ->name('guru-bulanan');

                });


        }); // END langganan.tulis


        // =================================================
        // 10. FOTO ABSENSI GURU
        // =================================================

        Route::get('/kehadiran-guru/foto/{absensiGuru}/{jenis}', [KehadiranGuruController::class, 'foto'])
            ->middleware('role:admin,kepala_sekolah')
            ->whereIn('jenis', ['masuk', 'pulang'])
            ->name('kehadiran-guru.foto');


    }); // END sekolah.aktif


}); // END auth

Route::middleware('guest')->group(function () {
    Route::get('/daftar', [PendaftaranSekolahController::class, 'form'])->name('pendaftaran.form');
    Route::post('/daftar', [PendaftaranSekolahController::class, 'simpan'])
        ->middleware('throttle:5,1')
        ->name('pendaftaran.simpan');
});

Route::post('/midtrans/notifikasi', [MidtransController::class, 'notifikasi'])->name('midtrans.notifikasi');