<?php

namespace Tests\Traits;

use App\Models\Kelas;
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
}
