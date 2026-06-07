<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition(): array
    {
        $tingkat = fake()->randomElement(['X', 'XI', 'XII']);
        $suffix = strtoupper((string) fake()->randomElement(['A', 'B', 'C', 'D']));

        return [
            'nama' => $tingkat.'-'.$suffix.'-'.Str::upper(Str::random(4)),
        ];
    }
}
