<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PengajuanIzin;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PengajuanIzinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PengajuanIzinController extends Controller
{
    public function __construct(private PengajuanIzinService $service)
    {
    }

    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:menunggu,disetujui,ditolak']);
        $user = $request->user();

        $q = $this->service->queryUntuk($user);
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        $items = $q->paginate(15)->withQueryString();
        $boleh = $items->getCollection()->mapWithKeys(fn ($p) => [$p->id => $this->service->bolehMemproses($user, $p)]);

        return view('pengajuan.index', [
            'title' => 'Izin / Sakit',
            'breadcumd' => 'Izin / Sakit',
            'items' => $items,
            'boleh' => $boleh,
            'bisaMengajukan' => $user->hasRole(User::ROLE_ORANG_TUA, User::ROLE_GURU),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole(User::ROLE_ORANG_TUA)) {
            $siswa = $user->orangTua?->anak()->where('is_aktif', true)->orderBy('nama')->get(['id', 'nama']) ?? collect();
        } else {
            $ids = DB::table('kelas_siswa')
                ->join('kelas', 'kelas.id', '=', 'kelas_siswa.kelas_id')
                ->where('kelas.wali_kelas_id', $user->guru?->id)
                ->where('kelas.tahun_ajaran_id', TahunAjaran::aktif()?->id)
                ->pluck('kelas_siswa.siswa_id');
            $siswa = Siswa::whereIn('id', $ids)->orderBy('nama')->get(['id', 'nama']);
        }

        return view('pengajuan.create', [
            'title' => 'Buat Pengajuan',
            'breadcumd' => 'Izin / Sakit',
            'breadcumd1' => 'Buat pengajuan',
            'siswa' => $siswa, 'isGuru' => $user->hasRole(User::ROLE_GURU)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => 'nullable|integer|exists:siswa,id',
            'jenis' => 'required|in:sakit,izin,dinas_luar',
            'tanggal_mulai' => 'required|date_format:Y-m-d',
            'tanggal_selesai' => 'required|date_format:Y-m-d|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:500',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $p = $this->service->ajukan($request->user(), $data, $request->file('lampiran'));
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->withErrors(['msg' => $e->getMessage()]);
        }

        return redirect()->route('pengajuan.index')->with('sukses',
            $p->status === PengajuanIzin::STATUS_DISETUJUI
                ? 'Pengajuan dicatat dan langsung disetujui.'
                : 'Pengajuan terkirim, menunggu persetujuan.');
    }

    public function setujui(Request $request, PengajuanIzin $pengajuan)
    {
        try {
            $this->service->setujui($request->user(), $pengajuan);
        } catch (HttpExceptionInterface $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }

        return back()->with('sukses', 'Pengajuan disetujui, absensi terisi otomatis.');
    }

    public function tolak(Request $request, PengajuanIzin $pengajuan)
    {
        $data = $request->validate(['catatan_penolakan' => 'required|string|max:255']);

        try {
            $this->service->tolak($request->user(), $pengajuan, $data['catatan_penolakan']);
        } catch (HttpExceptionInterface $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }

        return back()->with('sukses', 'Pengajuan ditolak.');
    }
}
