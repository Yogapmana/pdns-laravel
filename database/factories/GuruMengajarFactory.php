<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guru;
use App\Models\GuruMengajar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuruMengajar>
 */
class GuruMengajarFactory extends Factory
{
    public function definition(): array
    {
        $kelasList = ['X-A', 'X-B', 'XI-A', 'XI-B'];
        $mapelList = ['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Bahasa Inggris', 'PKN'];

        return [
            'id_guru' => Guru::factory(),
            'kelas' => fake()->randomElement($kelasList),
            'mata_pelajaran' => fake()->randomElement($mapelList),
        ];
    }
}
