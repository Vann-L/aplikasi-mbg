<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\Produksi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProduksiController extends Controller
{
    /**
     * Display a filtered and paginated listing of production runs (read-only).
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $status = $request->string('status')->toString();

        $produksi = Produksi::query()
            ->with('menu')
            ->when($tanggal !== '', fn ($query) => $query->whereDate('tanggal', $tanggal))
            ->when(in_array($status, Produksi::STATUS_FLOW, true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('produksi.index', [
            'produksi' => $produksi,
            'tanggal' => $tanggal,
            'status' => $status,
            'routePrefix' => 'kepala',
            'canCreate' => false,
        ]);
    }

    /**
     * Display the production detail (read-only).
     */
    public function show(Produksi $produksi): View
    {
        $produksi->load(['menu', 'createdBy', 'distribusis.sekolah', 'produksiBahans.bahanBaku']);

        return view('produksi.show', [
            'produksi' => $produksi,
            'stokTersedia' => BahanBaku::stokTersediaPerBahan(),
            'sekolahs' => new Collection,
            'bahanBakus' => new Collection,
            'routePrefix' => 'kepala',
            'canOperate' => false,
        ]);
    }
}
