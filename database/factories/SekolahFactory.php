<?php

namespace Database\Factories;

use App\Models\Sekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sekolah>
 */
class SekolahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'npsn' => fake()->unique()->numerify('##########'),
            'nama' => 'SDN '.fake()->unique()->numberBetween(1, 999),
            'alamat' => fake()->address(),
            'kontak' => fake()->phoneNumber(),
            'jumlah_penerima' => fake()->numberBetween(100, 1000),
            'status' => Sekolah::STATUS_ACTIVE,
        ];
    }
}
