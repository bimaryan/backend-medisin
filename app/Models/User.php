<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'email_hash', 'password'])]
#[Hidden(['password', 'remember_token', 'email_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Encrypt email before saving to database and compute SHA-256 blind index.
     */
    public function setEmailAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['email'] = $value;
            $this->attributes['email_hash'] = null;

            return;
        }

        if (! str_starts_with($value, 'eyJpdi')) {
            $this->attributes['email'] = Crypt::encryptString($value);
            $this->attributes['email_hash'] = hash('sha256', strtolower(trim($value)));
        } else {
            $this->attributes['email'] = $value;
        }
    }

    /**
     * Decrypt email when accessed from model.
     */
    public function getEmailAttribute(?string $value): ?string
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
