<?php

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $nis = $this->route('siswa')?->nis;

        return [
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Siswa::class, 'nis')->ignore($nis, 'nis'),
            ],
            'nama_siswa' => ['required', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', Rule::exists(Kelas::class, 'nama')],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS sudah terdaftar.',
            'nis.required' => 'NIS wajib diisi.',
            'nama_siswa.required' => 'Nama siswa wajib diisi.',
            'kelas.exists' => 'Kelas tidak valid. Pilih dari daftar kelas yang tersedia.',
        ];
    }

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
