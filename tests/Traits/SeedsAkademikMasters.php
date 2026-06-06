<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\Kelas;
use App\Models\KelasMataPelajaran;
use App\Models\MataPelajaran;

trait SeedsAkademikMasters
{
    /**
     * Seed default master Kelas records. Idempotent.
     *
     * @param  array<int, string>|null  $only  If null, seeds full default SMA set; else only specified names.
     * @return array<int, string>
     */
    protected function seedKelas(?array $only = null): array
    {
        $defaults = ['X-A', 'X-B', 'XI-A', 'XI-B', 'XII-A', 'XII-B'];
        $names = $only ?? $defaults;

        foreach ($names as $nama) {
            Kelas::firstOrCreate(['nama' => $nama]);
        }

        return $names;
    }

    /**
     * Seed default master MataPelajaran records. Idempotent.
     *
     * @param  array<int, string>|null  $only  If null, seeds full default SMA set; else only specified names.
     * @return array<int, string>
     */
    protected function seedMataPelajaran(?array $only = null): array
    {
        $defaults = [
            'Matematika',
            'Bahasa Indonesia',
            'IPA',
            'IPS',
            'Bahasa Inggris',
            'PKN',
            'Penjaskes',
            'Seni Budaya',
            'Sejarah',
            'Bahasa Jawa',
        ];
        $names = $only ?? $defaults;

        foreach ($names as $nama) {
            MataPelajaran::firstOrCreate(['nama' => $nama]);
        }

        return $names;
    }

    /**
     * Seed the `kelas_mata_pelajaran` pivot so every default kelas
     * allows every default mapel. This mirrors the production "admin
     * has configured all combinations" state and lets the guru form
     * validation pass for the default test fixtures.
     *
     * Idempotent: subsequent calls are no-ops once the rows exist.
     *
     * @param  array<int, string>|null  $kelas  If null, uses the default kelas set.
     * @param  array<int, string>|null  $mapel  If null, uses the default mapel set.
     * @return int The number of (kelas, mapel) pairs now present in the pivot.
     */
    protected function seedKelasMataPelajaran(?array $kelas = null, ?array $mapel = null): int
    {
        $kelas = $kelas ?? $this->seedKelas();
        $mapel = $mapel ?? $this->seedMataPelajaran();

        $count = 0;
        foreach ($kelas as $k) {
            foreach ($mapel as $m) {
                $row = KelasMataPelajaran::firstOrCreate(
                    ['kelas' => $k, 'mata_pelajaran' => $m],
                );
                if ($row->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
