<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Records an attendance scan (masuk or pulang) for a pegawai using a dynamic QR
 * token. Identity always comes from the logged-in user (via users → pegawai),
 * never from the request payload.
 */
class AbsensiService
{
    public function __construct(private readonly AbsensiQrService $qr) {}

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

        $jadwal = $pegawai->jadwalPada($now);

        if ($jadwal === null) {
            throw AbsensiScanException::reason('Tidak ada jadwal kerja untuk hari ini, absensi tidak dapat dilakukan.');
        }

        if ($jadwal->status === JadwalPegawai::STATUS_IZIN) {
            throw AbsensiScanException::reason('Jadwal hari ini izin, tidak dapat melakukan absensi normal.');
        }

        if ($jadwal->status === JadwalPegawai::STATUS_LIBUR) {
            throw AbsensiScanException::reason('Jadwal hari ini libur, tidak dapat melakukan absensi normal.');
        }

        return DB::transaction(function () use ($pegawai, $jadwal, $now): array {
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

            $status = $this->statusForScanTime($jadwal, $now);

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

    private function statusForScanTime(JadwalPegawai $jadwal, CarbonImmutable $now): string
    {
        $jadwalJamMasuk = $jadwal->jam_masuk;

        if ($jadwalJamMasuk === null) {
            return Absensi::STATUS_HADIR;
        }

        $batasMasuk = $now->copy()->setTimeFromTimeString($jadwalJamMasuk->format('H:i'));

        return $now->lessThanOrEqualTo($batasMasuk)
            ? Absensi::STATUS_HADIR
            : Absensi::STATUS_TERLAMBAT;
    }
}
