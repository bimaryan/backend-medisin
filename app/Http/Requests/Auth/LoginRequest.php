<?php

namespace App\Http\Requests\Auth;

use App\Services\CaptchaService;
use App\Services\SecurityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation by decrypting any encrypted payload fields.
     */
    protected function prepareForValidation(): void
    {
        $security = app(SecurityService::class);

        $rawUsername = $this->input('username') ?? $this->input('user_name');
        $rawEmail = $this->input('email');
        $rawPassword = $this->input('password');
        $rawCaptcha = $this->input('captcha_code');

        $merged = [];

        if ($rawUsername !== null) {
            $decryptedUser = $security->decrypt((string) $rawUsername);
            $merged['username'] = $decryptedUser;
            $merged['user_name'] = $decryptedUser;
        }

        if ($rawEmail !== null) {
            $merged['email'] = $security->decrypt((string) $rawEmail);
        }

        if ($rawPassword !== null) {
            $merged['password'] = $security->decrypt((string) $rawPassword);
        }

        if ($rawCaptcha !== null) {
            $merged['captcha_code'] = $security->decrypt((string) $rawCaptcha);
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    /**
     * Configure the validator instance with the captcha verification rule.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Skip verification when the required captcha fields are already invalid
            if ($validator->errors()->has('captcha_code') || $validator->errors()->has('tokenCaptcha')) {
                return;
            }

            if (! app(CaptchaService::class)->verify(
                $this->input('tokenCaptcha'),
                $this->input('captcha_code'),
                $this->ip(),
            )) {
                $validator->errors()->add('captcha_code', 'Captcha tidak valid atau telah kedaluwarsa.');
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
            'username' => ['required_without:email', 'nullable', 'string'],
            'user_name' => ['nullable', 'string'],
            'email' => ['required_without:username', 'nullable', 'string'],
            'password' => ['required', 'string'],
            'captcha_code' => ['required', 'string'],
            'tokenCaptcha' => ['required', 'string'],
        ];
    }

    /**
     * Custom messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required_without' => 'Username atau email wajib diisi.',
            'email.required_without' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'captcha_code.required' => 'Captcha wajib diisi.',
            'tokenCaptcha.required' => 'Token captcha wajib diisi.',
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
