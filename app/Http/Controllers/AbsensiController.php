<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Services\AbsensiQrService;
use App\Support\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    public function __construct(protected readonly AbsensiQrService $qr) {}

    /**
     * Display the attendance recap filtered by date and status.
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString() ?: now()->toDateString();
        $status = $request->string('status')->toString();

        $query = JadwalPegawai::query()->with('pegawai.absensi');

        $query->whereDate('tanggal', $tanggal);

        if (in_array($status, $this->availableStatuses(), true)) {
            if ($status === JadwalPegawai::STATUS_IZIN) {
                $query->where('status', JadwalPegawai::STATUS_IZIN);
            } elseif ($status === 'libur') {
                $query->where('status', JadwalPegawai::STATUS_LIBUR);
            } else {
                $query->where('status', JadwalPegawai::STATUS_TERJADWAL);

                if ($status === Absensi::STATUS_ALPA) {
                    $query->whereDoesntHave('pegawai.absensi', fn ($query) => $query->whereDate('tanggal', $tanggal));
                } else {
                    $query->whereHas('pegawai.absensi', fn ($query) => $query->whereDate('tanggal', $tanggal)->where('status', $status));
                }
            }
        }

        $jadwal = $query
            ->orderBy('jam_masuk')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('absensi.index', [
            'jadwal' => $jadwal,
            'tanggal' => $tanggal,
            'status' => $status,
            'availableStatuses' => $this->availableStatuses(),
            'routePrefix' => $this->prefix(),
        ]);
    }

    /**
     * Display the live QR attendance display.
     */
    public function qr(): View
    {
        $token = $this->qr->generate();

        return view('absensi.qr', [
            'token' => $token,
            'scanUrl' => $this->scanUrlFor($token),
            'expirySeconds' => $this->qr->secondsUntilExpiry(),
            'routePrefix' => $this->prefix(),
        ]);
    }

    /**
     * Return a fresh QR token as JSON for the polling interval.
     *
     * @return array{token: string, expires_in: int, scan_url: string}
     */
    public function token(): JsonResponse
    {
        $token = $this->qr->generate();

        return response()->json([
            'token' => $token,
            'expires_in' => $this->qr->secondsUntilExpiry(),
            'scan_url' => $this->scanUrlFor($token),
        ]);
    }

    /**
     * Render the QR image (SVG) for a given token.
     */
    public function qrImg(Request $request): Response
    {
        $token = $request->string('token')->toString();

        $svg = QrCode::encodeText($this->scanUrlFor($token))->toSvg();

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Route prefix used for links inside shared views.
     */
    protected function prefix(): string
    {
        return Route::currentRouteName() !== null && str_starts_with(Route::currentRouteName(), 'kepala.')
            ? 'kepala'
            : 'admin';
    }

    /**
     * Statuses usable as recap filters.
     *
     * @return list<string>
     */
    protected function availableStatuses(): array
    {
        return [
            Absensi::STATUS_HADIR,
            Absensi::STATUS_TERLAMBAT,
            Absensi::STATUS_IZIN,
            Absensi::STATUS_ALPA,
            'libur',
        ];
    }

    /**
     * Build the absolute scan URL a petugas receives when scanning the QR.
     */
    private function scanUrlFor(string $token): string
    {
        return route('petugas.absensi.scan-token', ['token' => $token]);
    }
}
