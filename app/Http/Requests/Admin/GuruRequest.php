<?php

namespace App\Http\Requests\Admin;

use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $guruId = $this->route('guru')?->id;
        $existingPairs = $guruId
            ? GuruMengajar::where('id_guru', $guruId)
                ->get(['kelas', 'mata_pelajaran'])
                ->map(fn ($m) => $m->kelas.'|'.$m->mata_pelajaran)
                ->all()
            : [];

        $rules = [
            'nama_guru' => ['required', 'string', 'max:255'],
            'mengajar' => ['required', 'array', 'min:1'],
        ];

        foreach (range(0, 50) as $i) {
            $input = $this->input("mengajar.$i");
            if ($input === null && ! $this->has("mengajar.$i")) {
                continue;
            }

            $kelasKey = "mengajar.$i.kelas";
            $mapelKey = "mengajar.$i.mata_pelajaran";

            $rules[$kelasKey] = ['required', 'string', Rule::exists(Kelas::class, 'nama')];
            $rules[$mapelKey] = ['required', 'string', Rule::exists(MataPelajaran::class, 'nama')];
        }

        $this->existingMengajarPairs = $existingPairs;

        return $rules;
    }

    private array $existingMengajarPairs = [];

    public function messages(): array
    {
        return [
            'nama_guru.required' => 'Nama guru wajib diisi.',
            'mengajar.required' => 'Minimal satu kombinasi kelas dan mata pelajaran harus diisi.',
            'mengajar.min' => 'Minimal satu kombinasi kelas dan mata pelajaran harus diisi.',
            'mengajar.*.kelas.exists' => 'Kelas tidak valid. Pilih dari daftar kelas yang tersedia.',
            'mengajar.*.mata_pelajaran.exists' => 'Mata pelajaran tidak valid. Pilih dari daftar mata pelajaran yang tersedia.',
            'mengajar.*.mata_pelajaran.required' => 'Mata pelajaran wajib dipilih.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $mengajar = $this->input('mengajar', []);
            $seen = [];

            foreach ($mengajar as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $kelas = trim((string) ($row['kelas'] ?? ''));
                $mapel = trim((string) ($row['mata_pelajaran'] ?? ''));

                if ($kelas === '' || $mapel === '') {
                    continue;
                }

                $pair = $kelas.'|'.$mapel;

                if (in_array($pair, $seen, true)) {
                    $v->errors()->add("mengajar.$i.mata_pelajaran", 'Kombinasi kelas & mata pelajaran duplikat.');
                } else {
                    $seen[] = $pair;
                }
            }
        });
    }

    public function prepareForValidation(): void
    {
        $mengajar = $this->input('mengajar', []);

        if (is_array($mengajar)) {
            $cleaned = [];
            foreach ($mengajar as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $cleaned[] = [
                    'kelas' => isset($row['kelas']) ? trim((string) $row['kelas']) : '',
                    'mata_pelajaran' => isset($row['mata_pelajaran']) ? trim((string) $row['mata_pelajaran']) : '',
                ];
            }
            $this->merge(['mengajar' => $cleaned]);
        }
    }

    /**
     * @return array<int, array{kelas: string, mata_pelajaran: string}>
     */
    public function getMengajar(): array
    {
        $out = [];
        foreach ($this->input('mengajar', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $kelas = trim((string) ($row['kelas'] ?? ''));
            $mapel = trim((string) ($row['mata_pelajaran'] ?? ''));

            if ($kelas === '' || $mapel === '') {
                continue;
            }
            $out[] = ['kelas' => $kelas, 'mata_pelajaran' => $mapel];
        }

        return $out;
    }
}
