<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDistribusiRequest;
use App\Models\Distribusi;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistribusiController extends Controller
{
    /**
     * Display a filtered and paginated listing of distributions.
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

        return view('distribusi.index', [
            'distribusi' => $distribusi,
            'tanggal' => $tanggal,
            'status' => $status,
            'availableStatuses' => $this->availableStatuses(),
            'routePrefix' => $this->prefix(),
        ]);
    }

    /**
     * Display a single distribution with its scheduling and status actions.
     */
    public function show(Distribusi $distribusi): View
    {
        $this->petugasOwner($distribusi);

        $distribusi->load(['produksi.menu', 'sekolah', 'petugas', 'createdBy']);

        return view('distribusi.show', $this->showData($distribusi));
    }

    /**
     * Show the form to schedule a distribution (petugas, vehicle, departure).
     */
    public function edit(Distribusi $distribusi): View|RedirectResponse
    {
        if (! in_array($distribusi->status, Distribusi::STATUS_PERENCANAAN, true)) {
            return redirect()->route('admin.distribusi.show', $distribusi)
                ->with('error', 'Distribusi yang sudah dikirim tidak dapat dijadwalkan ulang.');
        }

        return view('distribusi.edit', [
            'distribusi' => $distribusi,
            'petugasList' => Pegawai::kandidatPetugas(),
        ]);
    }

    /**
     * Update the scheduling of a distribution.
     */
    public function update(Distribusi $distribusi, UpdateDistribusiRequest $request): RedirectResponse
    {
        if (! in_array($distribusi->status, Distribusi::STATUS_PERENCANAAN, true)) {
            return redirect()->route('admin.distribusi.show', $distribusi)
                ->with('error', 'Distribusi yang sudah dikirim tidak dapat dijadwalkan ulang.');
        }

        $jamBerangkat = null;

        if ($request->filled('jam_berangkat')) {
            $jamBerangkat = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $request->date('tanggal')->format('Y-m-d').' '.$request->input('jam_berangkat'),
            );
        }

        $distribusi->update([
            'petugas_id' => $request->filled('petugas_id') ? $request->integer('petugas_id') : null,
            'kendaraan' => $request->filled('kendaraan') ? trim($request->string('kendaraan')->toString()) : null,
            'tanggal' => $request->date('tanggal'),
            'jam_berangkat' => $jamBerangkat,
            'catatan' => $request->filled('catatan') ? trim($request->string('catatan')->toString()) : null,
        ]);

        return redirect()->route('admin.distribusi.show', $distribusi)
            ->with('status', 'Penjadwalan distribusi diperbarui.');
    }

    /**
     * Remove a distribution that is still in the planning stage.
     */
    public function destroy(Distribusi $distribusi): RedirectResponse
    {
        if (! in_array($distribusi->status, Distribusi::STATUS_PERENCANAAN, true)) {
            return back()->with('error', 'Distribusi yang sudah dikirim tidak dapat dihapus.');
        }

        $distribusi->delete();
        $distribusi->produksi?->perbaruiTargetPorsi();

        return redirect()->route($this->prefix().'.distribusi.index')
            ->with('status', 'Distribusi berhasil dihapus.');
    }

    /**
     * Move a distribution from dijadwalkan to disiapkan.
     */
    public function prepare(Distribusi $distribusi): RedirectResponse
    {
        $this->petugasOwner($distribusi);

        if ($distribusi->status !== Distribusi::STATUS_DIJADWALKAN) {
            return back()->with('error', 'Hanya distribusi berstatus dijadwalkan yang dapat disiapkan.');
        }

        $distribusi->update(['status' => Distribusi::STATUS_DISIAPKAN]);

        return back()->with('status', 'Distribusi disiapkan. Pastikan produksi telah memenuhi kebutuhan sebelum dikirim.');
    }

    /**
     * Send a prepared distribution once the production fulfills the demand.
     */
    public function send(Distribusi $distribusi): RedirectResponse
    {
        $this->petugasOwner($distribusi);

        if ($distribusi->status !== Distribusi::STATUS_DISIAPKAN) {
            return back()->with('error', 'Distribusi harus disiapkan terlebih dahulu sebelum dikirim.');
        }

        if ($distribusi->petugas_id === null) {
            return back()->with('error', 'Tentukan petugas distribusi sebelum mengirim.');
        }

        if (! $distribusi->produksi->hasilMemenuhiKebutuhan()) {
            return back()->with('error', 'Produksi belum memenuhi kebutuhan porsi, distribusi tidak dapat dikirim.');
        }

        $distribusi->update([
            'status' => Distribusi::STATUS_DIKIRIM,
            'jam_berangkat' => $distribusi->jam_berangkat ?? now(),
        ]);

        return back()->with('status', 'Distribusi berhasil dikirim.');
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
            Distribusi::STATUS_DIJADWALKAN,
            Distribusi::STATUS_DISIAPKAN,
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
     * Shared payload for the distribution detail view.
     *
     * @return array<string, mixed>
     */
    private function showData(Distribusi $distribusi): array
    {
        return [
            'distribusi' => $distribusi,
            'routePrefix' => $this->prefix(),
            'canAct' => true,
            'kebutuhanTerpenuhi' => $distribusi->produksi->hasilMemenuhiKebutuhan(),
        ];
    }
}
