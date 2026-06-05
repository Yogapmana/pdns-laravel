<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $role = $this->input('role');

        return [
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class, 'username'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_GURU, User::ROLE_SISWA])],
            'password' => ['required', 'string', Password::min(6)],
            'nis' => [
                Rule::requiredIf(fn () => $role === User::ROLE_SISWA),
                'nullable',
                'string',
                'exists:siswa,nis',
            ],
            'guru_id' => [
                Rule::requiredIf(fn () => $role === User::ROLE_GURU),
                'nullable',
                'integer',
                'exists:guru,id',
            ],
        ];
    }
}
