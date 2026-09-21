<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePegawaiRequest;
use App\Http\Requests\UpdatePegawaiRequest;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    /**
     * Display a filtered and paginated listing of pegawai.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $pegawai = Pegawai::query()
            ->with('user')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('id_pegawai', 'like', "%{$search}%")
                ->orWhere('jabatan', 'like', "%{$search}%")))
            ->when(
                in_array($status, [Pegawai::STATUS_ACTIVE, Pegawai::STATUS_INACTIVE], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pegawai.index', [
            'pegawai' => $pegawai,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new pegawai.
     */
    public function create(): View
    {
        return view('admin.pegawai.create');
    }

    /**
     * Store a newly created pegawai.
     */
    public function store(StorePegawaiRequest $request): RedirectResponse
    {
        Pegawai::create($request->validated());

        return redirect()->route('admin.pegawai.index')->with('status', 'Pegawai berhasil ditambahkan.');
    }

    /**
     * Display the specified pegawai.
     */
    public function show(Pegawai $pegawai): View
    {
        $pegawai->load('user');

        return view('admin.pegawai.show', ['pegawai' => $pegawai]);
    }

    /**
     * Show the form for editing the specified pegawai.
     */
    public function edit(Pegawai $pegawai): View
    {
        return view('admin.pegawai.edit', ['pegawai' => $pegawai]);
    }

    /**
     * Update the specified pegawai.
     */
    public function update(UpdatePegawaiRequest $request, Pegawai $pegawai): RedirectResponse
    {
        $pegawai->update($request->validated());

        return redirect()->route('admin.pegawai.index')->with('status', 'Pegawai berhasil diperbarui.');
    }

    /**
     * Remove the specified pegawai.
     */
    public function destroy(Pegawai $pegawai): RedirectResponse
    {
        if ($pegawai->jadwalPegawai()->exists() || $pegawai->absensi()->exists()) {
            return redirect()->route('admin.pegawai.index')
                ->with('error', 'Pegawai tidak dapat dihapus karena masih memiliki jadwal atau absensi.');
        }

        $pegawai->delete();

        return redirect()->route('admin.pegawai.index')->with('status', 'Pegawai berhasil dihapus.');
    }
}
