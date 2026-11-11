<?php

namespace Tests\Feature;

use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasienApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_request_is_unauthorized(): void
    {
        $response = $this->getJson('/api/pasien');
        $response->assertStatus(401);
    }

    public function test_can_list_patients_with_visit_counts(): void
    {
        $pasien1 = Pasien::factory()->create([
            'no_rekam_medik' => 'RM0001',
            'nama_pasien' => 'NURFAZA M.',
            'nik' => '321412412312412',
            'alamat' => 'Jl Wisata 2 No 18',
        ]);

        Kunjungan::factory()->count(3)->create([
            'pasien_id' => $pasien1->id,
        ]);

        $pasien2 = Pasien::factory()->create([
            'no_rekam_medik' => 'RM0002',
            'nama_pasien' => 'NURFAZA D.',
            'nik' => '321412412355553',
            'alamat' => 'Jl Wisata 2 No 18',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/pasien');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar pasien berhasil diambil',
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.no_rekam_medik', 'RM0001')
            ->assertJsonPath('data.0.jml_kunjungan', 3)
            ->assertJsonPath('data.1.no_rekam_medik', 'RM0002')
            ->assertJsonPath('data.1.jml_kunjungan', 0);
    }

    public function test_can_register_new_patient_with_auto_generated_no_rekam_medik(): void
    {
        $payload = [
            'nama_pasien' => 'Ahmad Dahlan',
            'nik' => '3201123456789001',
            'alamat' => 'Jl Merdeka No 45',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pasien berhasil didaftarkan',
                'data' => [
                    'no_rekam_medik' => 'RM0001',
                    'nama_pasien' => 'Ahmad Dahlan',
                    'nik' => '3201123456789001',
                    'alamat' => 'Jl Merdeka No 45',
                    'jml_kunjungan' => 0,
                ],
            ]);

        $this->assertDatabaseHas('pasiens', [
            'no_rekam_medik' => 'RM0001',
            'nik_hash' => hash('sha256', '3201123456789001'),
        ]);

        $created = Pasien::where('no_rekam_medik', 'RM0001')->first();
        $this->assertNotNull($created);
        $this->assertEquals('3201123456789001', $created->nik);
        $this->assertNotEquals('3201123456789001', $created->getRawOriginal('nik'));
    }

    public function test_auto_generates_sequential_no_rekam_medik(): void
    {
        Pasien::factory()->create(['no_rekam_medik' => 'RM0001']);
        Pasien::factory()->create(['no_rekam_medik' => 'RM0002']);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'Pasien Ketiga',
            'nik' => '3201999999999999',
            'alamat' => 'Jl Kenanga No 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.no_rekam_medik', 'RM0003');
    }

    public function test_can_register_patient_with_custom_no_rekam_medik(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'no_rekam_medik' => 'RM9999',
            'nama_pasien' => 'Budi Santoso',
            'nik' => '3201888888888888',
            'alamat' => 'Jl Mawar No 10',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.no_rekam_medik', 'RM9999');
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonValidationErrors(['nama_pasien', 'nik', 'alamat']);
    }

    public function test_validation_fails_when_nik_is_duplicate(): void
    {
        Pasien::factory()->create([
            'nik' => '3201111111111111',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'Siti Nurhaliza',
            'nik' => '3201111111111111',
            'alamat' => 'Jl Melati No 3',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_can_find_patient_by_no_rekam_medik(): void
    {
        $pasien = Pasien::factory()->create([
            'no_rekam_medik' => 'RM0001',
            'nama_pasien' => 'NURFAZA M.',
            'nik' => '321412412312412',
            'alamat' => 'Jl Wisata 2 No 18',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pasien/search/RM0001');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $pasien->id,
                    'no_rekam_medik' => 'RM0001',
                    'nama_pasien' => 'NURFAZA M.',
                    'nik' => '321412412312412',
                    'alamat' => 'Jl Wisata 2 No 18',
                ],
            ]);
    }

    public function test_finding_non_existent_patient_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pasien/search/RM9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_get_patient_detail(): void
    {
        $pasien = Pasien::factory()->create();
        Kunjungan::factory()->count(2)->create(['pasien_id' => $pasien->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pasien/'.$pasien->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $pasien->id,
                    'jml_kunjungan' => 2,
                ],
            ])
            ->assertJsonCount(2, 'data.kunjungans');
    }

    // === NEGATIVE TESTS ===

    public function test_validation_fails_when_nik_contains_non_numeric_characters(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'Test Patient',
            'nik' => '3201ABC456789',
            'alamat' => 'Jl Test No 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik'])
            ->assertJsonPath('errors.nik.0', 'NIK harus berupa angka.');
    }

    public function test_validation_fails_when_nik_is_too_short(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'Test Patient',
            'nik' => '12345',
            'alamat' => 'Jl Test No 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik'])
            ->assertJsonPath('errors.nik.0', 'NIK minimal 10 digit angka.');
    }

    public function test_validation_fails_when_nik_is_too_long(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'Test Patient',
            'nik' => '123456789012345678901',
            'alamat' => 'Jl Test No 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_validation_fails_when_nama_pasien_is_too_short(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'A',
            'nik' => '3201123456789001',
            'alamat' => 'Jl Test No 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_pasien'])
            ->assertJsonPath('errors.nama_pasien.0', 'Nama pasien minimal 2 karakter.');
    }

    public function test_validation_fails_when_alamat_is_too_short(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'nama_pasien' => 'Test Patient',
            'nik' => '3201123456789001',
            'alamat' => 'Jl',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['alamat'])
            ->assertJsonPath('errors.alamat.0', 'Alamat minimal 3 karakter.');
    }

    public function test_validation_fails_when_no_rekam_medik_has_invalid_format(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'no_rekam_medik' => 'ABC123',
            'nama_pasien' => 'Test Patient',
            'nik' => '3201123456789001',
            'alamat' => 'Jl Test No 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_rekam_medik'])
            ->assertJsonPath('errors.no_rekam_medik.0', 'Format nomor rekam medik harus diawali RM diikuti angka (contoh: RM0001).');
    }

    public function test_validation_fails_when_no_rekam_medik_is_duplicate(): void
    {
        Pasien::factory()->create(['no_rekam_medik' => 'RM0001']);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pasien', [
            'no_rekam_medik' => 'RM0001',
            'nama_pasien' => 'Test Patient',
            'nik' => '3201123456789001',
            'alamat' => 'Jl Test No 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_rekam_medik'])
            ->assertJsonPath('errors.no_rekam_medik.0', 'Nomor rekam medik sudah terdaftar.');
    }

    public function test_get_non_existent_patient_detail_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pasien/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}
