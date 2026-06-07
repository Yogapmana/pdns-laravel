<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataPelajaran>
 */
class MataPelajaranFactory extends Factory
{
    protected $model = MataPelajaran::class;

    public function definition(): array
    {
        $list = [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA',
            'IPS', 'PKN', 'Fisika', 'Kimia', 'Biologi', 'Sejarah',
            'Geografi', 'Ekonomi', 'Sosiologi', 'Seni Budaya', 'PJOK',
        ];

        return [
            'nama' => fake()->unique()->randomElement($list),
        ];
    }
}
