<?php

namespace App\Values;

use Carbon\CarbonImmutable;

/**
 * Immutable result of resolving the effective schedule of a pegawai on a date.
 * "Sumber" tells where the schedule comes from for audit/UI purposes:
 * kalender (SPPG-wide libur), override (jadwal_pegawai) or pola (weekly).
 */
final readonly class JadwalEfektif
{
    public function __construct(
        public ?string $status,
        public ?CarbonImmutable $jamMasuk,
        public ?CarbonImmutable $jamPulang,
        public string $sumber,
    ) {}
}
