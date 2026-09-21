<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Services\AbsensiQrService;
use App\Services\AbsensiScanException;
use App\Services\AbsensiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    public function __construct(
        private readonly AbsensiService $absensi,
        private readonly AbsensiQrService $qr,
    ) {}

    /**
     * Display today's schedule, today's attendance, and recent history.
     */
    public function index(): View
    {
        $pegawai = $this->pegawai();
        $now = CarbonImmutable::now();

        return view('absensi.saya', [
            'pegawai' => $pegawai,
            'jadwalHariIni' => $pegawai?->jadwalPada($now),
            'absensiHariIni' => $pegawai?->absensiPada($now),
            'riwayat' => $pegawai?->absensi()->latest('tanggal')->latest('id')->limit(30)->get() ?? collect(),
            'expirySeconds' => $this->qr->expirySeconds(),
        ]);
    }

    /**
     * Record attendance from the manual token entry form.
     */
    public function scan(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        return $this->record($request->string('token')->toString());
    }

    /**
     * Record attendance by opening the QR payload URL from a phone camera.
     */
    public function scanToken(string $token): RedirectResponse
    {
        return $this->record($token);
    }

    /**
     * Run a scan for the logged-in pegawai and flash the outcome.
     */
    private function record(string $token): RedirectResponse
    {
        $pegawai = $this->pegawai();

        if ($pegawai === null) {
            return redirect()->route('petugas.absensi.index')->with('error', 'Akun Anda belum terhubung ke data pegawai.');
        }

        try {
            $this->absensi->scan($pegawai, $token);
        } catch (AbsensiScanException $e) {
            return redirect()->route('petugas.absensi.index')->with('error', $e->getMessage());
        }

        return redirect()->route('petugas.absensi.index')
            ->with('status', 'Absensi berhasil tercatat.');
    }

    /**
     * The pegawai linked to the authenticated user, if any.
     */
    private function pegawai(): ?Pegawai
    {
        return auth()->user()?->pegawai;
    }
}
