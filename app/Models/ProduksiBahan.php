<?php

namespace App\Models;

use Database\Factories\ProduksiBahanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'produksi_bahan')]
#[Fillable(['produksi_id', 'bahan_baku_id', 'jumlah', 'satuan'])]
class ProduksiBahan extends Model
{
    /** @use HasFactory<ProduksiBahanFactory> */
    use HasFactory;

    /**
     * Get the production that uses this ingredient.
     */
    public function produksi(): BelongsTo
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    /**
     * Get the ingredient used in this production.
     */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'bahan_baku_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }
}
