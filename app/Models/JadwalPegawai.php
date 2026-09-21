<?php

namespace App\Models;

use Database\Factories\JadwalPegawaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'jadwal_pegawai')]
#[Fillable(['pegawai_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'status', 'catatan'])]
class JadwalPegawai extends Model
{
    /** @use HasFactory<JadwalPegawaiFactory> */
    use HasFactory;

    public const STATUS_TERJADWAL = 'terjadwal';

    public const STATUS_IZIN = 'izin';

    public const STATUS_LIBUR = 'libur';

    /**
     * Get the pegawai this schedule belongs to.
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    /**
     * Resolve the attendance status shown in a recap row: hadir/terlambat come
     * from the attendance record, alpa when a terjadwal schedule has none, and
     * izin/libur reflect the schedule itself.
     */
    public function statusRekap(): string
    {
        if ($this->status === self::STATUS_IZIN) {
            return Absensi::STATUS_IZIN;
        }

        if ($this->status === self::STATUS_LIBUR) {
            return self::STATUS_LIBUR;
        }

        $absensi = $this->pegawai->absensi->firstWhere(
            fn (Absensi $absensi): bool => $absensi->tanggal?->toDateString() === $this->tanggal?->toDateString(),
        );

        return $absensi?->status ?? Absensi::STATUS_ALPA;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_masuk' => 'datetime:H:i',
            'jam_pulang' => 'datetime:H:i',
        ];
    }
}
