<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PegawaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Table(name: 'pegawai')]
#[Fillable(['user_id', 'id_pegawai', 'nama', 'jabatan', 'bagian', 'no_hp', 'status'])]
class Pegawai extends Model
{
    /** @use HasFactory<PegawaiFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Get the user account linked to this pegawai.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all work schedules for this pegawai.
     */
    public function jadwalPegawai(): HasMany
    {
        return $this->hasMany(JadwalPegawai::class, 'pegawai_id');
    }

    /**
     * Get all attendance records for this pegawai.
     */
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'pegawai_id');
    }

    /**
     * Get all distributions assigned to this pegawai as petugas.
     */
    public function distribusis(): HasMany
    {
        return $this->hasMany(Distribusi::class, 'petugas_id');
    }

    /**
     * Active pegawai with an active petugas account, eligible as distribution petugas.
     */
    public static function kandidatPetugas(): Collection
    {
        return self::query()
            ->where('status', self::STATUS_ACTIVE)
            ->whereHas('user', function ($query): void {
                $query->where('role', 'petugas')->where('status', 'active');
            })
            ->orderBy('nama')
            ->get();
    }

    /**
     * Get the work schedule for the given date, if any.
     */
    public function jadwalPada(CarbonImmutable $tanggal): ?JadwalPegawai
    {
        return $this->jadwalPegawai()->whereDate('tanggal', $tanggal->toDateString())->first();
    }

    /**
     * Get the attendance record for the given date, if any.
     */
    public function absensiPada(CarbonImmutable $tanggal): ?Absensi
    {
        return $this->absensi()->whereDate('tanggal', $tanggal->toDateString())->first();
    }
}
