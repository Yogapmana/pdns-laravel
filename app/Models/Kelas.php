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
 * Model Eloquent yang merepresentasikan kelompok kelas (`kelas`).
 *
 * Didukung oleh tabel `kelas`. Kelas diidentifikasi berdasarkan nama-nya
 * (contoh: `X-A`, `XI-B`) dan direferensikan oleh baris data siswa, guru_mengajar,
 * dan nilai melalui foreign key `kelas_id`.
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
     * Siswa yang terdaftar di kelas ini.
     *
     * @return HasMany<Siswa> Relasi ke model Siswa.
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    /**
     * Kombinasi guru_mengajar yang ditugaskan ke kelas ini.
     *
     * @return HasMany<GuruMengajar> Relasi ke model GuruMengajar.
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'kelas_id');
    }

    /**
     * Baris data nilai yang memiliki `kelas_id` sesuai dengan kelas ini.
     *
     * @return HasMany<Nilai> Relasi ke model Nilai.
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'kelas_id');
    }

    /**
     * Mata pelajaran yang diperbolehkan untuk kelas ini, dihubungkan melalui pivot
     * `kelas_mata_pelajaran` menggunakan composite key `(kelas_id, mata_pelajaran_id)`.
     *
     * @return BelongsToMany<MataPelajaran> Relasi ke model MataPelajaran.
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
     * Accessor: jumlah siswa yang saat ini berada di kelas ini.
     *
     * @return int Jumlah baris data siswa.
     */
    public function getJumlahSiswaAttribute(): int
    {
        return $this->siswa()->count();
    }

    /**
     * Accessor: jumlah baris data guru_mengajar yang ditugaskan ke kelas ini.
     *
     * @return int Jumlah baris data guru_mengajar.
     */
    public function getJumlahGuruMengajarAttribute(): int
    {
        return $this->guruMengajar()->count();
    }

    /**
     * Accessor: jumlah mata pelajaran yang diperbolehkan untuk kelas ini.
     *
     * @return int Jumlah baris data `kelas_mata_pelajaran` untuk kelas ini.
     */
    public function getJumlahMapelAttribute(): int
    {
        return $this->mataPelajaran()->count();
    }

    /**
     * Helper statis yang mengembalikan semua nilai `nama` kelas, diurutkan secara menaik.
     *
     * Digunakan oleh form dan dropdown yang membutuhkan daftar kanonikal nama kelas.
     *
     * @return Collection<int, string> Koleksi string `nama` kelas.
     */
    public static function pluckNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * Helper statis yang mengembalikan `id` dan `nama` untuk setiap kelas, diurutkan menaik berdasarkan `nama`.
     *
     * Digunakan oleh form yang memproses FK dan membutuhkan pengiriman ID kelas namun menampilkan
     * nama kelas pada label opsi.
     *
     * @return Collection<int, static> Koleksi model Kelas dengan hanya memilih kolom `id` dan `nama`.
     */
    public static function pluckIdNamaOrdered(): Collection
    {
        return static::query()->orderBy('nama')->get(['id', 'nama']);
    }

    /**
     * Query scope: menyaring query builder berdasarkan pencarian LIKE pada `nama` kelas.
     *
     * Kata kunci pencarian yang kosong tidak akan mengubah kueri.
     *
     * @param  Builder<Kelas>  $query  Query builder yang sedang aktif.
     * @param  string  $term  Kata kunci pencarian yang dicari.
     * @return Builder<Kelas> Query builder yang (mungkin) sudah disaring.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->when($term !== '', fn ($q) => $q->where('nama', 'like', "%{$term}%"));
    }
}
