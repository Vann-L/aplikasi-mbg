<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistribusiController extends Controller
{
    /**
     * Display a filtered and paginated listing of all distributions for monitoring.
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $status = $request->string('status')->toString();

        $statuses = [
            Distribusi::STATUS_DIJADWALKAN,
            Distribusi::STATUS_DISIAPKAN,
            Distribusi::STATUS_DIKIRIM,
            Distribusi::STATUS_DITERIMA,
            Distribusi::STATUS_SELESAI,
        ];

        $distribusi = Distribusi::query()
            ->with(['produksi.menu', 'sekolah', 'petugas'])
            ->when($tanggal !== '', fn ($query) => $query->whereDate('tanggal', $tanggal))
            ->when(in_array($status, $statuses, true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('distribusi.index', [
            'distribusi' => $distribusi,
            'tanggal' => $tanggal,
            'status' => $status,
            'availableStatuses' => $statuses,
            'routePrefix' => 'kepala',
        ]);
    }

    /**
     * Display a single distribution for read-only monitoring.
     */
    public function show(Distribusi $distribusi): View
    {
        $distribusi->load(['produksi.menu', 'sekolah', 'petugas', 'createdBy']);

        return view('distribusi.show', [
            'distribusi' => $distribusi,
            'routePrefix' => 'kepala',
            'canAct' => false,
            'kebutuhanTerpenuhi' => $distribusi->produksi->hasilMemenuhiKebutuhan(),
        ]);
    }
}
