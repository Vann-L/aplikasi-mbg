<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\KalenderOperasional;
use App\Models\Pegawai;
use App\Services\JadwalEfektifService;
use App\Values\JadwalEfektif;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function __construct(private readonly JadwalEfektifService $jadwalEfektif) {}

    /**
     * Display the authenticated pegawai's own effective schedule for the next
     * few weeks. The effective schedule combines SPPG operational calendar,
     * per-date overrides and the weekly pattern.
     */
    public function index(): View
    {
        $pegawai = auth()->user()?->pegawai;

        $jadwal = $pegawai === null
            ? collect()
            : $this->jadwalEfektifSaya($pegawai);

        return view('jadwal.saya', [
            'jadwal' => $jadwal,
            'pegawai' => $pegawai,
        ]);
    }

    /**
     * Effective schedule rows starting yesterday up to four weeks ahead.
     *
     * @return Collection<int, array{tanggal: CarbonImmutable, efektif: JadwalEfektif|null, kalender: KalenderOperasional|null, catatan: string|null}>
     */
    private function jadwalEfektifSaya(Pegawai $pegawai): Collection
    {
        $mulai = CarbonImmutable::today(config('app.timezone'))->subDay();

        return collect(range(0, 27))
            ->map(fn (int $offset): CarbonImmutable => $mulai->addDays($offset))
            ->map(function (CarbonImmutable $tanggal) use ($pegawai): array {
                $override = $pegawai->jadwalPada($tanggal);

                return [
                    'tanggal' => $tanggal,
                    'efektif' => $this->jadwalEfektif->efektif($pegawai, $tanggal),
                    'kalender' => $this->jadwalEfektif->kalenderPada($tanggal),
                    'catatan' => $override?->catatan,
                ];
            });
    }
}
