<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Eloquent model representing a class group (`kelas`).
 *
 * Backed by the `kelas` table. The class is identified by its `nama`
 * (e.g. `X-A`, `XI-B`) and is referenced by siswa, guru-mengajar and
 * nilai rows through that string value (non-FK relationships).
 *
 * @property int $id
 * @property string $nama
 * @property-read int $jumlah_siswa
 * @property-read int $jumlah_guru_mengajar
 */
#[Fillable(['nama'])]
class Kelas extends Model
{
    protected $table = 'kelas';

    /**
     * The siswa that belong to this kelas (matched on `kelas.nama == siswa.kelas`).
     *
     * @return HasMany<Siswa>
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas', 'nama');
    }

    /**
     * The guru-mengajar combinations that target this kelas.
     *
     * @return HasMany<GuruMengajar>
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'kelas', 'nama');
    }

    /**
     * The nilai rows whose `kelas` matches this row's `nama`.
     *
     * @return HasMany<Nilai>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'kelas', 'nama');
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
