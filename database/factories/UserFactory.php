<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'role' => User::ROLE_SISWA,
            'is_active' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => User::ROLE_ADMIN]);
    }

    public function guru(): static
    {
        return $this->state(fn (): array => ['role' => User::ROLE_GURU]);
    }

    public function siswa(): static
    {
        return $this->state(fn (): array => ['role' => User::ROLE_SISWA]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
