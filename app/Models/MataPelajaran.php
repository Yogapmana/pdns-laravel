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
 * Model Eloquent yang merepresentasikan mata pelajaran (`mata_pelajaran`).
 *
 * Didukung oleh tabel `mata_pelajaran`. Mata pelajaran diidentifikasi oleh
 * `nama` nya dan direferensikan oleh baris guru_mengajar dan nilai melalui
 * foreign key `mata_pelajaran_id`.
 *
 * @property int $id
 * @property string $nama
 * @property-read int $jumlah_guru_mengajar
 * @property-read int $jumlah_nilai
 * @property-read int $jumlah_kelas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Kelas> $kelas
 */
#[Fillable(['nama'])]
class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    /**
     * Kombinasi guru_mengajar yang ditargetkan ke mata pelajaran ini.
     *
     * @return HasMany<GuruMengajar> Relasi ke model GuruMengajar.
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'mata_pelajaran_id');
    }

    /**
     * Baris data nilai yang memiliki `mata_pelajaran_id` sesuai dengan mata pelajaran ini.
     *
     * @return HasMany<Nilai> Relasi ke model Nilai.
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'mata_pelajaran_id');
    }

    /**
     * Kelas yang memiliki mata pelajaran ini, dihubungkan melalui pivot
     * `kelas_mata_pelajaran` menggunakan composite key `(kelas_id, mata_pelajaran_id)`.
     *
     * @return BelongsToMany<Kelas> Relasi ke model Kelas.
     */
    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(
            Kelas::class,
            'kelas_mata_pelajaran',
            'mata_pelajaran_id',
            'kelas_id',
        )->withTimestamps();
    }

    /**
     * Accessor: jumlah baris data guru_mengajar yang ditargetkan ke mata pelajaran ini.
     *
     * @return int Jumlah baris data guru_mengajar.
     */
    public function getJumlahGuruMengajarAttribute(): int
    {
        return $this->guruMengajar()->count();
    }

    /**
     * Accessor: jumlah baris data nilai untuk mata pelajaran ini.
     *
     * @return int Jumlah baris data nilai.
     */
    public function getJumlahNilaiAttribute(): int
    {
        return $this->nilai()->count();
    }

    /**
     * Accessor: jumlah kelas yang memperbolehkan mata pelajaran ini.
     *
     * @return int Jumlah baris data `kelas_mata_pelajaran` untuk mapel ini.
     */
    public function getJumlahKelasAttribute(): int
    {
        return $this->kelas()->count();
    }

    /**
     * Helper statis yang mengembalikan semua nilai `nama` mata pelajaran, diurutkan secara menaik.
     *
     * Digunakan oleh form dan dropdown yang membutuhkan daftar kanonikal mata pelajaran.
     *
     * @return Collection<int, string> Koleksi string `nama` mata pelajaran.
     */
    public static function pluckNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * Helper statis yang mengembalikan `id` dan `nama` untuk setiap mata pelajaran, diurutkan menaik berdasarkan `nama`.
     *
     * Digunakan oleh form yang memproses FK dan membutuhkan pengiriman ID mapel namun menampilkan
     * nama mapel pada label opsi.
     *
     * @return Collection<int, static> Koleksi model MataPelajaran dengan hanya memilih kolom `id` dan `nama`.
     */
    public static function pluckIdNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->get(['id', 'nama']);
    }

    /**
     * Query scope: menyaring query builder berdasarkan pencarian LIKE pada `nama` mata pelajaran.
     *
     * Kata kunci pencarian yang kosong tidak akan mengubah kueri.
     *
     * @param  Builder<MataPelajaran>  $query  Query builder yang sedang aktif.
     * @param  string  $term  Kata kunci pencarian yang dicari.
     * @return Builder<MataPelajaran> Query builder yang (mungkin) sudah disaring.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->when($term !== '', fn ($q) => $q->where('nama', 'like', "%{$term}%"));
    }
}
