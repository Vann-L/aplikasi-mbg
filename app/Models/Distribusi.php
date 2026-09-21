<?php

namespace App\Models;

use Database\Factories\DistribusiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Table(name: 'distribusi')]
#[Fillable(['kode_distribusi', 'produksi_id', 'sekolah_id', 'tanggal', 'jumlah_porsi', 'petugas_id', 'kendaraan', 'jam_berangkat', 'status', 'catatan', 'created_by'])]
class Distribusi extends Model
{
    /** @use HasFactory<DistribusiFactory> */
    use HasFactory;

    public const STATUS_DIJADWALKAN = 'dijadwalkan';

    public const STATUS_DISIAPKAN = 'disiapkan';

    public const STATUS_DIKIRIM = 'dikirim';

    public const STATUS_DITERIMA = 'diterima';

    public const STATUS_SELESAI = 'selesai';

    /**
     * Status in the planning stage: can still be scheduled, edited, or deleted.
     */
    public const STATUS_PERENCANAAN = [
        self::STATUS_DIJADWALKAN,
        self::STATUS_DISIAPKAN,
    ];

    /**
     * Generate a unique distribution code, e.g. DST-20260921-AB12C.
     */
    public static function kodeBaru(): string
    {
        return 'DST-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    /**
     * Get the production that supplied this distribution.
     */
    public function produksi(): BelongsTo
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    /**
     * Get the school this distribution is destined for.
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * Get the petugas assigned to this distribution.
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'petugas_id');
    }

    /**
     * Get the user who created this distribution.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the single reception record for this distribution.
     */
    public function penerimaan(): HasOne
    {
        return $this->hasOne(Penerimaan::class, 'distribusi_id');
    }

    /**
     * Difference between the shipped portions and the received portions.
     * Returns null while no reception has been recorded yet.
     */
    public function selisihTerkirimDiterima(): ?int
    {
        $diterima = $this->penerimaan?->jumlah_diterima;

        if ($diterima === null) {
            return null;
        }

        return $this->jumlah_porsi - (int) $diterima;
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
            'jumlah_porsi' => 'integer',
            'jam_berangkat' => 'datetime',
        ];
    }
}
