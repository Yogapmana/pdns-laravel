<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Eloquent model representing a student (`siswa`).
 *
 * Backed by the `siswa` table. The primary key is the string `nis`
 * (Nomor Induk Siswa) — this is also the route-model binding key.
 *
 * @property string $nis
 * @property int|null $user_id
 * @property string $nama_siswa
 * @property int|null $kelas_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nis', 'user_id', 'nama_siswa', 'kelas_id'])]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $table = 'siswa';

    protected $primaryKey = 'nis';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Use `nis` for route-model binding (see the `Route::resource('siswa', ...)` definitions).
     *
     * @return string The route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'nis';
    }

    /**
     * The login account associated with this siswa (1:1, optional).
     *
     * @return BelongsTo<User, Siswa>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The `Kelas` this siswa belongs to.
     *
     * @return BelongsTo<Kelas, Siswa>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * The `Nilai` rows belonging to this siswa.
     *
     * @return HasMany<Nilai>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'nis', 'nis');
    }

    /**
     * Convenience accessor: the display name of the siswa's kelas, or
     * `null` when the siswa is not assigned to a kelas. Useful in views
     * that previously relied on the now-removed string `kelas` column.
     */
    protected function getKelasNamaAttribute(): ?string
    {
        return $this->kelas?->nama;
    }
}
