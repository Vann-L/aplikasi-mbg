<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Values\JadwalEfektif;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Records an attendance scan (masuk or pulang) for a pegawai using a dynamic QR
 * token. Identity always comes from the logged-in user (via users → pegawai),
 * never from the request payload.
 */
class AbsensiService
{
    public function __construct(
        private readonly AbsensiQrService $qr,
        private readonly JadwalEfektifService $jadwalEfektif,
    ) {}

    /**
     * Handle a valid scan for the given pegawai at the given moment.
     *
     * @return array{status: string, message: string}
     */
    public function scan(Pegawai $pegawai, string $token, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        if (! $this->qr->verify($token, $now)) {
            throw AbsensiScanException::reason('Token QR tidak valid atau sudah kedaluwarsa.');
        }

        $efektif = $this->jadwalEfektif->efektif($pegawai, $now);

        if ($efektif === null) {
            throw AbsensiScanException::reason('Tidak ada jadwal kerja untuk hari ini, absensi tidak dapat dilakukan.');
        }

        if ($efektif->status === JadwalPegawai::STATUS_IZIN) {
            throw AbsensiScanException::reason('Jadwal hari ini izin, tidak dapat melakukan absensi normal.');
        }

        if ($efektif->status === JadwalPegawai::STATUS_LIBUR) {
            throw AbsensiScanException::reason('Jadwal hari ini libur, tidak dapat melakukan absensi normal.');
        }

        return DB::transaction(function () use ($pegawai, $efektif, $now): array {
            $absensi = $pegawai->absensiPada($now);

            if ($absensi !== null) {
                if ($absensi->jam_pulang !== null) {
                    throw AbsensiScanException::reason('Anda sudah mencatat absen pulang hari ini.');
                }

                $absensi->update(['jam_pulang' => $now]);

                return [
                    'status' => $absensi->fresh()->status,
                    'message' => 'Absen pulang berhasil dicatat.',
                ];
            }

            $status = $this->statusForScanTime($efektif, $now);

            Absensi::create([
                'pegawai_id' => $pegawai->id,
                'tanggal' => $now->toDateString(),
                'jam_masuk' => $now,
                'jam_pulang' => null,
                'status' => $status,
            ]);

            return [
                'status' => $status,
                'message' => $status === Absensi::STATUS_HADIR
                    ? 'Absen masuk berhasil dicatat. Selamat bekerja!'
                    : 'Absen masuk berhasil dicatat. Anda tercatat terlambat.',
            ];
        });
    }

    private function statusForScanTime(JadwalEfektif $efektif, CarbonImmutable $now): string
    {
        $jamMasuk = $efektif->jamMasuk;

        if ($jamMasuk === null) {
            return Absensi::STATUS_HADIR;
        }

        return $now->lessThanOrEqualTo($jamMasuk)
            ? Absensi::STATUS_HADIR
            : Absensi::STATUS_TERLAMBAT;
    }
}
