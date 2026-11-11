<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\KunjunganController;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Medisin
|--------------------------------------------------------------------------
*/

// Public Authentication & Security Helper Routes
Route::get('/captcha', [CaptchaController::class, 'generate'])
    ->middleware('throttle:30,1')
    ->name('api.captcha');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('api.login');
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/security/encrypt', [SecurityController::class, 'encrypt'])->name('api.security.encrypt');

// Protected Routes (Requires Bearer Token)
Route::middleware('auth:sanctum')->group(function (): void {
    // Current User Profile & Logout
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Pasien (Patient Management)
    Route::get('/pasien', [PasienController::class, 'index'])->name('api.pasien.index');
    Route::post('/pasien', [PasienController::class, 'store'])->name('api.pasien.store');
    Route::get('/pasien/search/{no_rekam_medik}', [PasienController::class, 'findByRekamMedik'])->name('api.pasien.search');
    Route::get('/pasien/{pasien}', [PasienController::class, 'show'])->name('api.pasien.show');

    // Kunjungan (Patient Visits)
    Route::get('/kunjungan', [KunjunganController::class, 'index'])->name('api.kunjungan.index');
    Route::post('/kunjungan', [KunjunganController::class, 'store'])->name('api.kunjungan.store');
    Route::get('/kunjungan/{kunjungan}', [KunjunganController::class, 'show'])->name('api.kunjungan.show');
});
