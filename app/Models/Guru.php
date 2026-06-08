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
 * Model Eloquent yang merepresentasikan guru (`guru`).
 *
 * Didukung oleh tabel `guru`. Seorang guru memiliki banyak baris `GuruMengajar`
 * yang menjelaskan kombinasi (kelas, mata_pelajaran) yang boleh mereka ajar.
 * Accessor dan helper di bawah ini digunakan oleh controller khusus guru
 * untuk menyaring dan mengotorisasi input nilai.
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
     * Akun login yang terkait dengan guru ini (1:1, opsional).
     *
     * @return BelongsTo<User, Guru> Relasi ke model User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kombinasi mengajar yang dimiliki oleh guru ini.
     *
     * @return HasMany<GuruMengajar> Relasi ke model GuruMengajar.
     */
    public function mengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'id_guru');
    }

    /**
     * Baris data `Nilai` yang diinput oleh guru ini.
     *
     * @return HasMany<Nilai> Relasi ke model Nilai.
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'id_guru');
    }

    /**
     * Accessor: daftar unik kelas yang diajar oleh guru ini, diurutkan secara menaik.
     *
     * @return array<int, string> Daftar nama kelas.
     */
    public function getAllKelasAttribute(): array
    {
        return $this->mengajar()
            ->with('kelas:id,nama')
            ->get()
            ->pluck('kelas.nama')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Accessor: daftar unik mata pelajaran yang diajar oleh guru ini, diurutkan secara menaik.
     *
     * @return array<int, string> Daftar nama mata pelajaran.
     */
    public function getAllMapelAttribute(): array
    {
        return $this->mengajar()
            ->with('mataPelajaran:id,nama')
            ->get()
            ->pluck('mataPelajaran.nama')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Mengembalikan daftar mata pelajaran yang diajar oleh guru ini untuk kelas tertentu.
     *
     * @param  string  $kelas  Nama kelas (contoh: "X-A").
     * @return array<int, string> Daftar terurut mata pelajaran yang diajar di kelas tersebut.
     */
    public function getMapelByKelas(string $kelas): array
    {
        $kelasId = Kelas::where('nama', $kelas)->value('id');
        if ($kelasId === null) {
            return [];
        }

        return $this->mengajar()
            ->where('kelas_id', $kelasId)
            ->with('mataPelajaran:id,nama')
            ->get()
            ->pluck('mataPelajaran.nama')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Memeriksa apakah guru ini mengajar pasangan (kelas, mata_pelajaran) yang diberikan.
     *
     * @param  string  $kelas  Nama kelas.
     * @param  string  $mataPelajaran  Nama mata pelajaran.
     * @return bool `true` jika baris `GuruMengajar` ada untuk kombinasi ini.
     */
    public function mengajarDiKelasMapel(string $kelas, string $mataPelajaran): bool
    {
        $kelasId = Kelas::where('nama', $kelas)->value('id');
        $mapelId = MataPelajaran::where('nama', $mataPelajaran)->value('id');

        if ($kelasId === null || $mapelId === null) {
            return false;
        }

        return $this->mengajarDiKelasMapelId($kelasId, $mapelId);
    }

    /**
     * Memeriksa apakah guru ini mengajar pasangan ID (kelas_id, mata_pelajaran_id) yang diberikan.
     *
     * @param  int  $kelasId  ID kelas.
     * @param  int  $mataPelajaranId  ID mata pelajaran.
     * @return bool `true` jika baris `GuruMengajar` ada untuk kombinasi ini.
     */
    public function mengajarDiKelasMapelId(int $kelasId, int $mataPelajaranId): bool
    {
        return $this->mengajar()
            ->where('kelas_id', $kelasId)
            ->where('mata_pelajaran_id', $mataPelajaranId)
            ->exists();
    }
}
