<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absensi>
 */
class AbsensiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'tanggal' => fake()->date(),
            'jam_masuk' => fake()->dateTimeBetween('-1 day', 'now'),
            'jam_pulang' => fake()->dateTimeBetween('now', '+1 day'),
            'status' => 'hadir',
        ];
    }
}
