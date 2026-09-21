<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBahanBakuRequest;
use App\Http\Requests\UpdateBahanBakuRequest;
use App\Models\BahanBaku;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BahanBakuController extends Controller
{
    /**
     * Display a filtered and paginated listing of bahan baku.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $bahanBaku = BahanBaku::query()
            ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
            ->when(
                in_array($status, [BahanBaku::STATUS_ACTIVE, BahanBaku::STATUS_INACTIVE], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.bahan-baku.index', [
            'bahanBaku' => $bahanBaku,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new bahan baku.
     */
    public function create(): View
    {
        return view('admin.bahan-baku.create');
    }

    /**
     * Store a newly created bahan baku.
     */
    public function store(StoreBahanBakuRequest $request): RedirectResponse
    {
        BahanBaku::create($request->validated());

        return redirect()->route('admin.bahan-baku.index')->with('status', 'Bahan baku berhasil ditambahkan.');
    }

    /**
     * Display the specified bahan baku.
     */
    public function show(BahanBaku $bahanBaku): View
    {
        return view('admin.bahan-baku.show', ['bahanBaku' => $bahanBaku]);
    }

    /**
     * Show the form for editing the specified bahan baku.
     */
    public function edit(BahanBaku $bahanBaku): View
    {
        return view('admin.bahan-baku.edit', ['bahanBaku' => $bahanBaku]);
    }

    /**
     * Update the specified bahan baku.
     */
    public function update(UpdateBahanBakuRequest $request, BahanBaku $bahanBaku): RedirectResponse
    {
        $bahanBaku->update($request->validated());

        return redirect()->route('admin.bahan-baku.index')->with('status', 'Bahan baku berhasil diperbarui.');
    }

    /**
     * Remove the specified bahan baku.
     */
    public function destroy(BahanBaku $bahanBaku): RedirectResponse
    {
        if ($bahanBaku->produksiBahans()->exists() || $bahanBaku->stokMutations()->exists()) {
            return redirect()->route('admin.bahan-baku.index')
                ->with('error', 'Bahan baku tidak dapat dihapus karena sudah digunakan pada data produksi atau mutasi stok.');
        }

        $bahanBaku->delete();

        return redirect()->route('admin.bahan-baku.index')->with('status', 'Bahan baku berhasil dihapus.');
    }
}
