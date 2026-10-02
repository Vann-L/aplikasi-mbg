<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use Carbon\CarbonImmutable;

/**
 * Domain rules for work schedules: which fields feed attendance derivation and
 * what a schedule may be once it already has attendance.
 */
class JadwalPegawaiService
{
    /**
     * Fields that participate in the attendance decision. Changing any of them
     * on a schedule that already has attendance would silently rewrite history.
     */
    private const FIELD_ABSENSI = ['pegawai_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'status'];

    /**
     * Whether the schedule's pegawai already recorded attendance on its date.
     */
    public function hasAbsensi(JadwalPegawai $jadwal): bool
    {
        return Absensi::query()
            ->where('pegawai_id', $jadwal->pegawai_id)
            ->whereDate('tanggal', $jadwal->tanggal)
            ->exists();
    }

    /**
     * Which attendance-affecting fields differ between the schedule and the
     * submitted data. Returns an empty array when no protected field changed.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function fieldAbsensiBerubah(JadwalPegawai $jadwal, array $data): array
    {
        $berubah = [];

        foreach (self::FIELD_ABSENSI as $field) {
            if (array_key_exists($field, $data) && $this->nilai($jadwal, $field) !== $this->nilai($data, $field)) {
                $berubah[] = $field;
            }
        }

        return $berubah;
    }

    /**
     * Normalize a single field to a comparable string (or null).
     *
     * @param  JadwalPegawai|array<string, mixed>  $source
     */
    private function nilai(JadwalPegawai|array $source, string $field): ?string
    {
        if ($source instanceof JadwalPegawai) {
            return match ($field) {
                'pegawai_id' => (string) $source->pegawai_id,
                'tanggal' => $source->tanggal?->toDateString(),
                'jam_masuk' => $source->jam_masuk?->format('H:i'),
                'jam_pulang' => $source->jam_pulang?->format('H:i'),
                'status' => $source->status,
                default => null,
            };
        }

        $value = $source[$field] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'tanggal') {
            return CarbonImmutable::parse((string) $value)->toDateString();
        }

        return match ($field) {
            'pegawai_id', 'jam_masuk', 'jam_pulang', 'status' => (string) $value,
            default => null,
        };
    }
}
