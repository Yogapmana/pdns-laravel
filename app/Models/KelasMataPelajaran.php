<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model representing the membership of a single
 * (kelas, mata_pelajaran) pair in the `kelas_mata_pelajaran` pivot table.
 *
 * The table holds the master list of "which subjects are valid for which
 * class". A row here is required before an admin can assign a guru to
 * that (kelas, mata_pelajaran) pair in `guru_mengajar`. The `kelas_id`
 * and `mata_pelajaran_id` columns are FKs to the `kelas` and
 * `mata_pelajaran` master tables, with `ON DELETE CASCADE` so deleting a
 * master automatically cleans its pivot entries.
 *
 * @property int $id
 * @property int $kelas_id
 * @property int $mata_pelajaran_id
 */
#[Fillable(['kelas_id', 'mata_pelajaran_id'])]
class KelasMataPelajaran extends Model
{
    protected $table = 'kelas_mata_pelajaran';

    /**
     * The kelas this pivot row belongs to.
     *
     * @return BelongsTo<Kelas, KelasMataPelajaran>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * The mata pelajaran this pivot row belongs to.
     *
     * @return BelongsTo<MataPelajaran, KelasMataPelajaran>
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
