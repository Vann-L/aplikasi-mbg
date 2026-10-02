<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\JadwalPegawai;
use App\Models\KalenderOperasional;
use App\Models\Pegawai;
use App\Models\PolaJadwal;
use App\Services\JadwalEfektifService;
use App\Services\JadwalPegawaiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function __construct(
        private readonly JadwalPegawaiService $jadwalService,
        private readonly JadwalEfektifService $jadwalEfektif,
    ) {}

    /**
     * Read-only listing of all per-date overrides.
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $pegawaiId = $request->integer('pegawai_id');
        $status = $request->string('status')->toString();

        $jadwal = JadwalPegawai::query()
            ->join('pegawai', 'jadwal_pegawai.pegawai_id', '=', 'pegawai.id')
            ->with('pegawai')
            ->select('jadwal_pegawai.*')
            ->when($tanggal !== '', fn ($query) => $query->whereDate('jadwal_pegawai.tanggal', $tanggal))
            ->when($pegawaiId > 0, fn ($query) => $query->where('jadwal_pegawai.pegawai_id', $pegawaiId))
            ->when(in_array($status, JadwalPegawai::STATUSES, true), fn ($query) => $query->where('jadwal_pegawai.status', $status))
            ->orderByDesc('jadwal_pegawai.tanggal')
            ->orderBy('pegawai.nama')
            ->orderByDesc('jadwal_pegawai.id')
            ->paginate(15)
            ->withQueryString();

        return view('jadwal.index', [
            'jadwal' => $jadwal,
            'pegawaiList' => Pegawai::orderBy('nama')->get(),
            'tanggal' => $tanggal,
            'pegawaiId' => $pegawaiId,
            'status' => $status,
            'routePrefix' => 'kepala',
            'canManage' => false,
            'active' => 'override',
        ]);
    }

    /**
     * Display the specified per-date override.
     */
    public function show(JadwalPegawai $jadwal): View
    {
        $jadwal->load('pegawai');

        return view('jadwal.show', [
            'jadwal' => $jadwal,
            'routePrefix' => 'kepala',
            'canManage' => false,
            'hasAbsensi' => $this->jadwalService->hasAbsensi($jadwal),
            'active' => 'override',
        ]);
    }

    /**
     * Read-only weekly pattern matrix and history.
     */
    public function pola(): View
    {
        $pegawaiList = Pegawai::query()
            ->where('status', Pegawai::STATUS_ACTIVE)
            ->orderBy('nama')
            ->get();
        $hariIni = CarbonImmutable::today(config('app.timezone'));

        $matriks = $pegawaiList->map(function (Pegawai $pegawai) use ($hariIni): array {
            $polaAktif = $this->jadwalEfektif->polaAktif($pegawai, $hariIni);

            return [
                'pegawai' => $pegawai,
                'pola' => $polaAktif,
                'hari' => $polaAktif?->hari ?? array_fill(0, 7, ['status' => null]),
            ];
        });

        $polas = PolaJadwal::query()
            ->with('pegawai')
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return view('jadwal.pola', [
            'matriks' => $matriks,
            'polas' => $polas,
            'routePrefix' => 'kepala',
            'canManage' => false,
            'active' => 'pola',
            'namaHari' => PolaJadwal::NAMA_HARI,
        ]);
    }

    /**
     * Read-only operational calendar.
     */
    public function kalender(): View
    {
        $kalender = KalenderOperasional::query()
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();

        return view('jadwal.kalender', [
            'kalender' => $kalender,
            'routePrefix' => 'kepala',
            'canManage' => false,
            'active' => 'kalender',
        ]);
    }

    /**
     * Read-only weekly monitoring matrix.
     */
    public function monitoring(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $acuan = $tanggal !== '' ? CarbonImmutable::parse($tanggal) : CarbonImmutable::today(config('app.timezone'));
        $mulai = $acuan->startOfWeek(CarbonImmutable::MONDAY);
        $selesai = $mulai->addDays(6);

        return view('jadwal.monitoring', [
            'rows' => $this->jadwalEfektif->monitoringMingguan($mulai),
            'mulai' => $mulai,
            'selesai' => $selesai,
            'tanggal' => $acuan->toDateString(),
            'routePrefix' => 'kepala',
            'canManage' => false,
            'active' => 'monitoring',
            'namaHari' => PolaJadwal::NAMA_HARI,
        ]);
    }
}
