<?php

namespace App\Models;

use Database\Factories\KalenderOperasionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'kalender_operasional')]
#[Fillable(['tanggal', 'status', 'alasan', 'catatan'])]
class KalenderOperasional extends Model
{
    /** @use HasFactory<KalenderOperasionalFactory> */
    use HasFactory;

    public const STATUS_BUKA = 'buka';

    public const STATUS_LIBUR = 'libur';

    public const STATUSES = [
        self::STATUS_BUKA,
        self::STATUS_LIBUR,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
