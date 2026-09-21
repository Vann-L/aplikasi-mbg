<?php

namespace Database\Factories;

use App\Models\Distribusi;
use App\Models\Penerimaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penerimaan>
 */
class PenerimaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'distribusi_id' => Distribusi::factory(),
            'jumlah_diterima' => fake()->numberBetween(200, 1000),
            'waktu_diterima' => fake()->dateTime(),
            'penerima_nama' => fake()->name(),
            'foto_bukti' => null,
            'catatan' => null,
            'created_by' => User::factory(),
        ];
    }
}
