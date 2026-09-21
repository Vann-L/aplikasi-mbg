<?php

namespace App\Models;

use Database\Factories\SekolahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'sekolah')]
#[Fillable(['npsn', 'nama', 'alamat', 'kontak', 'jumlah_penerima', 'status'])]
class Sekolah extends Model
{
    /** @use HasFactory<SekolahFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Get all distributions destined for this school.
     */
    public function distribusis(): HasMany
    {
        return $this->hasMany(Distribusi::class, 'sekolah_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_penerima' => 'integer',
        ];
    }
}
