<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pasien\StorePasienRequest;
use App\Http\Resources\PasienResource;
use App\Models\Pasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    /**
     * Display a listing of patients with visit counts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pasien::query()->withCount('kunjungans as jml_kunjungan');

        if ($search = $request->input('search', $request->input('q'))) {
            $searchHash = hash('sha256', trim((string) $search));
            $query->where(function ($q) use ($search, $searchHash): void {
                $q->where('nama_pasien', 'like', "%{$search}%")
                    ->orWhere('no_rekam_medik', 'like', "%{$search}%")
                    ->orWhere('nik_hash', $searchHash);
            });
        }

        $pasiens = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pasien berhasil diambil',
            'data' => PasienResource::collection($pasiens),
        ]);
    }

    /**
     * Store a newly created patient in storage.
     */
    public function store(StorePasienRequest $request): JsonResponse
    {
        $data = $request->validated();

        $pasien = Pasien::create($data);
        $pasien->loadCount('kunjungans as jml_kunjungan');

        return response()->json([
            'success' => true,
            'message' => 'Pasien berhasil didaftarkan',
            'data' => new PasienResource($pasien),
        ], 201);
    }

    /**
     * Display the specified patient with visits history.
     */
    public function show(Pasien $pasien): JsonResponse
    {
        $pasien->load(['kunjungans' => fn ($q) => $q->latest('tanggal_kunjungan')])
            ->loadCount('kunjungans as jml_kunjungan');

        return response()->json([
            'success' => true,
            'message' => 'Detail pasien berhasil diambil',
            'data' => new PasienResource($pasien),
        ]);
    }

    /**
     * Search patient specifically by No Rekam Medik for visit creation modal.
     */
    public function findByRekamMedik(string $no_rekam_medik): JsonResponse
    {
        $pasien = Pasien::where('no_rekam_medik', $no_rekam_medik)
            ->withCount('kunjungans as jml_kunjungan')
            ->first();

        if (! $pasien) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien dengan No Rekam Medik '.$no_rekam_medik.' tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pasien ditemukan',
            'data' => new PasienResource($pasien),
        ]);
    }
}
