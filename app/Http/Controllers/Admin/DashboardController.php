<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Sekolah;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard with master data and today's distribution totals.
     */
    public function __invoke(): View
    {
        return view('dashboard.admin', [
            'totalPegawai' => Pegawai::count(),
            'totalSekolah' => Sekolah::count(),
            'totalMenu' => Menu::count(),
            'totalBahanBaku' => BahanBaku::count(),
            'distribusiHariIni' => Distribusi::whereDate('tanggal', today())->count(),
        ]);
    }
}
