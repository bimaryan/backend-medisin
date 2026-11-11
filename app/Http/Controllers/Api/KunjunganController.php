<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kunjungan\StoreKunjunganRequest;
use App\Http\Resources\KunjunganResource;
use App\Models\Kunjungan;
use App\Models\Pasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KunjunganController extends Controller
{
    /**
     * Display a listing of patient visits.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kunjungan::query()->with('pasien');

        if ($request->filled('pasien_id')) {
            $query->where('pasien_id', $request->input('pasien_id'));
        } elseif ($request->filled('no_rekam_medik')) {
            $pasienId = Pasien::where('no_rekam_medik', $request->input('no_rekam_medik'))->value('id');
            $query->where('pasien_id', $pasienId);
        }

        $kunjungans = $query->latest('tanggal_kunjungan')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kunjungan berhasil diambil',
            'data' => KunjunganResource::collection($kunjungans),
        ]);
    }

    /**
     * Store a newly created visit in storage.
     */
    public function store(StoreKunjunganRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $kunjungan = Kunjungan::create([
            'pasien_id' => $request->input('pasien_id'),
            'tanggal_kunjungan' => $request->input('tanggal_kunjungan', now()),
            'keluhan' => $request->input('keluhan'),
            'catatan' => $request->input('catatan'),
        ]);

        $kunjungan->load('pasien');

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan berhasil dibuat',
            'data' => new KunjunganResource($kunjungan),
        ], 201);
    }

    /**
     * Display the specified visit.
     */
    public function show(Kunjungan $kunjungan): JsonResponse
    {
        $kunjungan->load('pasien');

        return response()->json([
            'success' => true,
            'message' => 'Detail kunjungan berhasil diambil',
            'data' => new KunjunganResource($kunjungan),
        ]);
    }
}
