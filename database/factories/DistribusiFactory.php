<?php

namespace Database\Factories;

use App\Models\Distribusi;
use App\Models\Pegawai;
use App\Models\Produksi;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distribusi>
 */
class DistribusiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_distribusi' => 'DST-'.fake()->unique()->numberBetween(10000, 99999),
            'produksi_id' => Produksi::factory(),
            'sekolah_id' => Sekolah::factory(),
            'tanggal' => fake()->date(),
            'jumlah_porsi' => fake()->numberBetween(200, 1000),
            'petugas_id' => Pegawai::factory(),
            'kendaraan' => fake()->randomElement(['Motor', 'Mobil Box', 'Pickup']),
            'jam_berangkat' => null,
            'status' => 'dijadwalkan',
            'catatan' => null,
            'created_by' => User::factory(),
        ];
    }
}
