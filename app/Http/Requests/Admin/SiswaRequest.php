<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Form-request for creating and updating `Siswa` records.
 *
 * - Authorisation: admin only.
 * - Validates that `nis` is unique (ignoring the current row on update).
 * - Validates that `kelas` (if provided) references an existing row in `kelas`.
 * - `validated()` removes `nis` on PUT/PATCH so the field becomes immutable.
 */
class SiswaRequest extends FormRequest
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
     * Builds a unique-NIS rule that ignores the current siswa when the route
     * already carries a `siswa` parameter (i.e. on update).
     *
     * @return array<string, array<int, string|In>> Validation rules keyed by field name.
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Siswa::class, 'nis')->ignore($this->route('siswa')?->nis, 'nis'),
            ],
            'nama_siswa' => ['required', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', Rule::exists(Kelas::class, 'nama')],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
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
            'nis.unique' => 'NIS sudah terdaftar.',
            'nis.required' => 'NIS wajib diisi.',
            'nama_siswa.required' => 'Nama siswa wajib diisi.',
            'kelas.exists' => 'Kelas tidak valid. Pilih dari daftar kelas yang tersedia.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }

    /**
     * Return the validated payload, removing `nis` on PUT/PATCH (the field
     * is immutable) and dropping an empty `kelas` (so the column is
     * preserved unchanged on update when left blank).
     *
     * @param  string|array<int, string>|null  $key  Optional key(s) to pluck.
     * @param  mixed  $default  Default value when the key is missing.
     * @return array<string, mixed>|mixed The (possibly pruned) validated payload.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            unset($data['nis']);
        }

        if (empty($data['kelas'])) {
            unset($data['kelas']);
        }

        return $data;
    }
}
