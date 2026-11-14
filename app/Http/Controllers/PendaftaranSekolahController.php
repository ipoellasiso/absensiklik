<?php
// Simpan di: app/Http/Controllers/PendaftaranSekolahController.php
//
// BAGIAN YANG PERLU DISESUAIKAN dengan struktur Bang ditandai "SESUAIKAN".

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Semester;
use App\Models\Pengaturan;

class PendaftaranSekolahController extends Controller
{
    private const HARI_UJI_COBA = 30;

    public function form()
    {
        return view('pendaftaran.daftar', ['title' => 'Daftar Sekolah']);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'npsn'         => ['required', 'digits:8', Rule::unique(Sekolah::class, 'npsn')],
            'alamat'       => ['required', 'string', 'max:255'],
            'nama_admin'   => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'no_wa'        => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'setuju'       => ['accepted'],
            'username' => ['required', 'alpha_dash', 'min:4', 'max:30', Rule::unique(User::class, 'username')],
        ], [
            'npsn.unique'   => 'NPSN ini sudah terdaftar. Hubungi admin jika sekolah Anda belum pernah mendaftar.',
            'email.unique'  => 'Email ini sudah dipakai.',
            'no_wa.regex'   => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
            'setuju.accepted' => 'Anda harus menyetujui syarat penggunaan.',
            'username.unique' => 'Username ini sudah dipakai.',
        ]);

        $admin = DB::transaction(function () use ($data) {
            // 1. Sekolah + masa uji coba
            // SESUAIKAN: nama kolom nama/alamat sekolah di tabel Bang
            // $sekolah = Sekolah::create([
            //     'nama'              => $data['nama_sekolah'],
            //     'alamat'            => $data['alamat'],
            //     'npsn'              => $data['npsn'],
            //     'email_kontak'      => $data['email'],
            //     'no_wa'             => $this->normalisasiWa($data['no_wa']),
            //     'status_langganan'  => 'trial',
            //     'trial_berakhir_at' => now()->addDays(self::HARI_UJI_COBA)->endOfDay(),
            // ]);

            $sekolah = Sekolah::create([
                'nama'          => $data['nama_sekolah'],
                'alamat'        => $data['alamat'],   // hapus baris ini jika tabel sekolah tidak punya kolom alamat
                'npsn'          => $data['npsn'],
                'kode'          => Str::slug(Str::limit($data['nama_sekolah'], 40, '')) . '-' . $data['npsn'],
                'email_kontak'  => $data['email'],
                'no_wa'         => $this->normalisasiWa($data['no_wa']),
                'is_aktif'      => true,
                'uji_coba_sampai' => now()->addDays(self::HARI_UJI_COBA)->toDateString(),
            ]);

            // 3. Tahun ajaran aktif + dua semester
            $bulan = now()->month;
            $mulai = $bulan >= 7 ? now()->year : now()->year - 1;
            $ganjilAktif = $bulan >= 7;

            $tahunAjaran = $this->buat(TahunAjaran::class, [
                'sekolah_id'      => $sekolah->id,
                'nama'            => $mulai . '/' . ($mulai + 1),
                'tanggal_mulai'   => $mulai . '-07-01',
                'tanggal_selesai' => ($mulai + 1) . '-06-30',
                // 'semester'   => $bulan >= 7 ? 'ganjil' : 'genap',
                'is_aktif'        => true,
            ]);

            $this->buat(Semester::class, [
                'sekolah_id'      => $sekolah->id,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'nama'            => 'Ganjil',
                'tanggal_mulai'   => $mulai . '-07-01',
                'tanggal_selesai' => $mulai . '-12-31',
                'is_aktif'        => $ganjilAktif,
            ]);

            $this->buat(Semester::class, [
                'sekolah_id'      => $sekolah->id,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'nama'            => 'Genap',
                'tanggal_mulai'   => ($mulai + 1) . '-01-01',
                'tanggal_selesai' => ($mulai + 1) . '-06-30',
                'is_aktif'        => !$ganjilAktif,
            ]);

            // 2. Admin sekolah
            // SESUAIKAN: kolom role & sekolah_id di tabel users.
            // Jika model User sudah punya cast 'password' => 'hashed', ganti Hash::make(...) dengan password mentah.
            $admin = User::create([
                'name'       => $data['nama_admin'],
                'username'   => $data['username'],
                'email'      => $data['email'],
                'no_hp'      => $this->normalisasiWa($data['no_wa']),
                'password'   => Hash::make($data['password']),
                'role'       => 'admin',
                'sekolah_id' => $sekolah->id,
                
            ]);

            // 3. Tahun ajaran aktif
            // SESUAIKAN: kolom tahun ajaran (nama/semester/aktif).
            // $bulan  = now()->month;
            // $tahun  = now()->year;
            // $mulai  = $bulan >= 7 ? $tahun : $tahun - 1;
            // $tahunAjaran = TahunAjaran::create([
            //     'sekolah_id' => $sekolah->id,
            //     'nama'       => $mulai . '/' . ($mulai + 1),
            //     'semester'   => $bulan >= 7 ? 'ganjil' : 'genap',
            //     'aktif'      => true,
            // ]);

            // 4. Kelas 1-6 bawaan
            // SESUAIKAN: kolom kelas (nama/tingkat/tahun_ajaran_id).
            foreach (range(1, 6) as $tingkat) {
                $this->buat(Kelas::class, [
                    'sekolah_id'      => $sekolah->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'nama'            => 'Kelas ' . $tingkat,
                    'tingkat'         => $tingkat,
                ]);
            }

            // 5. Pengaturan bawaan (jam, radius, dll. memakai nilai default kolom)
            $this->buat(Pengaturan::class, [
                'sekolah_id'   => $sekolah->id,
                'nama_sekolah' => $data['nama_sekolah'],
                'npsn'         => $data['npsn'],
            ]);

            return $admin;
        });

        Auth::login($admin);
        $request->session()->regenerate();

        // SESUAIKAN: tujuan setelah daftar (dashboard admin sekolah)
        return redirect('/dashboard')->with(
            'success',
            'Pendaftaran berhasil. Masa uji coba ' . self::HARI_UJI_COBA . ' hari sudah aktif.'
        );
    }

    private function buat(string $model, array $atribut)
    {
        $m = new $model();
        $m->forceFill($atribut)->save();

        return $m;
    }

    private function normalisasiWa(string $nomor): string
    {
        $nomor = preg_replace('/\D+/', '', $nomor);

        if (str_starts_with($nomor, '0')) {
            return '62' . substr($nomor, 1);
        }

        return $nomor;
    }
}