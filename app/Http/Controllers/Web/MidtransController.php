<?php
// Simpan di: app/Http/Controllers/Web/MidtransController.php
// Rute webhook ini TANPA login dan TANPA CSRF (lihat langkah di penjelasan).

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function notifikasi(Request $request, MidtransService $midtrans)
    {
        $p = $request->all();

        if (!$midtrans->signatureValid($p)) {
            abort(403, 'Signature tidak valid.');
        }

        // order_id = NOMOR-TIMESTAMP, jadi nomor tagihan adalah bagian sebelum tanda "-" terakhir
        $orderId = (string) $p['order_id'];
        $pos = strrpos($orderId, '-');
        $tagihan = $pos === false ? null : Tagihan::where('nomor', substr($orderId, 0, $pos))->first();

        // Order tidak dikenal (misalnya tes dari dashboard Midtrans): balas 200 agar tidak diulang terus
        if ($tagihan) {
            $midtrans->terapkan($tagihan, $p);
        }

        return response()->json(['ok' => true]);
    }
}