<?php

namespace Database\Factories;

use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalPegawai>
 */
class JadwalPegawaiFactory extends Factory
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
            'jam_masuk' => '07:00',
            'jam_pulang' => '16:00',
            'status' => 'terjadwal',
            'catatan' => null,
        ];
    }
}
