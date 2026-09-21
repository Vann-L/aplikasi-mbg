<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\Pegawai;
use App\Models\Penerimaan;
use App\Models\Produksi;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the kepala dashboard with monitoring summaries for today.
     */
    public function __invoke(): View
    {
        $produksiHariIni = Produksi::whereDate('tanggal', today());
        $targetHariIni = (int) $produksiHariIni->sum('target_porsi');
        $hasilHariIni = (int) $produksiHariIni->sum('hasil_porsi');

        $distribusiHariIni = Distribusi::whereDate('tanggal', today());
        $penerimaanHariIni = Penerimaan::whereDate('waktu_diterima', today());

        return view('dashboard.kepala', [
            'produksiHariIniCount' => Produksi::whereDate('tanggal', today())->count(),
            'targetHariIni' => $targetHariIni,
            'hasilHariIni' => $hasilHariIni,
            'progressProduksi' => $this->progressPercentage($targetHariIni, $hasilHariIni),
            'distribusiHariIniCount' => $distribusiHariIni->count(),
            'porsiDidistribusikan' => (int) $distribusiHariIni->sum('jumlah_porsi'),
            'penerimaanHariIniCount' => $penerimaanHariIni->count(),
            'porsiDiterima' => (int) $penerimaanHariIni->sum('jumlah_diterima'),
            'totalBahanBaku' => BahanBaku::count(),
            'bahanBakuDiBawahMinimum' => $this->bahanBakuDiBawahMinimum(),
            'totalPegawai' => Pegawai::count(),
            'absensiHariIni' => Absensi::whereDate('tanggal', today())->count(),
        ]);
    }

    /**
     * Calculate the production progress percentage against the target.
     */
    private function progressPercentage(int $target, int $hasil): int
    {
        if ($target <= 0) {
            return 0;
        }

        return (int) round($hasil / $target * 100);
    }

    /**
     * List ingredients whose available stock (computed from mutations) is below minimum.
     *
     * @return Collection<int, array{nama: string, satuan: string, tersedia: float, minimum: float}>
     */
    private function bahanBakuDiBawahMinimum(): Collection
    {
        $stokTersedia = BahanBaku::stokTersediaPerBahan();

        return BahanBaku::query()
            ->get()
            ->map(fn (BahanBaku $bahanBaku): array => [
                'nama' => $bahanBaku->nama,
                'satuan' => $bahanBaku->satuan,
                'tersedia' => $stokTersedia->get($bahanBaku->id) ?? 0.0,
                'minimum' => (float) $bahanBaku->stok_minimum,
            ])
            ->filter(fn (array $item): bool => $item['tersedia'] < $item['minimum'])
            ->values();
    }
}
