<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Form-request for creating and updating `Guru` records together with
 * their associated `GuruMengajar` rows.
 *
 * - Authorisation: admin only.
 * - Validates that each mengajar pair references existing rows in `kelas`
 *   and `mata_pelajaran`.
 * - `withValidator()` rejects duplicate `(kelas, mata_pelajaran)` pairs in
 *   the submission.
 * - `prepareForValidation()` trims whitespace from each pair.
 * - `getMengajar()` exposes the cleaned-up pairs to the controller.
 */
class GuruRequest extends FormRequest
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
     * Validation rules for the guru + mengajar form.
     *
     * For each submitted `mengajar.<i>` row, the method enforces that
     * `kelas` and `mata_pelajaran` are required strings that exist in
     * their respective master tables.
     *
     * @return array<string, array<int, string|In>> Validation rules keyed by field name.
     */
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

    /**
     * Indonesian-language error messages for the validation rules.
     *
     * @return array<string, string> Custom validation messages keyed by `field.rule`.
     */
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

    /**
     * Add an `after` validation hook that rejects duplicate
     * `(kelas, mata_pelajaran)` pairs in the submitted `mengajar` array.
     *
     * @param  Validator  $validator  The current validator instance.
     */
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

    /**
     * Trim whitespace from each `mengajar.*.kelas` and `mengajar.*.mata_pelajaran`
     * entry before validation runs.
     */
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
     * Return the cleaned-up mengajar pairs as a 2-D array.
     *
     * Each pair is `['kelas' => string, 'mata_pelajaran' => string]`. Pairs
     * with an empty `kelas` or `mata_pelajaran` are skipped (the rules
     * already enforce non-empty values, so this is a defensive fallback).
     *
     * @return array<int, array{kelas: string, mata_pelajaran: string}> List of mengajar pairs.
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
