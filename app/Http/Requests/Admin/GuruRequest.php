<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\KelasMataPelajaran;
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
 *   and `mata_pelajaran` via their FK ids.
 * - `withValidator()` rejects duplicate `(kelas_id, mata_pelajaran_id)` pairs
 *   in the submission and checks the pair is allowed by the
 *   `kelas_mata_pelajaran` pivot.
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
     * `kelas_id` and `mata_pelajaran_id` are required integers that exist
     * in their respective master tables.
     *
     * @return array<string, array<int, string|In>> Validation rules keyed by field name.
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'nama_guru' => ['required', 'string', 'max:255'],
            'mengajar' => ['required', 'array', 'min:1'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ];

        foreach ($this->input('mengajar', []) as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $kelasKey = "mengajar.$i.kelas_id";
            $mapelKey = "mengajar.$i.mata_pelajaran_id";

            $rules[$kelasKey] = ['required', 'integer', Rule::exists(Kelas::class, 'id')];
            $rules[$mapelKey] = ['required', 'integer', Rule::exists(MataPelajaran::class, 'id')];
        }

        return $rules;
    }

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
            'mengajar.*.kelas_id.exists' => 'Kelas tidak valid. Pilih dari daftar kelas yang tersedia.',
            'mengajar.*.mata_pelajaran_id.exists' => 'Mata pelajaran tidak valid. Pilih dari daftar mata pelajaran yang tersedia.',
            'mengajar.*.mata_pelajaran_id.required' => 'Mata pelajaran wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }

    /**
     * Add an `after` validation hook that rejects duplicate
     * `(kelas_id, mata_pelajaran_id)` pairs in the submitted `mengajar` array
     * and ensures the pair is allowed by the `kelas_mata_pelajaran` master.
     *
     * @param  Validator  $validator  The current validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $mengajar = $this->input('mengajar', []);
            $seen = [];
            $allowedByKelas = $this->loadAllowedMapelByKelasId($mengajar);

            foreach ($mengajar as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $kelasId = (int) ($row['kelas_id'] ?? 0);
                $mapelId = (int) ($row['mata_pelajaran_id'] ?? 0);

                if ($kelasId <= 0 || $mapelId <= 0) {
                    continue;
                }

                $pair = $kelasId.'|'.$mapelId;

                if (in_array($pair, $seen, true)) {
                    $v->errors()->add("mengajar.$i.mata_pelajaran_id", 'Kombinasi kelas & mata pelajaran duplikat.');
                } else {
                    $seen[] = $pair;
                }

                $allowedForKelas = $allowedByKelas[$kelasId] ?? null;
                $mapelName = MataPelajaran::where('id', $mapelId)->value('nama');
                $kelasName = Kelas::where('id', $kelasId)->value('nama');

                if ($allowedForKelas === null) {
                    $v->errors()->add(
                        "mengajar.$i.kelas_id",
                        "Kelas \"{$kelasName}\" belum punya mata pelajaran yang diizinkan. Atur dulu di Manajemen Kelas."
                    );
                } elseif (! in_array($mapelId, $allowedForKelas, true)) {
                    $v->errors()->add(
                        "mengajar.$i.mata_pelajaran_id",
                        "Mata pelajaran \"{$mapelName}\" tidak diizinkan untuk kelas \"{$kelasName}\". Atur dulu di Manajemen Kelas."
                    );
                }
            }
        });
    }

    /**
     * Build a `[kelas_id => [mapel_id, ...]]` map of the allowed mata-pelajaran
     * for every kelas referenced by the submitted `mengajar` rows.
     *
     * @param  mixed  $mengajar  The raw input from `$this->input('mengajar', [])`.
     * @return array<int, array<int, int>>
     */
    private function loadAllowedMapelByKelasId(mixed $mengajar): array
    {
        if (! is_array($mengajar)) {
            return [];
        }

        $kelasIds = [];
        foreach ($mengajar as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['kelas_id'] ?? 0);
            if ($id > 0) {
                $kelasIds[$id] = true;
            }
        }

        if ($kelasIds === []) {
            return [];
        }

        $rows = KelasMataPelajaran::whereIn('kelas_id', array_keys($kelasIds))->get(['kelas_id', 'mata_pelajaran_id']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->kelas_id][] = (int) $r->mata_pelajaran_id;
        }

        return $map;
    }

    /**
     * Return the cleaned-up mengajar pairs as a 2-D array.
     *
     * Each pair is `['kelas_id' => int, 'mata_pelajaran_id' => int]`. Pairs
     * with non-positive ids are skipped (the rules already enforce
     * positive values, so this is a defensive fallback).
     *
     * @return array<int, array{kelas_id: int, mata_pelajaran_id: int}> List of mengajar pairs.
     */
    public function getMengajar(): array
    {
        $out = [];
        foreach ($this->input('mengajar', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $kelasId = (int) ($row['kelas_id'] ?? 0);
            $mapelId = (int) ($row['mata_pelajaran_id'] ?? 0);

            if ($kelasId <= 0 || $mapelId <= 0) {
                continue;
            }
            $out[] = ['kelas_id' => $kelasId, 'mata_pelajaran_id' => $mapelId];
        }

        return $out;
    }
}
