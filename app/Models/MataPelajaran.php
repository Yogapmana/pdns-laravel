<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['nama'])]
class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'mata_pelajaran', 'nama');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'mata_pelajaran', 'nama');
    }

    public function getJumlahGuruMengajarAttribute(): int
    {
        return $this->guruMengajar()->count();
    }

    public function getJumlahNilaiAttribute(): int
    {
        return $this->nilai()->count();
    }

    /**
     * @return Collection<int, string>
     */
    public static function pluckNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * @param  Builder<MataPelajaran>  $query
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->when($term !== '', fn ($q) => $q->where('nama', 'like', "%{$term}%"));
    }
}
