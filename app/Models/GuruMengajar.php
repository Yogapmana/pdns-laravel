<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent yang merepresentasikan satu penugasan (kelas, mata_pelajaran) untuk seorang guru.
 *
 * Didukung oleh tabel `guru_mengajar`. Baris data ada untuk setiap kombinasi
 * (guru, kelas, mata_pelajaran) yang diizinkan untuk diajar oleh guru tersebut.
 * Kolom `kelas_id` dan `mata_pelajaran_id` merupakan Foreign Key ke tabel master
 * `kelas` dan `mata_pelajaran`.
 *
 * @property int $id
 * @property int $id_guru
 * @property int $kelas_id
 * @property int $mata_pelajaran_id
 */
#[Fillable(['id_guru', 'kelas_id', 'mata_pelajaran_id'])]
class GuruMengajar extends Model
{
    protected $table = 'guru_mengajar';

    /**
     * Guru yang memiliki baris mengajar ini.
     *
     * @return BelongsTo<Guru, GuruMengajar> Relasi ke model Guru.
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    /**
     * Kelas yang ditargetkan oleh baris mengajar ini.
     *
     * @return BelongsTo<Kelas, GuruMengajar> Relasi ke model Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Mata pelajaran yang ditargetkan oleh baris mengajar ini.
     *
     * @return BelongsTo<MataPelajaran, GuruMengajar> Relasi ke model MataPelajaran.
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
