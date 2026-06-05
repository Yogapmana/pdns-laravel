<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Form-request for creating and updating `Kelas` records.
 *
 * - Authorisation: admin only.
 * - Validates that `nama` is a unique string of at most 20 characters,
 *   ignoring the current row on update.
 */
class KelasRequest extends FormRequest
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
     * Validation rules for the create/update form.
     *
     * @return array<string, array<int, string|In>> Validation rules keyed by field name.
     */
    public function rules(): array
    {
        $kelasId = $this->route('kela')?->id;

        return [
            'nama' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Kelas::class, 'nama')->ignore($kelasId),
            ],
        ];
    }

    /**
     * Indonesian-language error messages for the validation rules.
     *
     * @return array<string, string> Custom validation messages keyed by `field.rule`.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kelas wajib diisi.',
            'nama.unique' => 'Nama kelas sudah ada.',
            'nama.max' => 'Nama kelas maksimal 20 karakter.',
        ];
    }
}
