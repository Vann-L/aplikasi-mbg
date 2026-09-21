<?php

namespace App\Models;

use Database\Factories\AbsensiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'absensi')]
#[Fillable(['pegawai_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'status'])]
class Absensi extends Model
{
    /** @use HasFactory<AbsensiFactory> */
    use HasFactory;

    public const STATUS_HADIR = 'hadir';

    public const STATUS_TERLAMBAT = 'terlambat';

    public const STATUS_IZIN = 'izin';

    public const STATUS_ALPA = 'alpa';

    /**
     * Get the pegawai who recorded this attendance.
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
            'jam_masuk' => 'datetime',
            'jam_pulang' => 'datetime',
        ];
    }
}
