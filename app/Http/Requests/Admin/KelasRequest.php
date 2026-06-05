<?php

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

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

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kelas wajib diisi.',
            'nama.unique' => 'Nama kelas sudah ada.',
            'nama.max' => 'Nama kelas maksimal 20 karakter.',
        ];
    }
}
