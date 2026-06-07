<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Eloquent model representing a class group (`kelas`).
 *
 * Backed by the `kelas` table. The class is identified by its `nama`
 * (e.g. `X-A`, `XI-B`) and is referenced by siswa, guru-mengajar and
 * nilai rows through the `kelas_id` foreign key.
 *
 * @property int $id
 * @property string $nama
 * @property-read int $jumlah_siswa
 * @property-read int $jumlah_guru_mengajar
 * @property-read int $jumlah_mapel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MataPelajaran> $mataPelajaran
 */
#[Fillable(['nama'])]
class Kelas extends Model
{
    protected $table = 'kelas';

    /**
     * The siswa that belong to this kelas.
     *
     * @return HasMany<Siswa>
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    /**
     * The guru-mengajar combinations that target this kelas.
     *
     * @return HasMany<GuruMengajar>
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'kelas_id');
    }

    /**
     * The nilai rows whose `kelas_id` matches this row.
     *
     * @return HasMany<Nilai>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'kelas_id');
    }

    /**
     * The mata-pelajaran rows that this kelas allows, joined through the
     * `kelas_mata_pelajaran` pivot using the `(kelas_id, mata_pelajaran_id)`
     * composite key.
     *
     * @return BelongsToMany<MataPelajaran>
     */
    public function mataPelajaran(): BelongsToMany
    {
        return $this->belongsToMany(
            MataPelajaran::class,
            'kelas_mata_pelajaran',
            'kelas_id',
            'mata_pelajaran_id',
        )->withTimestamps();
    }

    /**
     * Accessor: the count of siswa currently in this kelas.
     *
     * @return int The number of siswa rows.
     */
    public function getJumlahSiswaAttribute(): int
    {
        return $this->siswa()->count();
    }

    /**
     * Accessor: the count of guru-mengajar rows that target this kelas.
     *
     * @return int The number of guru-mengajar rows.
     */
    public function getJumlahGuruMengajarAttribute(): int
    {
        return $this->guruMengajar()->count();
    }

    /**
     * Accessor: the count of mata-pelajaran rows that this kelas allows.
     *
     * @return int The number of `kelas_mata_pelajaran` rows for this kelas.
     */
    public function getJumlahMapelAttribute(): int
    {
        return $this->mataPelajaran()->count();
    }

    /**
     * Static helper that returns all `nama` values, ordered ascending.
     *
     * Used by forms and dropdowns that need the canonical list of kelas.
     *
     * @return Collection<int, string> Collection of `nama` strings.
     */
    public static function pluckNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * Static helper that returns `id` and `nama` for every kelas, ordered ascending by `nama`.
     *
     * Used by FK-aware forms that need to submit the kelas id but display
     * the name in the option label.
     *
     * @return Collection<int, static> Collection of Kelas models with only `id` and `nama` selected.
     */
    public static function pluckIdNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->get(['id', 'nama']);
    }

    /**
     * Query scope: filter the builder by a `nama` LIKE search.
     *
     * Empty search terms leave the query untouched.
     *
     * @param  Builder<Kelas>  $query  The active query builder.
     * @param  string  $term  The search term (already trimmed by the caller).
     * @return Builder<Kelas> The (possibly filtered) query builder.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->when($term !== '', fn ($q) => $q->where('nama', 'like', "%{$term}%"));
    }
}
