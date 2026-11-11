<?php

namespace App\Http\Requests\Pasien;

use App\Models\Pasien;
use App\Services\SecurityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePasienRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $security = app(SecurityService::class);

        $merged = [];

        // Support 'nama' as alias for 'nama_pasien'
        if ($this->has('nama') && ! $this->has('nama_pasien')) {
            $merged['nama_pasien'] = $this->input('nama');
        }

        // Clean & decrypt NIK if needed
        if ($this->filled('nik')) {
            $decryptedNik = $security->decrypt((string) $this->input('nik'));
            $merged['nik'] = trim($decryptedNik);
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    /**
     * Configure the validator instance with custom duplicate NIK check.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('nik')) {
                $hash = hash('sha256', trim((string) $this->input('nik')));
                $exists = Pasien::where('nik_hash', $hash)->exists();

                if ($exists) {
                    $validator->errors()->add('nik', 'NIK sudah terdaftar dalam sistem.');
                }
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'no_rekam_medik' => ['nullable', 'string', 'max:50', 'regex:/^RM[0-9]+$/i', 'unique:pasiens,no_rekam_medik'],
            'nama_pasien' => ['required', 'string', 'min:2', 'max:255'],
            'nik' => ['required', 'string', 'regex:/^[0-9]+$/', 'min:10', 'max:20'],
            'alamat' => ['required', 'string', 'min:3'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pasien.required' => 'Nama pasien wajib diisi.',
            'nama_pasien.min' => 'Nama pasien minimal 2 karakter.',
            'nama_pasien.max' => 'Nama pasien maksimal 255 karakter.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.regex' => 'NIK harus berupa angka.',
            'nik.min' => 'NIK minimal 10 digit angka.',
            'nik.max' => 'NIK maksimal 20 digit angka.',
            'no_rekam_medik.regex' => 'Format nomor rekam medik harus diawali RM diikuti angka (contoh: RM0001).',
            'no_rekam_medik.unique' => 'Nomor rekam medik sudah terdaftar.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.min' => 'Alamat minimal 3 karakter.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
