<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nis' => str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'user_id' => null,
            'nama_siswa' => fake()->name(),
            'kelas_id' => Kelas::factory(),
        ];
    }

    public function withUser(): static
    {
        return $this->state(function (): array {
            $user = User::factory()->siswa()->create();

            return ['user_id' => $user->id];
        });
    }
}
