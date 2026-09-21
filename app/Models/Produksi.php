<?php

namespace App\Models;

use Database\Factories\ProduksiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'produksi')]
#[Fillable(['menu_id', 'tanggal', 'target_porsi', 'hasil_porsi', 'status', 'catatan', 'started_at', 'completed_at', 'stock_deducted_at', 'created_by'])]
class Produksi extends Model
{
    /** @use HasFactory<ProduksiFactory> */
    use HasFactory;

    public const STATUS_BELUM_DIMULAI = 'belum_dimulai';

    public const STATUS_PERSIAPAN = 'persiapan';

    public const STATUS_PENGOLAHAN = 'pengolahan';

    public const STATUS_QC = 'qc';

    public const STATUS_PEMORSIAN = 'pemorsian';

    public const STATUS_PACKING = 'packing';

    public const STATUS_SELESAI = 'selesai';

    /**
     * Ordered production flow. Status may only advance one step at a time.
     */
    public const STATUS_FLOW = [
        self::STATUS_BELUM_DIMULAI,
        self::STATUS_PERSIAPAN,
        self::STATUS_PENGOLAHAN,
        self::STATUS_QC,
        self::STATUS_PEMORSIAN,
        self::STATUS_PACKING,
        self::STATUS_SELESAI,
    ];

    /**
     * Get the menu being produced.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Get the user who created this production record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all ingredients used in this production.
     */
    public function produksiBahans(): HasMany
    {
        return $this->hasMany(ProduksiBahan::class, 'produksi_id');
    }

    /**
     * Get all distributions produced from this production.
     */
    public function distribusis(): HasMany
    {
        return $this->hasMany(Distribusi::class, 'produksi_id');
    }

    /**
     * Total portion demand derived from the planned distributions.
     */
    public function kebutuhanPorsi(): int
    {
        return (int) $this->distribusis()->sum('jumlah_porsi');
    }

    /**
     * Difference between actual result and demand. Positive means overproduction,
     * negative means a shortage.
     */
    public function selisihPorsi(): int
    {
        return (int) $this->hasil_porsi - $this->kebutuhanPorsi();
    }

    /**
     * Whether the stock required for this production has already been deducted.
     */
    public function stokSudahDipotong(): bool
    {
        return $this->stock_deducted_at !== null;
    }

    /**
     * Whether the recorded result covers the planned portion demand.
     */
    public function hasilMemenuhiKebutuhan(): bool
    {
        return (int) $this->hasil_porsi >= $this->kebutuhanPorsi();
    }

    /**
     * Keep the stored target in sync with the planned distribution demand.
     */
    public function perbaruiTargetPorsi(): void
    {
        $this->forceFill(['target_porsi' => $this->kebutuhanPorsi()])->save();
    }

    /**
     * Get the next allowed status in the production flow.
     */
    public function nextStatus(): ?string
    {
        $index = array_search($this->status, self::STATUS_FLOW, true);

        return $index === false ? null : (self::STATUS_FLOW[$index + 1] ?? null);
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
            'target_porsi' => 'integer',
            'hasil_porsi' => 'integer',
            'started_at' => 'datetime',
            'stock_deducted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
