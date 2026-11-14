<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\LiburNasional;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    public function index()
    {
        [$dari, $sampai, $ta] = $this->rentang();

        $rows = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])->orderBy('tanggal')->get();
        $nasional = LiburNasional::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])->get();
        $belumDisalin = $nasional->filter(fn ($n) => ! $rows->contains(fn ($h) => $h->tanggal->isSameDay($n->tanggal)))->count();

        return view('hari-libur.index', [
            'title' => 'Hari Libur',
            'breadcumd' => 'Data Master',
            'breadcumd1' => 'Hari Libur',
            'rows' => $rows,
            'ta' => $ta,
            'dari' => $dari,
            'sampai' => $sampai,
            'belumDisalin' => $belumDisalin,
        ]);
    }

    public function simpan(Request $request)
    {
        $d = $request->validate([
            'tanggal_mulai' => 'required|date_format:Y-m-d',
            'tanggal_selesai' => 'nullable|date_format:Y-m-d|after_or_equal:tanggal_mulai',
            'keterangan' => 'required|string|max:255',
        ]);

        $mulai = Carbon::parse($d['tanggal_mulai']);
        $selesai = Carbon::parse($d['tanggal_selesai'] ?? $d['tanggal_mulai']);
        abort_if($mulai->diffInDays($selesai) > 60, 422, 'Rentang maksimal 60 hari.');

        $n = 0;
        foreach (CarbonPeriod::create($mulai, $selesai) as $tgl) {
            if ($tgl->isWeekend()) {
                continue; // akhir pekan sudah otomatis bukan hari efektif
            }
            $h = HariLibur::firstOrCreate(['tanggal' => $tgl->toDateString()], ['keterangan' => $d['keterangan']]);
            $n += (int) $h->wasRecentlyCreated;
        }

        return back()->with('sukses', "{$n} hari libur ditambahkan.");
    }

    public function hapus(HariLibur $hariLibur)
    {
        $hariLibur->delete();

        return back()->with('sukses', 'Hari libur dihapus.');
    }

    public function salinNasional()
    {
        [$dari, $sampai] = $this->rentang();
        $n = 0;

        foreach (LiburNasional::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])->get() as $libur) {
            $h = HariLibur::firstOrCreate(['tanggal' => $libur->tanggal->toDateString()], ['keterangan' => $libur->keterangan]);
            $n += (int) $h->wasRecentlyCreated;
        }

        return back()->with('sukses', "{$n} libur nasional disalin ke kalender sekolah.");
    }

    /** Rentang yang ditampilkan: tahun ajaran aktif, atau tahun berjalan jika belum ada. */
    private function rentang(): array
    {
        $ta = TahunAjaran::aktif();

        return $ta
            ? [$ta->tanggal_mulai->copy(), $ta->tanggal_selesai->copy(), $ta]
            : [today()->startOfYear(), today()->endOfYear(), null];
    }
}