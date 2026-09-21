<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\StokMutation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StokController extends Controller
{
    /**
     * Show the stock overview with availability and low-stock indicators (read-only).
     */
    public function index(Request $request): View
    {
        $bahanBaku = BahanBaku::query()
            ->orderBy('nama')
            ->paginate(15);

        return view('stok.index', [
            'bahanBaku' => $bahanBaku,
            'stokTersedia' => BahanBaku::stokTersediaPerBahan(),
            'routePrefix' => 'kepala',
            'canCreate' => false,
        ]);
    }

    /**
     * Show the paginated history of stock mutations (read-only).
     */
    public function history(): View
    {
        return view('stok.history', [
            'mutasi' => StokMutation::query()
                ->with(['bahanBaku', 'user'])
                ->latest('tanggal')
                ->latest('id')
                ->paginate(15),
            'routePrefix' => 'kepala',
        ]);
    }
}
