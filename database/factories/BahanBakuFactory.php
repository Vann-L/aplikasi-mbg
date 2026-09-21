<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BahanBaku>
 */
class BahanBakuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->words(2, true),
            'satuan' => fake()->randomElement(['kg', 'gr', 'liter', 'pcs', 'kemasan']),
            'stok_minimum' => fake()->randomFloat(2, 1, 50),
            'status' => BahanBaku::STATUS_ACTIVE,
        ];
    }
}
