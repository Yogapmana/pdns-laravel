<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MataPelajaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Form-request for creating and updating `MataPelajaran` records.
 *
 * - Authorisation: admin only.
 * - Validates that `nama` is a unique string of at most 100 characters,
 *   ignoring the current row on update.
 */
class MataPelajaranRequest extends FormRequest
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
        $mapelId = $this->route('mata_pelajaran')?->id;

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique(MataPelajaran::class, 'nama')->ignore($mapelId),
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
            'nama.required' => 'Nama mata pelajaran wajib diisi.',
            'nama.unique' => 'Nama mata pelajaran sudah ada.',
            'nama.max' => 'Nama mata pelajaran maksimal 100 karakter.',
        ];
    }
}
