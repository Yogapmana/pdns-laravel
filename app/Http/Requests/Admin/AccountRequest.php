<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\RequiredIf;

/**
 * Form-request for the admin "create account" page.
 *
 * Validates a new user account payload: a unique `username`, a display
 * `name`, one of the supported roles, and a password. The `nis` and
 * `guru_id` fields are conditionally required based on the chosen role
 * (they link the new account to an existing siswa or guru profile).
 */
class AccountRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user is allowed to perform this request.
     *
     * @return bool `true` when the user is an admin, `false` otherwise.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Validation rules for the account-creation form.
     *
     * @return array<string, array<int, string|In|Password|RequiredIf>> Validation rules keyed by field name.
     */
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
