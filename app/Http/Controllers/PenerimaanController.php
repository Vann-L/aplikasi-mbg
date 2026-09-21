<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenerimaanRequest;
use App\Models\Distribusi;
use App\Models\Penerimaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenerimaanController extends Controller
{
    /**
     * Display received listings for distributions (wait list for dikirim).
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $status = $request->string('status')->toString();

        $query = Distribusi::query()->with(['produksi.menu', 'sekolah', 'petugas']);

        if (auth()->user()->role === 'petugas') {
            $query->where('petugas_id', auth()->user()->pegawai?->id ?? -1);
        }

        $distribusi = $query
            ->when($tanggal !== '', fn ($query) => $query->whereDate('tanggal', $tanggal))
            ->when(in_array($status, $this->availableStatuses(), true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('penerimaan.index', [
            'distribusi' => $distribusi,
            'tanggal' => $tanggal,
            'status' => $status,
            'availableStatuses' => $this->availableStatuses(),
            'routePrefix' => $this->prefix(),
        ]);
    }

    /**
     * Display a distribution with its reception summary.
     */
    public function show(Distribusi $distribusi): View
    {
        $this->petugasOwner($distribusi);

        $distribusi->load(['produksi.menu', 'sekolah', 'petugas', 'penerimaan.createdBy', 'createdBy']);

        return view('penerimaan.show', $this->showData($distribusi));
    }

    /**
     * Show the reception form for a sent distribution.
     */
    public function create(Distribusi $distribusi): View|RedirectResponse
    {
        $this->petugasOwner($distribusi);

        if ($distribusi->status !== Distribusi::STATUS_DIKIRIM) {
            return redirect()->route(auth()->user()->role === 'admin' ? 'admin.penerimaan.show' : 'petugas.penerimaan.show', $distribusi)
                ->with('error', 'Hanya distribusi berstatus dikirim yang dapat dicatat penerimaannya.');
        }

        if ($distribusi->penerimaan()->exists()) {
            return redirect()->route(auth()->user()->role === 'admin' ? 'admin.penerimaan.show' : 'petugas.penerimaan.show', $distribusi)
                ->with('error', 'Penerimaan untuk distribusi ini sudah tercatat.');
        }

        $distribusi->load(['produksi.menu', 'sekolah', 'petugas']);

        return view('penerimaan.create', [
            'distribusi' => $distribusi,
            'routePrefix' => $this->prefix(),
        ]);
    }

    /**
     * Record the reception for a sent distribution and mark it diterima.
     */
    public function store(Distribusi $distribusi, StorePenerimaanRequest $request): RedirectResponse
    {
        $this->petugasOwner($distribusi);

        $fotoBukti = null;

        if ($request->hasFile('foto_bukti')) {
            $fotoBukti = $request->file('foto_bukti')->store('penerimaan', 'public');
        }

        DB::transaction(function () use ($distribusi, $request, $fotoBukti): void {
            Penerimaan::create([
                'distribusi_id' => $distribusi->id,
                'jumlah_diterima' => $request->integer('jumlah_diterima'),
                'waktu_diterima' => $request->date('waktu_diterima'),
                'penerima_nama' => trim($request->string('penerima_nama')->toString()),
                'foto_bukti' => $fotoBukti,
                'catatan' => $request->filled('catatan') ? trim($request->string('catatan')->toString()) : null,
                'created_by' => auth()->id(),
            ]);

            $distribusi->update(['status' => Distribusi::STATUS_DITERIMA]);
        });

        return redirect()->route($this->prefix().'.penerimaan.show', $distribusi)
            ->with('status', 'Penerimaan tercatat. Distribusi berstatus diterima.');
    }

    /**
     * Finalize a received distribution to selesai.
     */
    public function selesai(Distribusi $distribusi): RedirectResponse
    {
        $this->petugasOwner($distribusi);

        if ($distribusi->status !== Distribusi::STATUS_DITERIMA) {
            return back()->with('error', 'Hanya distribusi berstatus diterima yang dapat diselesaikan.');
        }

        if ($distribusi->penerimaan()->doesntExist()) {
            return back()->with('error', 'Catat penerimaan terlebih dahulu sebelum menyelesaikan distribusi.');
        }

        $distribusi->update(['status' => Distribusi::STATUS_SELESAI]);

        return back()->with('status', 'Distribusi selesai.');
    }

    /**
     * Role prefix for admin vs petugas route names.
     */
    private function prefix(): string
    {
        return auth()->user()->role === 'admin' ? 'admin' : 'petugas';
    }

    /**
     * Statuses used to filter the index listing.
     *
     * @return list<string>
     */
    private function availableStatuses(): array
    {
        return [
            Distribusi::STATUS_DIKIRIM,
            Distribusi::STATUS_DITERIMA,
            Distribusi::STATUS_SELESAI,
        ];
    }

    /**
     * Restrict petugas to distributions actually assigned to them.
     */
    private function petugasOwner(Distribusi $distribusi): void
    {
        if (auth()->user()->role !== 'petugas') {
            return;
        }

        abort_unless($distribusi->petugas_id === auth()->user()->pegawai?->id, 403);
    }

    /**
     * Shared payload for the reception detail view.
     *
     * @return array<string, mixed>
     */
    private function showData(Distribusi $distribusi): array
    {
        return [
            'distribusi' => $distribusi,
            'routePrefix' => $this->prefix(),
            'canAct' => true,
        ];
    }
}
