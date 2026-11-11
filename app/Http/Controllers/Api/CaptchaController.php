<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    /**
     * Generate a new captcha image and its one-time verification token.
     *
     * The plaintext captcha code is never returned. For manual API testing in
     * non-production environments with the CAPTCHA_DEBUG_ENABLED flag turned
     * on, it can be exposed with the `?debug=1` query parameter.
     */
    public function generate(Request $request, CaptchaService $captcha): JsonResponse
    {
        $data = $captcha->generate($request->ip());

        $payload = [
            'captcha_image' => $captcha->renderImage($data['captcha_code']),
            'tokenCaptcha' => $data['tokenCaptcha'],
            'expires_in' => $data['expires_in'],
        ];

        if ($this->debugAllowed() && $request->boolean('debug')) {
            $payload['captcha_code'] = $data['captcha_code'];
        }

        return response()->json([
            'success' => true,
            'message' => 'Captcha berhasil dibuat',
            'data' => $payload,
        ]);
    }

    /**
     * Determine whether debug exposure is allowed: it requires both a
     * non-production environment and the explicit CAPTCHA_DEBUG_ENABLED flag.
     */
    protected function debugAllowed(): bool
    {
        return ! app()->isProduction() && (bool) config('captcha.debug_enabled', false);
    }
}
