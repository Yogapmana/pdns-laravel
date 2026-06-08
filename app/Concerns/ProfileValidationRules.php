<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Mendapatkan aturan validasi yang digunakan untuk memvalidasi profil pengguna.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>> Aturan validasi profil.
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Mendapatkan aturan validasi yang digunakan untuk memvalidasi nama pengguna.
     *
     * @return array<int, ValidationRule|array<mixed>|string> Aturan validasi nama.
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Mendapatkan aturan validasi yang digunakan untuk memvalidasi email pengguna.
     *
     * @return array<int, ValidationRule|array<mixed>|string> Aturan validasi email.
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
