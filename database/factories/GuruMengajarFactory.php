<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuruMengajar>
 */
class GuruMengajarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_guru' => Guru::factory(),
            'kelas_id' => Kelas::factory(),
            'mata_pelajaran_id' => MataPelajaran::factory(),
        ];
    }
}
