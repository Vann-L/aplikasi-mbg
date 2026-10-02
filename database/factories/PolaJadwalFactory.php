<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\PolaJadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolaJadwal>
 */
class PolaJadwalFactory extends Factory
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
            'berlaku_mulai' => fake()->date(),
            'hari' => static::polaHari(['07:00', '16:00']),
            'keterangan' => null,
        ];
    }

    /**
     * Build a seven-day pattern array where Monday to Friday are work days.
     *
     * @param  array{0: string, 1: string}  $jam  [jam_masuk, jam_pulang]
     * @return list<array{status: string, jam_masuk: string|null, jam_pulang: string|null}>
     */
    public static function polaHari(array $jam = ['07:00', '16:00']): array
    {
        $hari = [];

        for ($i = 0; $i < 7; $i++) {
            $kerja = $i < 5;

            $hari[] = $kerja
                ? ['status' => PolaJadwal::STATUS_KERJA, 'jam_masuk' => $jam[0], 'jam_pulang' => $jam[1]]
                : ['status' => PolaJadwal::STATUS_LIBUR, 'jam_masuk' => null, 'jam_pulang' => null];
        }

        return $hari;
    }
}
