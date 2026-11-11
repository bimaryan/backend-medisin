<?php

namespace App\Http\Requests\Kunjungan;

use App\Models\Pasien;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreKunjunganRequest extends FormRequest
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
        // If no_rekam_medik is provided without pasien_id, resolve pasien_id
        if (! $this->has('pasien_id') && $this->filled('no_rekam_medik')) {
            $pasien = Pasien::where('no_rekam_medik', $this->input('no_rekam_medik'))->first();
            if ($pasien) {
                $this->merge([
                    'pasien_id' => $pasien->id,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pasien_id' => ['required_without:no_rekam_medik', 'integer', 'exists:pasiens,id'],
            'no_rekam_medik' => ['required_without:pasien_id', 'string', 'exists:pasiens,no_rekam_medik'],
            'tanggal_kunjungan' => ['nullable', 'date'],
            'keluhan' => ['nullable', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
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
            'pasien_id.required_without' => 'Nomor rekam medik atau ID pasien wajib diisi.',
            'pasien_id.exists' => 'Pasien tidak ditemukan.',
            'no_rekam_medik.required_without' => 'Nomor rekam medik atau ID pasien wajib diisi.',
            'no_rekam_medik.exists' => 'Pasien dengan nomor rekam medik tersebut tidak ditemukan.',
            'tanggal_kunjungan.date' => 'Format tanggal kunjungan tidak valid.',
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
