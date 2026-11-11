<?php

namespace Database\Factories;

use App\Models\Pasien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pasien>
 */
class PasienFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_rekam_medik' => Pasien::generateNextNoRekamMedik(),
            'nama_pasien' => fake()->name(),
            'nik' => fake()->unique()->numerify('32##############'),
            'alamat' => fake()->address(),
        ];
    }
}
