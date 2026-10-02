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

    public const STATUSES = [
        self::STATUS_TERJADWAL,
        self::STATUS_IZIN,
        self::STATUS_LIBUR,
    ];

    /**
     * Get the pegawai this schedule belongs to.
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
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
