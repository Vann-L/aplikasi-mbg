<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\Produksi;
use App\Models\ProduksiBahan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProduksiBahan>
 */
class ProduksiBahanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'produksi_id' => Produksi::factory(),
            'bahan_baku_id' => BahanBaku::factory(),
            'jumlah' => fake()->randomFloat(2, 1, 200),
            'satuan' => fake()->randomElement(['kg', 'gr', 'liter', 'pcs']),
        ];
    }
}
