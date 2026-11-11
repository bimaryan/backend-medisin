<?php

namespace Database\Seeders;

use App\Models\Kunjungan;
use App\Models\Pasien;
use Illuminate\Database\Seeder;

class PasienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pasien 1 from Figma reference
        $pasien1 = Pasien::firstOrCreate(
            ['no_rekam_medik' => 'RM0001'],
            [
                'nama_pasien' => 'NURFAZA M.',
                'nik' => '321412412312412',
                'alamat' => 'Jl Wisata 2 No 18',
            ]
        );

        // Pasien 1 has 8 kunjungan in Figma reference
        if ($pasien1->kunjungans()->count() === 0) {
            for ($i = 8; $i >= 1; $i--) {
                Kunjungan::create([
                    'pasien_id' => $pasien1->id,
                    'tanggal_kunjungan' => now()->subDays($i * 3),
                    'keluhan' => 'Pemeriksaan rutin ke-'.$i,
                    'catatan' => 'Kondisi stabil',
                ]);
            }
        }

        // Pasien 2 from Figma reference (0 kunjungan)
        Pasien::firstOrCreate(
            ['no_rekam_medik' => 'RM0002'],
            [
                'nama_pasien' => 'NURFAZA D.',
                'nik' => '321412412355553',
                'alamat' => 'Jl Wisata 2 No 18',
            ]
        );
    }
}
