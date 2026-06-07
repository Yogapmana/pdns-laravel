<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model representing a single (kelas, mata_pelajaran) assignment for a guru.
 *
 * Backed by the `guru_mengajar` table. A row exists for every (guru, kelas,
 * mata_pelajaran) combination the guru is allowed to teach. The `kelas_id`
 * and `mata_pelajaran_id` columns are FKs to the `kelas` and `mata_pelajaran`
 * master tables.
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
     * The guru owning this mengajar row.
     *
     * @return BelongsTo<Guru, GuruMengajar>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    /**
     * The kelas this mengajar row targets.
     *
     * @return BelongsTo<Kelas, GuruMengajar>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * The mata pelajaran this mengajar row targets.
     *
     * @return BelongsTo<MataPelajaran, GuruMengajar>
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
