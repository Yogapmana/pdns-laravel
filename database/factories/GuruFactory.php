<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guru>
 */
class GuruFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nama_guru' => fake()->name(),
        ];
    }

    public function withUser(): static
    {
        return $this->state(function (): array {
            $user = User::factory()->guru()->create();

            return ['user_id' => $user->id];
        });
    }
}
