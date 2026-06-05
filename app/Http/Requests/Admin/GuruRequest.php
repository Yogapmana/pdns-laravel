<?php

namespace App\Http\Requests\Admin;

use App\Models\GuruMengajar;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

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
            $mapelBaruKey = "mengajar.$i.mata_pelajaran_baru";

            $rules[$kelasKey] = ['required', 'string', 'max:20'];
            $rules[$mapelKey] = ['nullable', 'string', 'max:100'];
            $rules[$mapelBaruKey] = ['nullable', 'string', 'max:100'];
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
                $mapelBaru = trim((string) ($row['mata_pelajaran_baru'] ?? ''));

                if ($kelas === '') {
                    $v->errors()->add("mengajar.$i.kelas", 'Kelas wajib diisi.');

                    continue;
                }

                $finalMapel = $mapel !== '' ? $mapel : $mapelBaru;

                if ($finalMapel === '') {
                    $v->errors()->add("mengajar.$i.mata_pelajaran", 'Pilih atau isi mata pelajaran.');

                    continue;
                }

                $pair = $kelas.'|'.$finalMapel;

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
                    'mata_pelajaran_baru' => isset($row['mata_pelajaran_baru']) ? trim((string) $row['mata_pelajaran_baru']) : '',
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
            $mapelBaru = trim((string) ($row['mata_pelajaran_baru'] ?? ''));

            if ($kelas === '') {
                continue;
            }
            $final = $mapel !== '' ? $mapel : $mapelBaru;
            if ($final === '') {
                continue;
            }
            $out[] = ['kelas' => $kelas, 'mata_pelajaran' => $final];
        }

        return $out;
    }
}
