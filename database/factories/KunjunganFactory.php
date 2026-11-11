<?php

namespace Database\Factories;

use App\Models\Kunjungan;
use App\Models\Pasien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kunjungan>
 */
class KunjunganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pasien_id' => Pasien::factory(),
            'tanggal_kunjungan' => now(),
            'keluhan' => fake()->sentence(),
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
