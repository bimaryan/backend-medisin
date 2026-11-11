<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaService
{
    /**
     * Fallback captcha lifetime in seconds (5 minutes).
     */
    public const TTL_SECONDS = 300;

    /**
     * Length of the generated captcha code.
     */
    public const CODE_LENGTH = 5;

    protected const CACHE_PREFIX = 'captcha:';

    /**
     * Character set without visually ambiguous characters (no I, O, 0, 1).
     */
    protected const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Generate a new captcha code paired with a one-time token.
     *
     * The plaintext code is never stored: only its SHA-256 hash is kept in
     * the cache. When $ip is provided, the token is bound to that client IP.
     *
     * @return array{captcha_code: string, tokenCaptcha: string, expires_in: int}
     */
    public function generate(?string $ip = null): array
    {
        $code = $this->randomCode();
        $token = (string) Str::uuid();

        Cache::put(
            $this->cacheKey($token),
            $this->storedValue($code, $ip),
            $this->ttl(),
        );

        return [
            'captcha_code' => $code,
            'tokenCaptcha' => $token,
            'expires_in' => $this->ttl(),
        ];
    }

    /**
     * Render the captcha code into a distorted PNG image (data URI) so the
     * plaintext code is never exposed to automated clients.
     */
    public function renderImage(string $code): string
    {
        $width = 150;
        $height = 50;

        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 245, 247, 250);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $background);

        // Scatter background noise dots
        for ($i = 0; $i < 250; $i++) {
            $color = imagecolorallocate($image, random_int(140, 210), random_int(140, 210), random_int(140, 210));
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $color);
        }

        // Draw each character individually with a random rotation and position
        $slot = (int) floor(($width - 24) / strlen($code));

        foreach (str_split($code) as $index => $char) {
            $charImage = imagecreatetruecolor(22, 30);
            $charBackground = imagecolorallocate($charImage, 245, 247, 250);
            imagefilledrectangle($charImage, 0, 0, 21, 29, $charBackground);

            $charColor = imagecolorallocate($charImage, random_int(20, 90), random_int(20, 90), random_int(20, 90));
            imagestring($charImage, 5, 4, 7, $char, $charColor);

            $rotated = imagerotate($charImage, (float) random_int(-35, 35), $charBackground);
            imagecopy($image, $rotated, 12 + ($index * $slot), random_int(2, 8), 0, 0, imagesx($rotated), imagesy($rotated));

            imagedestroy($charImage);
            imagedestroy($rotated);
        }

        // Draw a few distorted noise lines over the text
        for ($i = 0; $i < 4; $i++) {
            $color = imagecolorallocate($image, random_int(100, 180), random_int(100, 180), random_int(100, 180));
            imageline($image, random_int(0, $width - 1), random_int(0, $height - 1), random_int(0, $width - 1), random_int(0, $height - 1), $color);
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Verify a captcha answer against its token. Each token is single-use:
     * it is invalidated after the first verification attempt. When the token
     * was generated with a client IP, verification must come from the same IP.
     */
    public function verify(?string $token, ?string $code, ?string $ip = null): bool
    {
        if ($token === null || $token === '' || $code === null || trim($code) === '') {
            return false;
        }

        $key = $this->cacheKey($token);
        $stored = Cache::get($key);

        if (! is_string($stored)) {
            return false;
        }

        Cache::forget($key);

        // Storage format: "codeHash|ip" (ip segment may be empty when unbound)
        $segments = explode('|', $stored, 2);
        $expectedHash = $segments[0];
        $boundIp = $segments[1] ?? '';

        if ($boundIp !== '' && ($ip === null || ! hash_equals($boundIp, $ip))) {
            return false;
        }

        return hash_equals($expectedHash, hash('sha256', strtoupper(trim($code))));
    }

    /**
     * Build the value stored in cache: code hash (never plaintext) + IP binding.
     */
    protected function storedValue(string $code, ?string $ip): string
    {
        $ipPart = '';

        if ($ip !== null && $ip !== '' && config('captcha.bind_ip', true)) {
            $ipPart = '|'.$ip;
        }

        return hash('sha256', strtoupper($code)).$ipPart;
    }

    /**
     * Generate a random, unambiguous captcha code.
     */
    protected function randomCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }

        return $code;
    }

    /**
     * Get the configured captcha lifetime.
     */
    protected function ttl(): int
    {
        return (int) config('captcha.ttl', self::TTL_SECONDS);
    }

    /**
     * Build the cache key for a captcha token (token itself is never stored).
     */
    protected function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX.hash('sha256', $token);
    }
}
