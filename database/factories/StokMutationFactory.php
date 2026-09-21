<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\StokMutation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StokMutation>
 */
class StokMutationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bahan_baku_id' => BahanBaku::factory(),
            'tipe' => fake()->randomElement(['masuk', 'keluar', 'penyesuaian']),
            'jumlah' => fake()->randomFloat(2, 1, 500),
            'tanggal' => fake()->date(),
            'keterangan' => fake()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
