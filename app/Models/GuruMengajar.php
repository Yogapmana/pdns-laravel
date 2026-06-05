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
 * mata_pelajaran) combination the guru is allowed to teach. The `kelas`
 * and `mata_pelajaran` columns are non-FK string references matching the
 * `kelas.nama` and `mata_pelajaran.nama` primary display keys.
 *
 * @property int $id
 * @property int $id_guru
 * @property string $kelas
 * @property string $mata_pelajaran
 */
#[Fillable(['id_guru', 'kelas', 'mata_pelajaran'])]
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
}
