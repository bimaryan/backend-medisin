<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CaptchaService;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a fresh captcha code and token pair for login requests.
     *
     * @return array{captcha_code: string, tokenCaptcha: string}
     */
    protected function getCaptcha(): array
    {
        $data = app(CaptchaService::class)->generate();

        return [
            'captcha_code' => $data['captcha_code'],
            'tokenCaptcha' => $data['tokenCaptcha'],
        ];
    }

    public function test_user_can_login_with_valid_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
            ...$this->getCaptcha(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'access_token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_user_can_login_with_encrypted_payload(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $security = app(SecurityService::class);
        $encryptedUser = $security->encrypt('admin');
        $encryptedPass = $security->encrypt('password');

        $response = $this->postJson('/api/login', [
            'user_name' => $encryptedUser,
            'password' => $encryptedPass,
            ...$this->getCaptcha(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonPath('data.user.username', 'admin');
    }

    public function test_user_can_login_with_encrypted_captcha_code(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $captcha = $this->getCaptcha();
        $encryptedCaptcha = app(SecurityService::class)->encrypt($captcha['captcha_code']);

        $response = $this->postJson('/api/login', [
            'user_name' => 'admin',
            'password' => 'password',
            'captcha_code' => $encryptedCaptcha,
            'tokenCaptcha' => $captcha['tokenCaptcha'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonPath('data.user.username', 'admin');
    }

    public function test_user_email_is_encrypted_at_rest_in_database(): void
    {
        $user = User::factory()->create([
            'email' => 'doctor@medisin.com',
        ]);

        $this->assertEquals('doctor@medisin.com', $user->email);
        $rawEmail = $user->getRawOriginal('email');
        $this->assertNotEquals('doctor@medisin.com', $rawEmail);
        $this->assertStringStartsWith('eyJpdi', $rawEmail);
    }

    public function test_user_can_login_with_valid_email_and_password(): void
    {
        User::factory()->create([
            'email' => 'admin@medisin.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@medisin.com',
            'password' => 'password',
            ...$this->getCaptcha(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
            ...$this->getCaptcha(),
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username atau password salah.',
            ]);
    }

    public function test_login_validation_fails_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonValidationErrors(['username', 'password', 'captcha_code', 'tokenCaptcha']);
    }

    public function test_captcha_endpoint_returns_image_and_token_without_exposing_code(): void
    {
        $response = $this->getJson('/api/captcha');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Captcha berhasil dibuat',
            ])
            ->assertJsonStructure([
                'data' => ['captcha_image', 'tokenCaptcha', 'expires_in'],
            ]);

        $data = $response->json('data');

        $this->assertStringStartsWith('data:image/png;base64,', $data['captcha_image']);
        $this->assertArrayNotHasKey('captcha_code', $data);
    }

    public function test_captcha_debug_param_is_ignored_by_default(): void
    {
        config(['captcha.debug_enabled' => false]);

        $data = $this->getJson('/api/captcha?debug=1')->json('data');

        $this->assertArrayNotHasKey('captcha_code', $data);
        $this->assertArrayHasKey('captcha_image', $data);
    }

    public function test_captcha_debug_mode_exposes_code_when_explicitly_enabled(): void
    {
        config(['captcha.debug_enabled' => true]);

        $data = $this->getJson('/api/captcha?debug=1')->json('data');

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{5}$/', $data['captcha_code']);
        $this->assertArrayHasKey('captcha_image', $data);
        $this->assertArrayHasKey('tokenCaptcha', $data);
    }

    public function test_captcha_token_is_bound_to_requesting_client_ip(): void
    {
        $service = app(CaptchaService::class);

        // Verification from a different IP must be rejected
        $foreignIpCaptcha = $service->generate('203.0.113.10');
        $this->assertFalse($service->verify(
            $foreignIpCaptcha['tokenCaptcha'],
            $foreignIpCaptcha['captcha_code'],
            '198.51.100.99',
        ));

        // Verification from the original IP must succeed
        $captcha = $service->generate('203.0.113.10');
        $this->assertTrue($service->verify(
            $captcha['tokenCaptcha'],
            $captcha['captcha_code'],
            '203.0.113.10',
        ));
    }

    public function test_captcha_code_is_stored_hashed_not_as_plaintext(): void
    {
        $captcha = app(CaptchaService::class)->generate('203.0.113.10');

        $reflection = new \ReflectionClass(app(CaptchaService::class));
        $method = $reflection->getMethod('cacheKey');
        $method->setAccessible(true);
        $cacheKey = $method->invoke(app(CaptchaService::class), $captcha['tokenCaptcha']);

        $stored = Cache::get($cacheKey);

        $this->assertIsString($stored);
        $this->assertStringNotContainsString($captcha['captcha_code'], $stored);
        $this->assertStringContainsString('203.0.113.10', $stored);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}\|203\.0\.113\.10$/', $stored);
    }

    public function test_login_fails_when_captcha_is_missing(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_code', 'tokenCaptcha']);
    }

    public function test_login_fails_with_wrong_captcha_code(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
            'captcha_code' => 'XXXXX',
            'tokenCaptcha' => $this->getCaptcha()['tokenCaptcha'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_code']);
    }

    public function test_login_fails_when_captcha_token_is_invalid_or_expired(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
            'captcha_code' => 'AB2CD',
            'tokenCaptcha' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_code']);
    }

    public function test_login_fails_when_captcha_token_is_reused(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $captcha = $this->getCaptcha();

        // First attempt consumes the token but uses a wrong password
        $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
            'captcha_code' => $captcha['captcha_code'],
            'tokenCaptcha' => $captcha['tokenCaptcha'],
        ])->assertStatus(401);

        // Reusing the same captcha token must be rejected
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
            'captcha_code' => $captcha['captcha_code'],
            'tokenCaptcha' => $captcha['tokenCaptcha'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_code']);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'username' => 'admin',
                ],
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil',
            ]);

        $this->assertDatabaseEmpty('personal_access_tokens');
    }

    public function test_security_encrypt_helper_endpoint(): void
    {
        $response = $this->postJson('/api/security/encrypt', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payload berhasil dienkripsi',
            ])
            ->assertJsonStructure([
                'data' => [
                    'encrypted_payload' => [
                        'username',
                        'password',
                    ],
                    'algorithm',
                ],
            ]);
    }
}
