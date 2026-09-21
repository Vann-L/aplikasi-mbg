<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSekolahRequest;
use App\Http\Requests\UpdateSekolahRequest;
use App\Models\Sekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SekolahController extends Controller
{
    /**
     * Display a filtered and paginated listing of sekolah.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $sekolah = Sekolah::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('npsn', 'like', "%{$search}%")))
            ->when(
                in_array($status, [Sekolah::STATUS_ACTIVE, Sekolah::STATUS_INACTIVE], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.sekolah.index', [
            'sekolah' => $sekolah,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new sekolah.
     */
    public function create(): View
    {
        return view('admin.sekolah.create');
    }

    /**
     * Store a newly created sekolah.
     */
    public function store(StoreSekolahRequest $request): RedirectResponse
    {
        Sekolah::create($request->validated());

        return redirect()->route('admin.sekolah.index')->with('status', 'Sekolah berhasil ditambahkan.');
    }

    /**
     * Display the specified sekolah.
     */
    public function show(Sekolah $sekolah): View
    {
        return view('admin.sekolah.show', ['sekolah' => $sekolah]);
    }

    /**
     * Show the form for editing the specified sekolah.
     */
    public function edit(Sekolah $sekolah): View
    {
        return view('admin.sekolah.edit', ['sekolah' => $sekolah]);
    }

    /**
     * Update the specified sekolah.
     */
    public function update(UpdateSekolahRequest $request, Sekolah $sekolah): RedirectResponse
    {
        $sekolah->update($request->validated());

        return redirect()->route('admin.sekolah.index')->with('status', 'Sekolah berhasil diperbarui.');
    }

    /**
     * Remove the specified sekolah.
     */
    public function destroy(Sekolah $sekolah): RedirectResponse
    {
        if ($sekolah->distribusis()->exists()) {
            return redirect()->route('admin.sekolah.index')
                ->with('error', 'Sekolah tidak dapat dihapus karena sudah digunakan pada data distribusi.');
        }

        $sekolah->delete();

        return redirect()->route('admin.sekolah.index')->with('status', 'Sekolah berhasil dihapus.');
    }
}
