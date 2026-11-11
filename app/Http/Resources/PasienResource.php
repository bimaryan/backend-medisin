<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasienResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_rekam_medik' => $this->no_rekam_medik,
            'nama_pasien' => $this->nama_pasien,
            'nik' => $this->nik,
            'alamat' => $this->alamat,
            'jml_kunjungan' => (int) ($this->jml_kunjungan ?? $this->kunjungans_count ?? $this->kunjungans()->count()),
            'kunjungans' => KunjunganResource::collection($this->whenLoaded('kunjungans')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
