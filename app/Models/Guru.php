<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model representing a teacher (`guru`).
 *
 * Backed by the `guru` table. A guru has many `GuruMengajar` rows that
 * describe the (kelas, mata_pelajaran) combinations they are allowed
 * to teach. The accessors and helpers below are used by the guru-facing
 * controllers to filter and authorise nilai input.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $nama_guru
 * @property-read array<int, string> $all_kelas
 * @property-read array<int, string> $all_mapel
 */
#[Fillable(['user_id', 'nama_guru'])]
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory;

    protected $table = 'guru';

    /**
     * The login account associated with this guru (1:1, optional).
     *
     * @return BelongsTo<User, Guru>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The mengajar combinations owned by this guru.
     *
     * @return HasMany<GuruMengajar>
     */
    public function mengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'id_guru');
    }

    /**
     * The `Nilai` rows input by this guru.
     *
     * @return HasMany<Nilai>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'id_guru');
    }

    /**
     * Accessor: distinct list of kelas this guru teaches, sorted ascending.
     *
     * @return array<int, string>
     */
    public function getAllKelasAttribute(): array
    {
        return $this->mengajar()->distinct()->orderBy('kelas')->pluck('kelas')->all();
    }

    /**
     * Accessor: distinct list of mata pelajaran this guru teaches, sorted ascending.
     *
     * @return array<int, string>
     */
    public function getAllMapelAttribute(): array
    {
        return $this->mengajar()->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran')->all();
    }

    /**
     * Return the list of mata pelajaran this guru teaches for the given kelas.
     *
     * @param  string  $kelas  The kelas name (e.g. "X-A").
     * @return array<int, string> Sorted list of mata pelajaran taught in that kelas.
     */
    public function getMapelByKelas(string $kelas): array
    {
        return $this->mengajar()
            ->where('kelas', $kelas)
            ->orderBy('mata_pelajaran')
            ->pluck('mata_pelajaran')
            ->all();
    }

    /**
     * Determine whether this guru teaches the supplied (kelas, mata_pelajaran) pair.
     *
     * @param  string  $kelas  The kelas name.
     * @param  string  $mataPelajaran  The mata pelajaran name.
     * @return bool `true` when a `GuruMengajar` row exists for this combination.
     */
    public function mengajarDiKelasMapel(string $kelas, string $mataPelajaran): bool
    {
        return $this->mengajar()
            ->where('kelas', $kelas)
            ->where('mata_pelajaran', $mataPelajaran)
            ->exists();
    }
}
