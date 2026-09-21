<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use App\Models\Pegawai;
use App\Models\Produksi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the petugas dashboard with today's schedule, production and delivery.
     */
    public function __invoke(Request $request): View
    {
        /** @var Pegawai|null $pegawai */
        $pegawai = $request->user()->pegawai;

        return view('dashboard.petugas', [
            'pegawai' => $pegawai,
            'jadwalHariIni' => $pegawai?->jadwalPegawai()->whereDate('tanggal', today())->first(),
            'absensiHariIni' => $pegawai?->absensi()->whereDate('tanggal', today())->first(),
            'produksiHariIni' => Produksi::with('menu')->whereDate('tanggal', today())->orderBy('id')->get(),
            'distribusiHariIni' => $pegawai === null
                ? collect()
                : Distribusi::with('sekolah')->whereDate('tanggal', today())->where('petugas_id', $pegawai->id)->orderBy('id')->get(),
        ]);
    }
}
