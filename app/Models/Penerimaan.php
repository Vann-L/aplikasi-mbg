<?php

namespace App\Models;

use Database\Factories\PenerimaanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'penerimaan')]
#[Fillable(['distribusi_id', 'jumlah_diterima', 'waktu_diterima', 'penerima_nama', 'foto_bukti', 'catatan', 'created_by'])]
class Penerimaan extends Model
{
    /** @use HasFactory<PenerimaanFactory> */
    use HasFactory;

    /**
     * Get the distribution this reception belongs to.
     */
    public function distribusi(): BelongsTo
    {
        return $this->belongsTo(Distribusi::class, 'distribusi_id');
    }

    /**
     * Get the user who recorded this reception.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_diterima' => 'integer',
            'waktu_diterima' => 'datetime',
        ];
    }
}
