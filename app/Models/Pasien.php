<?php

namespace App\Models;

use Database\Factories\PasienFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['no_rekam_medik', 'nama_pasien', 'nik', 'nik_hash', 'alamat'])]
#[Hidden(['nik_hash'])]
class Pasien extends Model
{
    /** @use HasFactory<PasienFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pasiens';

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Pasien $pasien): void {
            if (empty($pasien->no_rekam_medik)) {
                $pasien->no_rekam_medik = static::generateNextNoRekamMedik();
            }
        });
    }

    /**
     * Encrypt NIK before saving to database and compute SHA-256 blind index.
     */
    public function setNikAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['nik'] = $value;
            $this->attributes['nik_hash'] = null;

            return;
        }

        // If not already encrypted, encrypt it
        if (! str_starts_with($value, 'eyJpdi')) {
            $this->attributes['nik'] = Crypt::encryptString($value);
            $this->attributes['nik_hash'] = hash('sha256', trim($value));
        } else {
            $this->attributes['nik'] = $value;
        }
    }

    /**
     * Decrypt NIK when accessed from model.
     */
    public function getNikAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Generate sequential No Rekam Medik (e.g. RM0001, RM0002).
     */
    public static function generateNextNoRekamMedik(): string
    {
        $allRms = static::query()
            ->pluck('no_rekam_medik')
            ->filter(fn ($rm) => is_string($rm) && preg_match('/^RM(\d+)$/i', $rm))
            ->map(fn ($rm) => (int) preg_replace('/^RM/i', '', $rm));

        $maxNumber = $allRms->max() ?? 0;

        return sprintf('RM%04d', $maxNumber + 1);
    }

    /**
     * Get the kunjungan records for the pasien.
     */
    public function kunjungans(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'pasien_id');
    }
}
