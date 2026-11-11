<?php

namespace Tests\Feature;

use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KunjunganApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_kunjungan(): void
    {
        $response = $this->getJson('/api/kunjungan');
        $response->assertStatus(401);
    }

    public function test_can_create_kunjungan_using_no_rekam_medik(): void
    {
        $pasien = Pasien::factory()->create([
            'no_rekam_medik' => 'RM0001',
            'nama_pasien' => 'NURFAZA M.',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/kunjungan', [
            'no_rekam_medik' => 'RM0001',
            'keluhan' => 'Demam dan flu sejak 2 hari lalu',
            'catatan' => 'Diberikan paracetamol',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Kunjungan berhasil dibuat',
                'data' => [
                    'pasien_id' => $pasien->id,
                    'keluhan' => 'Demam dan flu sejak 2 hari lalu',
                    'catatan' => 'Diberikan paracetamol',
                    'pasien' => [
                        'id' => $pasien->id,
                        'no_rekam_medik' => 'RM0001',
                        'nama_pasien' => 'NURFAZA M.',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('kunjungans', [
            'pasien_id' => $pasien->id,
            'keluhan' => 'Demam dan flu sejak 2 hari lalu',
        ]);
    }

    public function test_can_create_kunjungan_using_pasien_id(): void
    {
        $pasien = Pasien::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/kunjungan', [
            'pasien_id' => $pasien->id,
            'keluhan' => 'Kontrol tekanan darah',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pasien_id' => $pasien->id,
                    'keluhan' => 'Kontrol tekanan darah',
                ],
            ]);
    }

    public function test_create_kunjungan_fails_with_invalid_no_rekam_medik(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/kunjungan', [
            'no_rekam_medik' => 'RM9999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_rekam_medik']);
    }

    public function test_create_kunjungan_increments_jml_kunjungan_in_pasien_list(): void
    {
        $pasien = Pasien::factory()->create([
            'no_rekam_medik' => 'RM0002',
            'nama_pasien' => 'NURFAZA D.',
        ]);

        // Initially 0 kunjungan
        $listBefore = $this->actingAs($this->user, 'sanctum')->getJson('/api/pasien');
        $this->assertEquals(0, $listBefore->json('data.0.jml_kunjungan'));

        // Add 1 visit
        $this->actingAs($this->user, 'sanctum')->postJson('/api/kunjungan', [
            'no_rekam_medik' => 'RM0002',
        ]);

        // Now 1 kunjungan
        $listAfter = $this->actingAs($this->user, 'sanctum')->getJson('/api/pasien');
        $this->assertEquals(1, $listAfter->json('data.0.jml_kunjungan'));
    }

    public function test_can_list_kunjungans(): void
    {
        $pasien = Pasien::factory()->create();
        Kunjungan::factory()->count(3)->create(['pasien_id' => $pasien->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/kunjungan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar kunjungan berhasil diambil',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_kunjungans_by_pasien_id(): void
    {
        $pasien1 = Pasien::factory()->create();
        $pasien2 = Pasien::factory()->create();

        Kunjungan::factory()->count(2)->create(['pasien_id' => $pasien1->id]);
        Kunjungan::factory()->count(4)->create(['pasien_id' => $pasien2->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/kunjungan?pasien_id='.$pasien1->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_kunjungan_detail(): void
    {
        $kunjungan = Kunjungan::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/kunjungan/'.$kunjungan->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $kunjungan->id,
                    'pasien_id' => $kunjungan->pasien_id,
                ],
            ]);
    }

    // === NEGATIVE TESTS ===

    public function test_create_kunjungan_fails_with_empty_body(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/kunjungan', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_rekam_medik']);
    }

    public function test_create_kunjungan_fails_with_non_existent_pasien_id(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/kunjungan', [
            'pasien_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pasien_id']);
    }

    public function test_get_non_existent_kunjungan_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/kunjungan/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}
