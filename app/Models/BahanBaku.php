<?php

namespace App\Models;

use Database\Factories\BahanBakuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Table(name: 'bahan_baku')]
#[Fillable(['nama', 'satuan', 'stok_minimum', 'status'])]
class BahanBaku extends Model
{
    /** @use HasFactory<BahanBakuFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STOK_STATUS_HABIS = 'habis';

    public const STOK_STATUS_RENDAH = 'rendah';

    public const STOK_STATUS_NORMAL = 'normal';

    /**
     * Get all stock mutations for this ingredient.
     */
    public function stokMutations(): HasMany
    {
        return $this->hasMany(StokMutation::class, 'bahan_baku_id');
    }

    /**
     * Get all production usage records for this ingredient.
     */
    public function produksiBahans(): HasMany
    {
        return $this->hasMany(ProduksiBahan::class, 'bahan_baku_id');
    }

    /**
     * Compute the available stock from all mutations (masuk +, keluar -, penyesuaian signed).
     */
    public function stokTersedia(): float
    {
        return (float) $this->stokMutations()
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN jumlah WHEN tipe = 'keluar' THEN -jumlah ELSE jumlah END), 0) as total")
            ->value('total');
    }

    /**
     * Map of all ingredients and their available stock, computed in a single query.
     *
     * @return Collection<int, float> keyed by bahan_baku id
     */
    public static function stokTersediaPerBahan(): Collection
    {
        $balances = DB::table('stok_mutations')
            ->selectRaw("bahan_baku_id, SUM(CASE WHEN tipe = 'masuk' THEN jumlah WHEN tipe = 'keluar' THEN -jumlah ELSE jumlah END) as total")
            ->groupBy('bahan_baku_id')
            ->get();

        return collect($balances)->pluck('total', 'bahan_baku_id')->map(fn ($value): float => (float) $value);
    }

    /**
     * Classify an available stock level against the configured minimum.
     */
    public function statusStok(float $stokTersedia): string
    {
        if ($stokTersedia <= 0) {
            return self::STOK_STATUS_HABIS;
        }

        if ($stokTersedia < (float) $this->stok_minimum) {
            return self::STOK_STATUS_RENDAH;
        }

        return self::STOK_STATUS_NORMAL;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stok_minimum' => 'decimal:2',
        ];
    }
}
