<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Models\Produksi;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Show the production report (read-only).
     */
    public function produksi(Request $request): View
    {
        $tanggalAwal = $request->string('tanggal_awal')->toString();
        $tanggalAkhir = $request->string('tanggal_akhir')->toString();
        $status = $request->string('status')->toString();

        $produksi = Produksi::query()
            ->with('menu')
            ->select('produksi.*')
            ->selectRaw('(COALESCE(hasil_porsi, 0) - COALESCE(target_porsi, 0)) AS selisih')
            ->when($tanggalAwal !== '', fn ($query) => $query->whereDate('tanggal', '>=', $tanggalAwal))
            ->when($tanggalAkhir !== '', fn ($query) => $query->whereDate('tanggal', '<=', $tanggalAkhir))
            ->when(in_array($status, Produksi::STATUS_FLOW, true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('laporan.produksi', [
            'produksi' => $produksi,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'status' => $status,
            'routePrefix' => $this->prefix(),
            'active' => 'produksi',
        ]);
    }

    /**
     * Show the current stock report, reusing the Phase 5 stock calculation.
     */
    public function stok(Request $request): View
    {
        $stokTersedia = BahanBaku::stokTersediaPerBahan();
        $bahanBakuId = $request->integer('bahan_baku');
        $status = $request->string('status')->toString();

        $rows = BahanBaku::query()
            ->when($bahanBakuId > 0, fn ($query) => $query->where('id', $bahanBakuId))
            ->orderBy('nama')
            ->get()
            ->map(function (BahanBaku $item) use ($stokTersedia): array {
                $tersedia = (float) ($stokTersedia->get($item->id) ?? 0);

                return [
                    'bahan' => $item,
                    'tersedia' => $tersedia,
                    'status' => $item->statusStok($tersedia),
                ];
            })
            ->filter(fn (array $row): bool => $status === '' || $row['status'] === $status)
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $rowsPaged = new LengthAwarePaginator(
            $rows->forPage($page, 15)->values(),
            $rows->count(),
            15,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        return view('laporan.stok', [
            'rows' => $rowsPaged,
            'bahanBakuList' => BahanBaku::orderBy('nama')->get(),
            'bahanBakuId' => $bahanBakuId,
            'status' => $status,
            'routePrefix' => $this->prefix(),
            'active' => 'stok',
        ]);
    }

    /**
     * Show the distribution report (read-only).
     */
    public function distribusi(Request $request): View
    {
        $tanggalAwal = $request->string('tanggal_awal')->toString();
        $tanggalAkhir = $request->string('tanggal_akhir')->toString();
        $sekolahId = $request->integer('sekolah');
        $status = $request->string('status')->toString();

        $distribusi = Distribusi::query()
            ->with(['produksi.menu', 'sekolah', 'petugas'])
            ->when($tanggalAwal !== '', fn ($query) => $query->whereDate('tanggal', '>=', $tanggalAwal))
            ->when($tanggalAkhir !== '', fn ($query) => $query->whereDate('tanggal', '<=', $tanggalAkhir))
            ->when($sekolahId > 0, fn ($query) => $query->where('sekolah_id', $sekolahId))
            ->when(in_array($status, $this->statusDistribusi(), true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('laporan.distribusi', [
            'distribusi' => $distribusi,
            'sekolahList' => Sekolah::orderBy('nama')->get(),
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'sekolahId' => $sekolahId,
            'status' => $status,
            'availableStatuses' => $this->statusDistribusi(),
            'routePrefix' => $this->prefix(),
            'active' => 'distribusi',
        ]);
    }

    /**
     * Show the reception report. Only distributions with an actual reception
     * record are listed; no fabricated reception rows are created.
     */
    public function penerimaan(Request $request): View
    {
        $tanggalAwal = $request->string('tanggal_awal')->toString();
        $tanggalAkhir = $request->string('tanggal_akhir')->toString();
        $sekolahId = $request->integer('sekolah');

        $distribusi = Distribusi::query()
            ->with(['produksi.menu', 'sekolah', 'penerimaan'])
            ->whereHas('penerimaan')
            ->when($tanggalAwal !== '', fn ($query) => $query->whereDate('tanggal', '>=', $tanggalAwal))
            ->when($tanggalAkhir !== '', fn ($query) => $query->whereDate('tanggal', '<=', $tanggalAkhir))
            ->when($sekolahId > 0, fn ($query) => $query->where('sekolah_id', $sekolahId))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('laporan.penerimaan', [
            'distribusi' => $distribusi,
            'sekolahList' => Sekolah::orderBy('nama')->get(),
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'sekolahId' => $sekolahId,
            'routePrefix' => $this->prefix(),
            'active' => 'penerimaan',
        ]);
    }

    /**
     * Show the attendance report, reusing the Phase 9 recap logic.
     */
    public function absensi(Request $request): View
    {
        $tanggalAwal = $request->string('tanggal_awal')->toString();
        $tanggalAkhir = $request->string('tanggal_akhir')->toString();
        $pegawaiId = $request->integer('pegawai');
        $status = $request->string('status')->toString();

        $rentangAwals = $tanggalAwal !== '' ? $tanggalAwal : '1970-01-01';
        $rentangAkhirs = $tanggalAkhir !== '' ? $tanggalAkhir : '9999-12-31';

        $query = JadwalPegawai::query()
            ->with('pegawai.absensi')
            ->when($tanggalAwal !== '', fn ($query) => $query->whereDate('tanggal', '>=', $tanggalAwal))
            ->when($tanggalAkhir !== '', fn ($query) => $query->whereDate('tanggal', '<=', $tanggalAkhir))
            ->when($pegawaiId > 0, fn ($query) => $query->where('pegawai_id', $pegawaiId));

        if (in_array($status, $this->statusAbsensi(), true)) {
            if ($status === JadwalPegawai::STATUS_IZIN) {
                $query->where('status', JadwalPegawai::STATUS_IZIN);
            } elseif ($status === 'libur') {
                $query->where('status', JadwalPegawai::STATUS_LIBUR);
            } else {
                $query->where('status', JadwalPegawai::STATUS_TERJADWAL);

                if ($status === Absensi::STATUS_ALPA) {
                    $query->whereDoesntHave(
                        'pegawai.absensi',
                        fn ($query) => $query->whereBetween('tanggal', [$rentangAwals, $rentangAkhirs]),
                    );
                } else {
                    $query->whereHas(
                        'pegawai.absensi',
                        fn ($query) => $query->whereBetween('tanggal', [$rentangAwals, $rentangAkhirs])->where('status', $status),
                    );
                }
            }
        }

        $jadwal = $query
            ->orderBy('tanggal')
            ->orderBy('jam_masuk')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('laporan.absensi', [
            'jadwal' => $jadwal,
            'pegawaiList' => Pegawai::orderBy('nama')->get(),
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'pegawaiId' => $pegawaiId,
            'status' => $status,
            'availableStatuses' => $this->statusAbsensi(),
            'routePrefix' => $this->prefix(),
            'active' => 'absensi',
        ]);
    }

    /**
     * Route prefix used for links inside the shared laporan views.
     */
    protected function prefix(): string
    {
        return Route::currentRouteName() !== null && str_starts_with(Route::currentRouteName(), 'kepala.')
            ? 'kepala'
            : 'admin';
    }

    /**
     * Statuses usable as distribution filters.
     *
     * @return list<string>
     */
    private function statusDistribusi(): array
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
     * Statuses usable as attendance filters.
     *
     * @return list<string>
     */
    private function statusAbsensi(): array
    {
        return [
            Absensi::STATUS_HADIR,
            Absensi::STATUS_TERLAMBAT,
            Absensi::STATUS_IZIN,
            Absensi::STATUS_ALPA,
            'libur',
        ];
    }
}
