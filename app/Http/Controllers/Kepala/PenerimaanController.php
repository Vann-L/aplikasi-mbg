<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenerimaanController extends Controller
{
    /**
     * Display a filtered and paginated listing of distributions for monitoring.
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $status = $request->string('status')->toString();

        $statuses = [
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

        return view('penerimaan.index', [
            'distribusi' => $distribusi,
            'tanggal' => $tanggal,
            'status' => $status,
            'availableStatuses' => $statuses,
            'routePrefix' => 'kepala',
        ]);
    }

    /**
     * Display a single distribution with its reception summary (read-only).
     */
    public function show(Distribusi $distribusi): View
    {
        $distribusi->load(['produksi.menu', 'sekolah', 'petugas', 'penerimaan.createdBy', 'createdBy']);

        return view('penerimaan.show', [
            'distribusi' => $distribusi,
            'routePrefix' => 'kepala',
            'canAct' => false,
        ]);
    }
}
