<?php

namespace Database\Factories;

use App\Models\KalenderOperasional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KalenderOperasional>
 */
class KalenderOperasionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->unique()->date(),
            'status' => KalenderOperasional::STATUS_BUKA,
            'alasan' => null,
            'catatan' => null,
        ];
    }
}
