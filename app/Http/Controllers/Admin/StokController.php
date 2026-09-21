<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStokMutationRequest;
use App\Models\BahanBaku;
use App\Models\StokMutation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class StokController extends Controller
{
    /**
     * Show the stock overview with availability and low-stock indicators.
     */
    public function index(Request $request): View
    {
        $bahanBaku = BahanBaku::query()
            ->orderBy('nama')
            ->paginate(15);

        return view('stok.index', [
            'bahanBaku' => $bahanBaku,
            'stokTersedia' => BahanBaku::stokTersediaPerBahan(),
            'routePrefix' => 'admin',
            'canCreate' => true,
        ]);
    }

    /**
     * Show the paginated history of stock mutations.
     */
    public function history(): View
    {
        return view('stok.history', [
            'mutasi' => $this->getRiwayat(),
            'routePrefix' => 'admin',
        ]);
    }

    /**
     * Show the form for recording a stock mutation.
     */
    public function create(): View
    {
        return view('stok.create', [
            'bahanBaku' => BahanBaku::orderBy('nama')->get(),
            'routePrefix' => 'admin',
            'admin' => true,
        ]);
    }

    /**
     * Record a stock mutation (masuk, keluar, or penyesuaian).
     */
    public function store(StoreStokMutationRequest $request): RedirectResponse
    {
        StokMutation::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.stok.index')->with('status', 'Mutasi stok berhasil dicatat.');
    }

    /**
     * Query the shared mutation history.
     */
    protected function getRiwayat(): LengthAwarePaginator
    {
        return StokMutation::query()
            ->with(['bahanBaku', 'user'])
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15);
    }
}
