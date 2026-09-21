<?php

namespace App\Models;

use Database\Factories\StokMutationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bahan_baku_id', 'tipe', 'jumlah', 'tanggal', 'keterangan', 'user_id'])]
class StokMutation extends Model
{
    /** @use HasFactory<StokMutationFactory> */
    use HasFactory;

    public const TIPE_MASUK = 'masuk';

    public const TIPE_KELUAR = 'keluar';

    public const TIPE_PENYESUAIAN = 'penyesuaian';

    /**
     * Get the ingredient this mutation belongs to.
     */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'bahan_baku_id');
    }

    /**
     * Get the user who recorded this mutation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
            'tanggal' => 'date',
        ];
    }
}
