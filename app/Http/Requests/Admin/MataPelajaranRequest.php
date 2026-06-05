<?php

namespace App\Http\Requests\Admin;

use App\Models\MataPelajaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MataPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

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

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama mata pelajaran wajib diisi.',
            'nama.unique' => 'Nama mata pelajaran sudah ada.',
            'nama.max' => 'Nama mata pelajaran maksimal 100 karakter.',
        ];
    }
}
