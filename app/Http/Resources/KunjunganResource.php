<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KunjunganResource extends JsonResource
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
            'pasien_id' => $this->pasien_id,
            'tanggal_kunjungan' => $this->tanggal_kunjungan?->toISOString() ?? $this->created_at?->toISOString(),
            'keluhan' => $this->keluhan,
            'catatan' => $this->catatan,
            'pasien' => new PasienResource($this->whenLoaded('pasien')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
