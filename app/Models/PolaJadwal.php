<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PolaJadwalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'pola_jadwal')]
#[Fillable(['pegawai_id', 'berlaku_mulai', 'hari', 'keterangan'])]
class PolaJadwal extends Model
{
    /** @use HasFactory<PolaJadwalFactory> */
    use HasFactory;

    public const STATUS_KERJA = 'kerja';

    public const STATUS_LIBUR = 'libur';

    /**
     * Day names indexed by Carbon's ISO weekday minus one
     * (0 = Senin ... 6 = Minggu).
     *
     * @var list<string>
     */
    public const NAMA_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /**
     * Get the pegawai this weekly pattern belongs to.
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    /**
     * Get the day entry that applies to the given date.
     *
     * @return array{status: string, jam_masuk: string|null, jam_pulang: string|null}
     */
    public function hariPada(CarbonImmutable $tanggal): array
    {
        $index = $tanggal->dayOfWeekIso - 1;
        $hari = $this->hari[$index] ?? ['status' => self::STATUS_LIBUR];

        return [
            'status' => $hari['status'] ?? self::STATUS_LIBUR,
            'jam_masuk' => $hari['jam_masuk'] ?? null,
            'jam_pulang' => $hari['jam_pulang'] ?? null,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date',
            'hari' => 'array',
        ];
    }
}
