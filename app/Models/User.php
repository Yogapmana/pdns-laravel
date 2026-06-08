<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Model Eloquent yang merepresentasikan pengguna terautentikasi.
 *
 * Didukung oleh tabel `users`. Kolom `role` membedakan antara
 * akun admin / guru / siswa dan digunakan untuk otorisasi
 * (melalui middleware `role`) dan pengalihan rute dashboard.
 *
 * @property int $id
 * @property string $username
 * @property string $name
 * @property string $role
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['username', 'name', 'role', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_GURU = 'guru';

    public const ROLE_SISWA = 'siswa';

    /**
     * Cast atribut yang diterapkan pada kolom tabel database.
     *
     * @return array<string, string> Definisi cast atribut.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi profil 1:1 untuk pengguna dengan role siswa.
     *
     * @return HasOne<Siswa> Relasi ke model Siswa.
     */
    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class);
    }

    /**
     * Relasi profil 1:1 untuk pengguna dengan role guru.
     *
     * @return HasOne<Guru> Relasi ke model Guru.
     */
    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }

    /**
     * Memeriksa apakah pengguna memiliki salah satu dari role yang diberikan.
     *
     * @param  string  ...$roles  Daftar role yang akan diuji.
     * @return bool `true` jika role pengguna ada dalam `$roles`.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Memeriksa apakah pengguna memiliki role admin.
     *
     * @return bool `true` jika `role === ROLE_ADMIN`.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Memeriksa apakah pengguna memiliki role guru.
     *
     * @return bool `true` jika `role === ROLE_GURU`.
     */
    public function isGuru(): bool
    {
        return $this->role === self::ROLE_GURU;
    }

    /**
     * Memeriksa apakah pengguna memiliki role siswa.
     *
     * @return bool `true` jika `role === ROLE_SISWA`.
     */
    public function isSiswa(): bool
    {
        return $this->role === self::ROLE_SISWA;
    }

    /**
     * Mendapatkan nama rute dashboard untuk pengguna berdasarkan role mereka.
     *
     * @return string Nama rute dashboard khusus role, atau `login` jika role tidak dikenal.
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_GURU => 'guru.dashboard',
            self::ROLE_SISWA => 'siswa.dashboard',
            default => 'login',
        };
    }
}
