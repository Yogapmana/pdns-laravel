<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Eloquent model representing a subject (`mata_pelajaran`).
 *
 * Backed by the `mata_pelajaran` table. The subject is identified by
 * its `nama` and is referenced by guru-mengajar and nilai rows through
 * that string value (non-FK relationships).
 *
 * @property int $id
 * @property string $nama
 * @property-read int $jumlah_guru_mengajar
 * @property-read int $jumlah_nilai
 */
#[Fillable(['nama'])]
class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    /**
     * The guru-mengajar combinations that target this mata pelajaran.
     *
     * @return HasMany<GuruMengajar>
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'mata_pelajaran', 'nama');
    }

    /**
     * The nilai rows whose `mata_pelajaran` matches this row's `nama`.
     *
     * @return HasMany<Nilai>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'mata_pelajaran', 'nama');
    }

    /**
     * Accessor: the count of guru-mengajar rows that target this subject.
     *
     * @return int The number of guru-mengajar rows.
     */
    public function getJumlahGuruMengajarAttribute(): int
    {
        return $this->guruMengajar()->count();
    }

    /**
     * Accessor: the count of nilai rows for this subject.
     *
     * @return int The number of nilai rows.
     */
    public function getJumlahNilaiAttribute(): int
    {
        return $this->nilai()->count();
    }

    /**
     * Static helper that returns all `nama` values, ordered ascending.
     *
     * Used by forms and dropdowns that need the canonical list of subjects.
     *
     * @return Collection<int, string> Collection of `nama` strings.
     */
    public static function pluckNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * Query scope: filter the builder by a `nama` LIKE search.
     *
     * Empty search terms leave the query untouched.
     *
     * @param  Builder<MataPelajaran>  $query  The active query builder.
     * @param  string  $term  The search term (already trimmed by the caller).
     * @return Builder<MataPelajaran> The (possibly filtered) query builder.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->when($term !== '', fn ($q) => $q->where('nama', 'like', "%{$term}%"));
    }
}
