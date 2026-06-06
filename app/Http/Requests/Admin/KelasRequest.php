<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use App\Models\MataPelajaran;
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
            'mata_pelajaran' => ['nullable', 'array'],
            'mata_pelajaran.*' => ['string', Rule::exists(MataPelajaran::class, 'nama')],
        ];
    }

    /**
     * Return the validated payload, removing an empty `mata_pelajaran`
     * array so the controller can distinguish "no change" (key absent)
     * from "explicitly cleared" (empty array).
     *
     * @param  string|array<int, string>|null  $key  Optional key(s) to pluck.
     * @param  mixed  $default  Default value when the key is missing.
     * @return array<string, mixed>|mixed The (possibly pruned) validated payload.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if (! $this->has('mata_pelajaran')) {
            unset($data['mata_pelajaran']);
        }

        return $data;
    }

    /**
     * Return the deduplicated, sorted list of mata-pelajaran names that
     * were submitted for this kelas. Used by the controller to sync the
     * `kelas_mata_pelajaran` pivot.
     *
     * @return array<int, string>
     */
    public function getMataPelajaran(): array
    {
        $raw = $this->input('mata_pelajaran', []);

        if (! is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $nama) {
            $nama = trim((string) $nama);
            if ($nama === '' || in_array($nama, $clean, true)) {
                continue;
            }
            $clean[] = $nama;
        }

        sort($clean);

        return $clean;
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
            'mata_pelajaran.array' => 'Daftar mata pelajaran harus berupa array.',
            'mata_pelajaran.*.exists' => 'Mata pelajaran tidak valid. Pilih dari daftar mata pelajaran yang tersedia.',
            'mata_pelajaran.*.string' => 'Nama mata pelajaran harus berupa teks.',
        ];
    }
}
