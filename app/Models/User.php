<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Eloquent model representing an authenticated user.
 *
 * Backed by the `users` table. The `role` column discriminates between
 * admin / guru / siswa accounts and is used for both authorisation
 * (via the `role` middleware) and dashboard routing.
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
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_GURU = 'guru';

    public const ROLE_SISWA = 'siswa';

    /**
     * Attribute casts applied to the underlying table columns.
     *
     * @return array<string, string> The cast definitions.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The 1:1 profile relation for siswa-role users.
     *
     * @return HasOne<Siswa>
     */
    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class);
    }

    /**
     * The 1:1 profile relation for guru-role users.
     *
     * @return HasOne<Guru>
     */
    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }

    /**
     * The 1:N collection of in-app notification rows owned by this user.
     *
     * Surfaced by the header bell; ordered newest-first in the UI but
     * un-ordered here so callers can apply their own sort/paginate.
     *
     * @return HasMany<Notification>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Determine whether the user has at least one of the supplied roles.
     *
     * @param  string  ...$roles  The roles to test against.
     * @return bool `true` when the user's `role` is in `$roles`.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Determine whether the user has the admin role.
     *
     * @return bool `true` when `role === ROLE_ADMIN`.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Determine whether the user has the guru role.
     *
     * @return bool `true` when `role === ROLE_GURU`.
     */
    public function isGuru(): bool
    {
        return $this->role === self::ROLE_GURU;
    }

    /**
     * Determine whether the user has the siswa role.
     *
     * @return bool `true` when `role === ROLE_SISWA`.
     */
    public function isSiswa(): bool
    {
        return $this->role === self::ROLE_SISWA;
    }

    /**
     * Resolve the dashboard route name for this user, based on their role.
     *
     * @return string The named route for the role-specific dashboard, or `login` for any unknown role.
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
