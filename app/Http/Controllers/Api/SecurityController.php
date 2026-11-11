<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /**
     * Helper endpoint to encrypt payload values (for testing and frontend integration).
     */
    public function encrypt(Request $request, SecurityService $security): JsonResponse
    {
        $input = $request->all();
        $encrypted = [];

        foreach ($input as $key => $val) {
            if (is_string($val)) {
                $encrypted[$key] = $security->encrypt($val);
            } else {
                $encrypted[$key] = $val;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payload berhasil dienkripsi',
            'data' => [
                'encrypted_payload' => $encrypted,
                'algorithm' => 'AES-256-CBC (Base64)',
            ],
        ]);
    }
}
