<?php

namespace App\Http\Requests\Admin;

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
            'kelas' => ['nullable', 'string', 'max:20'],
            'kelas_baru' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS sudah terdaftar.',
            'nis.required' => 'NIS wajib diisi.',
            'nama_siswa.required' => 'Nama siswa wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->kelas) && empty($this->kelas_baru)) {
                $v->errors()->add('kelas', 'Pilih kelas dari daftar atau isi kelas baru.');
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            unset($data['nis']);
        }

        return $data;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'kelas' => $this->input('kelas') ?: null,
            'kelas_baru' => $this->input('kelas_baru') ?: null,
        ]);
    }
}
