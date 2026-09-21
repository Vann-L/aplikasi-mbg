<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDistribusiPlanRequest;
use App\Http\Requests\StoreProduksiBahanRequest;
use App\Http\Requests\StoreProduksiRequest;
use App\Http\Requests\UpdateDistribusiPlanRequest;
use App\Http\Requests\UpdateHasilRequest;
use App\Http\Requests\UpdateProduksiStatusRequest;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\Menu;
use App\Models\Produksi;
use App\Models\ProduksiBahan;
use App\Models\Sekolah;
use App\Models\StokMutation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProduksiController extends Controller
{
    /**
     * Display a filtered and paginated listing of production runs.
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
            'routePrefix' => $this->prefix(),
            'canCreate' => true,
        ]);
    }

    /**
     * Show the form for creating a new production run.
     */
    public function create(): View
    {
        return view('produksi.create', [
            'menus' => Menu::where('status', Menu::STATUS_ACTIVE)->orderBy('nama')->get(),
            'routePrefix' => $this->prefix(),
        ]);
    }

    /**
     * Store a newly created production draft.
     */
    public function store(StoreProduksiRequest $request): RedirectResponse
    {
        $produksi = Produksi::create([
            ...$request->validated(),
            'status' => Produksi::STATUS_BELUM_DIMULAI,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route($this->prefix().'.produksi.show', $produksi)
            ->with('status', 'Produksi dibuat. Lengkapi rencana distribusi dan bahan produksi.');
    }

    /**
     * Display the production detail with demand, result, distributions, and ingredients.
     */
    public function show(Produksi $produksi): View
    {
        $produksi->load(['menu', 'createdBy', 'distribusis.sekolah', 'produksiBahans.bahanBaku']);

        return view('produksi.show', $this->showData($produksi));
    }

    /**
     * Remove a production draft that has no operational history yet.
     */
    public function destroy(Produksi $produksi): RedirectResponse
    {
        $prefix = $this->prefix();

        if ($produksi->distribusis()->exists() || $produksi->produksiBahans()->exists() || $produksi->stokSudahDipotong()) {
            return redirect()->route($prefix.'.produksi.index')
                ->with('error', 'Produksi tidak dapat dihapus karena sudah memiliki rencana distribusi, bahan, atau histori stok.');
        }

        $produksi->delete();

        return redirect()->route($prefix.'.produksi.index')->with('status', 'Produksi berhasil dihapus.');
    }

    /**
     * Add a distribution plan (portions demand) to the production.
     */
    public function storeDistribution(Produksi $produksi, StoreDistribusiPlanRequest $request): RedirectResponse
    {
        Distribusi::create([
            'kode_distribusi' => Distribusi::kodeBaru(),
            'produksi_id' => $produksi->id,
            'sekolah_id' => $request->integer('sekolah_id'),
            'tanggal' => $produksi->tanggal,
            'jumlah_porsi' => $request->integer('jumlah_porsi'),
            'status' => Distribusi::STATUS_DIJADWALKAN,
            'created_by' => $request->user()->id,
        ]);

        $produksi->perbaruiTargetPorsi();

        return back()->with('status', 'Rencana distribusi ditambahkan.');
    }

    /**
     * Update a distribution plan.
     */
    public function updateDistribution(Produksi $produksi, Distribusi $distribusi, UpdateDistribusiPlanRequest $request): RedirectResponse
    {
        abort_unless($distribusi->produksi_id === $produksi->id, 404);

        $distribusi->update([
            'sekolah_id' => $request->integer('sekolah_id'),
            'jumlah_porsi' => $request->integer('jumlah_porsi'),
        ]);

        $produksi->perbaruiTargetPorsi();

        return back()->with('status', 'Rencana distribusi diperbarui.');
    }

    /**
     * Remove a distribution plan.
     */
    public function destroyDistribution(Produksi $produksi, Distribusi $distribusi): RedirectResponse
    {
        abort_unless($distribusi->produksi_id === $produksi->id, 404);

        $distribusi->delete();
        $produksi->perbaruiTargetPorsi();

        return back()->with('status', 'Rencana distribusi dihapus.');
    }

    /**
     * Add an ingredient used in this production.
     */
    public function storeIngredient(Produksi $produksi, StoreProduksiBahanRequest $request): RedirectResponse
    {
        $bahan = BahanBaku::findOrFail($request->integer('bahan_baku_id'));

        ProduksiBahan::create([
            'produksi_id' => $produksi->id,
            'bahan_baku_id' => $bahan->id,
            'jumlah' => $request->input('jumlah'),
            'satuan' => $bahan->satuan,
        ]);

        return back()->with('status', 'Bahan produksi ditambahkan.');
    }

    /**
     * Remove an ingredient from the production.
     */
    public function destroyIngredient(Produksi $produksi, ProduksiBahan $produksiBahan): RedirectResponse
    {
        abort_unless($produksiBahan->produksi_id === $produksi->id, 404);

        $produksiBahan->delete();

        return back()->with('status', 'Bahan produksi dihapus.');
    }

    /**
     * Start the production: validate and deduct stock for all ingredients once.
     */
    public function start(Produksi $produksi): RedirectResponse
    {
        if ($produksi->status !== Produksi::STATUS_BELUM_DIMULAI) {
            return back()->with('error', 'Produksi sudah dimulai.');
        }

        if ($produksi->stokSudahDipotong()) {
            return back()->with('error', 'Stok produksi ini sudah dipotong.');
        }

        $bahan = $produksi->produksiBahans()->with('bahanBaku')->get();

        if ($bahan->isEmpty()) {
            return back()->with('error', 'Tambahkan bahan produksi sebelum memulai produksi.');
        }

        $stokTersedia = BahanBaku::stokTersediaPerBahan();

        foreach ($bahan as $produksiBahan) {
            $stok = $stokTersedia->get($produksiBahan->bahan_baku_id) ?? 0.0;

            if ($stok < (float) $produksiBahan->jumlah) {
                return back()->with('error', 'Stok '.$produksiBahan->bahanBaku->nama.' tidak mencukupi untuk memulai produksi.');
            }
        }

        DB::transaction(function () use ($produksi, $bahan): void {
            foreach ($bahan as $produksiBahan) {
                StokMutation::create([
                    'bahan_baku_id' => $produksiBahan->bahan_baku_id,
                    'tipe' => StokMutation::TIPE_KELUAR,
                    'jumlah' => $produksiBahan->jumlah,
                    'tanggal' => $produksi->tanggal,
                    'keterangan' => 'Pengurangan stok produksi #'.$produksi->id,
                    'user_id' => auth()->id(),
                ]);
            }

            $produksi->forceFill([
                'status' => Produksi::STATUS_PERSIAPAN,
                'started_at' => now(),
                'stock_deducted_at' => now(),
            ])->save();
        });

        return back()->with('status', 'Produksi dimulai dan stok bahan telah dipotong.');
    }

    /**
     * Advance the production status one step forward.
     */
    public function updateStatus(Produksi $produksi, UpdateProduksiStatusRequest $request): RedirectResponse
    {
        if ($produksi->status === Produksi::STATUS_BELUM_DIMULAI || $produksi->status === Produksi::STATUS_SELESAI) {
            return back()->with('error', 'Transisi status produksi tidak valid.');
        }

        if ($request->input('status') !== $produksi->nextStatus()) {
            return back()->with('error', 'Status produksi harus dijalankan secara berurutan.');
        }

        $produksi->update([
            'status' => $request->input('status'),
            'completed_at' => $request->input('status') === Produksi::STATUS_SELESAI ? now() : null,
        ]);

        return back()->with('status', 'Status produksi diperbarui.');
    }

    /**
     * Record the actual production result.
     */
    public function updateResult(Produksi $produksi, UpdateHasilRequest $request): RedirectResponse
    {
        $produksi->update(['hasil_porsi' => $request->integer('hasil_porsi')]);

        return back()->with('status', 'Hasil produksi dicatat.');
    }

    /**
     * Role prefix for admin vs petugas route names.
     */
    private function prefix(): string
    {
        return auth()->user()->role === 'admin' ? 'admin' : 'petugas';
    }

    /**
     * Shared payload for the production detail view.
     *
     * @return array<string, mixed>
     */
    private function showData(Produksi $produksi): array
    {
        return [
            'produksi' => $produksi,
            'stokTersedia' => BahanBaku::stokTersediaPerBahan(),
            'sekolahs' => Sekolah::orderBy('nama')->get(),
            'bahanBakus' => BahanBaku::where('status', BahanBaku::STATUS_ACTIVE)->orderBy('nama')->get(),
            'routePrefix' => $this->prefix(),
            'canOperate' => true,
        ];
    }
}
