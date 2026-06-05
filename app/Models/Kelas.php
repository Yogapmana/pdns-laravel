<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['nama'])]
class Kelas extends Model
{
    protected $table = 'kelas';

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas', 'nama');
    }

    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'kelas', 'nama');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'kelas', 'nama');
    }

    public function getJumlahSiswaAttribute(): int
    {
        return $this->siswa()->count();
    }

    public function getJumlahGuruMengajarAttribute(): int
    {
        return $this->guruMengajar()->count();
    }

    /**
     * @return Collection<int, string>
     */
    public static function pluckNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * @param  Builder<Kelas>  $query
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->when($term !== '', fn ($q) => $q->where('nama', 'like', "%{$term}%"));
    }
}
