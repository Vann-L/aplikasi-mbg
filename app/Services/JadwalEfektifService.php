<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\KalenderOperasional;
use App\Models\Pegawai;
use App\Models\PolaJadwal;
use App\Values\JadwalEfektif;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Single source of truth for resolving a pegawai's effective schedule on any
 * date. Priority: operational calendar (SPPG-wide libur) > per-date override
 * (jadwal_pegawai) > active weekly pattern (pola_jadwal) > no schedule.
 */
class JadwalEfektifService
{
    /**
     * Get the SPPG-wide operational calendar entry for a date, if any.
     */
    public function kalenderPada(CarbonImmutable $tanggal): ?KalenderOperasional
    {
        return KalenderOperasional::query()->whereDate('tanggal', $tanggal->toDateString())->first();
    }

    /**
     * Whether the SPPG is closed on the given date via the operational calendar.
     */
    public function isHariLiburOperasional(CarbonImmutable $tanggal): bool
    {
        return $this->kalenderPada($tanggal)?->status === KalenderOperasional::STATUS_LIBUR;
    }

    /**
     * Get the weekly pattern active for a pegawai on a date: the latest pattern
     * whose `berlaku_mulai` is not after the date. Returns null when none.
     */
    public function polaAktif(Pegawai $pegawai, CarbonImmutable $tanggal): ?PolaJadwal
    {
        return $pegawai->polaJadwal()
            ->whereDate('berlaku_mulai', '<=', $tanggal->toDateString())
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolve the effective schedule of a pegawai for a date, or null when the
     * pegawai has no schedule at all (no override and no active pattern).
     */
    public function efektif(Pegawai $pegawai, CarbonImmutable $tanggal): ?JadwalEfektif
    {
        $kalender = $this->kalenderPada($tanggal);

        if ($kalender?->status === KalenderOperasional::STATUS_LIBUR) {
            return new JadwalEfektif(
                status: JadwalPegawai::STATUS_LIBUR,
                jamMasuk: null,
                jamPulang: null,
                sumber: 'kalender',
            );
        }

        $override = $pegawai->jadwalPada($tanggal);

        if ($override !== null) {
            return new JadwalEfektif(
                status: $override->status,
                jamMasuk: $this->waktu($override->jam_masuk?->format('H:i'), $tanggal),
                jamPulang: $this->waktu($override->jam_pulang?->format('H:i'), $tanggal),
                sumber: 'override',
            );
        }

        $pola = $this->polaAktif($pegawai, $tanggal);

        if ($pola === null) {
            return null;
        }

        $hari = $pola->hariPada($tanggal);

        if ($hari['status'] === PolaJadwal::STATUS_LIBUR) {
            return new JadwalEfektif(
                status: JadwalPegawai::STATUS_LIBUR,
                jamMasuk: null,
                jamPulang: null,
                sumber: 'pola',
            );
        }

        return new JadwalEfektif(
            status: JadwalPegawai::STATUS_TERJADWAL,
            jamMasuk: $this->waktu($hari['jam_masuk'], $tanggal),
            jamPulang: $this->waktu($hari['jam_pulang'], $tanggal),
            sumber: 'pola',
        );
    }

    /**
     * Recapitulation status shown in a recap row. A real scan (hadir/terlambat)
     * wins over everything; otherwise the effective schedule decides between
     * alpa (terjadwal without attendance), izin, libur or no schedule at all.
     */
    public function statusRekap(?JadwalEfektif $efektif, ?Absensi $absensi): string
    {
        if ($absensi !== null) {
            return $absensi->status;
        }

        if ($efektif === null) {
            return 'tidak_terjadwal';
        }

        return match ($efektif->status) {
            JadwalPegawai::STATUS_IZIN => Absensi::STATUS_IZIN,
            JadwalPegawai::STATUS_LIBUR => JadwalPegawai::STATUS_LIBUR,
            default => Absensi::STATUS_ALPA,
        };
    }

    /**
     * Recap rows for all active pegawai on a date, so alpa and libur can be
     * shown in one table.
     *
     * @return Collection<int, array{pegawai: Pegawai, efektif: ?JadwalEfektif, absensi: ?Absensi, status: string}>
     */
    public function rekapHarian(CarbonImmutable $tanggal): Collection
    {
        return $this->pegawaiAktif()->map(
            fn (Pegawai $pegawai): array => $this->rekapBaris($pegawai, $tanggal),
        );
    }

    /**
     * Weekly attendance matrix (pegawai x 7 days) for the monitoring view,
     * starting Monday of the week given by $tanggalMulai.
     *
     * @return Collection<int, array{pegawai: Pegawai, hari: Collection<int, array{tanggal: CarbonImmutable, efektif: ?JadwalEfektif, absensi: ?Absensi, status: string}>}>
     */
    public function monitoringMingguan(CarbonImmutable $tanggalMulai): Collection
    {
        $mulai = $tanggalMulai->startOfWeek(CarbonImmutable::MONDAY);
        $tanggalSelesai = $mulai->addDays(6);
        $tanggalSekarang = CarbonImmutable::today(config('app.timezone'));

        $tanggalList = collect(range(0, 6))
            ->map(fn (int $offset): CarbonImmutable => $mulai->addDays($offset));

        return $this->pegawaiAktif()->map(function (Pegawai $pegawai) use ($tanggalList, $tanggalSekarang): array {
            $absensiList = $pegawai->absensi()
                ->whereBetween('tanggal', [$tanggalList->first()->toDateString(), $tanggalList->last()->toDateString()])
                ->get();

            $hari = $tanggalList->map(function (CarbonImmutable $tanggal) use ($pegawai, $absensiList, $tanggalSekarang): array {
                $efektif = $this->efektif($pegawai, $tanggal);
                $absensi = $absensiList->firstWhere(
                    fn (Absensi $absensi): bool => $absensi->tanggal?->toDateString() === $tanggal->toDateString(),
                );

                $status = match (true) {
                    $absensi !== null => $absensi->status,
                    $efektif === null => 'tidak',
                    $efektif->status === JadwalPegawai::STATUS_LIBUR => JadwalPegawai::STATUS_LIBUR,
                    $efektif->status === JadwalPegawai::STATUS_IZIN => Absensi::STATUS_IZIN,
                    $tanggal->lessThan($tanggalSekarang) => Absensi::STATUS_ALPA,
                    default => 'belum',
                };

                return [
                    'tanggal' => $tanggal,
                    'efektif' => $efektif,
                    'absensi' => $absensi,
                    'status' => $status,
                ];
            });

            return [
                'pegawai' => $pegawai,
                'hari' => $hari,
            ];
        });
    }

    /**
     * Build a single recap row for a pegawai on a date.
     *
     * @return array{pegawai: Pegawai, efektif: ?JadwalEfektif, absensi: ?Absensi, status: string}
     */
    private function rekapBaris(Pegawai $pegawai, CarbonImmutable $tanggal): array
    {
        $absensi = $pegawai->absensiPada($tanggal);
        $efektif = $this->efektif($pegawai, $tanggal);

        return [
            'pegawai' => $pegawai,
            'efektif' => $efektif,
            'absensi' => $absensi,
            'status' => $this->statusRekap($efektif, $absensi),
        ];
    }

    /**
     * Active pegawai ordered by name, the population shown in recaps.
     *
     * @return Collection<int, Pegawai>
     */
    private function pegawaiAktif(): Collection
    {
        return Pegawai::query()
            ->where('status', Pegawai::STATUS_ACTIVE)
            ->orderBy('nama')
            ->get();
    }

    /**
     * Build a CarbonImmutable time on the given date from an "H:i" string.
     */
    private function waktu(?string $jam, CarbonImmutable $tanggal): ?CarbonImmutable
    {
        return $jam === null ? null : $tanggal->setTimeFromTimeString($jam);
    }
}
