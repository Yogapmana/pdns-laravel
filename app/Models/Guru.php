<?php

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'nama_guru'])]
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory;

    protected $table = 'guru';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'id_guru');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'id_guru');
    }

    public function getAllKelasAttribute(): array
    {
        return $this->mengajar()->distinct()->orderBy('kelas')->pluck('kelas')->all();
    }

    public function getAllMapelAttribute(): array
    {
        return $this->mengajar()->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran')->all();
    }

    public function getMapelByKelas(string $kelas): array
    {
        return $this->mengajar()
            ->where('kelas', $kelas)
            ->orderBy('mata_pelajaran')
            ->pluck('mata_pelajaran')
            ->all();
    }

    public function mengajarDiKelasMapel(string $kelas, string $mataPelajaran): bool
    {
        return $this->mengajar()
            ->where('kelas', $kelas)
            ->where('mata_pelajaran', $mataPelajaran)
            ->exists();
    }
}
