<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pegawai>
 */
class PegawaiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'id_pegawai' => 'PEG-'.fake()->unique()->numberBetween(1000, 9999),
            'nama' => fake()->name(),
            'jabatan' => fake()->jobTitle(),
            'bagian' => fake()->randomElement(['Operasional', 'Dapur', 'Distribusi', 'QC']),
            'no_hp' => fake()->phoneNumber(),
            'status' => Pegawai::STATUS_ACTIVE,
        ];
    }
}
