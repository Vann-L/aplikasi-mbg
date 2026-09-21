<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Produksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produksi>
 */
class ProduksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'tanggal' => fake()->date(),
            'target_porsi' => fake()->numberBetween(100, 5000),
            'hasil_porsi' => 0,
            'status' => 'belum_dimulai',
            'catatan' => null,
            'created_by' => User::factory(),
        ];
    }
}
