<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Mendapatkan aturan validasi yang digunakan untuk memvalidasi password.
     *
     * @return array<int, ValidationRule|array<mixed>|string> Aturan validasi password.
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /**
     * Mendapatkan aturan validasi yang digunakan untuk memvalidasi password saat ini.
     *
     * @return array<int, ValidationRule|array<mixed>|string> Aturan validasi password saat ini.
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
